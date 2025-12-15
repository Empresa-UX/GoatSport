<?php
// php/proveedor/configuracion/configuracionAction.php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header("Location: ../login.php"); exit();
}
$proveedor_id = (int)$_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';
if ($action !== 'update_profile') { header("Location: configuracion.php"); exit(); }

/* Helper: normalizar y limitar */
$norm = function(?string $s, int $max){
  $s = trim((string)$s);
  $s = preg_replace('/\s+/u', ' ', $s);
  return mb_substr($s, 0, $max);
};

$nombre       = $norm($_POST['nombre']       ?? '', 80);
$email        = $norm($_POST['email']        ?? '', 120);
$nombre_club  = $norm($_POST['nombre_club']  ?? '', 100);
$telefono     = $norm($_POST['telefono']     ?? '', 25);
$direccion    = $norm($_POST['direccion']    ?? '', 140);
$ciudad       = $norm($_POST['ciudad']       ?? '', 80);
$descripcion  = $norm($_POST['descripcion']  ?? '', 1000);

$errors = [];
if ($nombre === '') $errors[] = "El nombre es obligatorio.";
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido.";
if ($telefono !== '' && !preg_match('/^[\d+\s\-()]{5,25}$/', $telefono)) $errors[] = "Teléfono inválido.";

if ($errors) {
  header("Location: configuracion.php?err=" . urlencode(implode(' ', $errors))); exit();
}

/* Leer valores actuales para comparar cambios y validar unicidad de email solo si cambia */
$stmt = $conn->prepare("
  SELECT u.nombre AS u_nombre, u.email AS u_email,
         d.nombre_club, d.telefono, d.direccion, d.ciudad, d.descripcion
  FROM usuarios u
  LEFT JOIN proveedores_detalle d ON d.proveedor_id = u.user_id
  WHERE u.user_id=? LIMIT 1
");
$stmt->bind_param("i", $proveedor_id);
$stmt->execute(); $cur = $stmt->get_result()->fetch_assoc(); $stmt->close();

$cur = $cur ?: [
  'u_nombre'=>'', 'u_email'=>'', 'nombre_club'=>'', 'telefono'=>'',
  'direccion'=>'', 'ciudad'=>'', 'descripcion'=>''
];

$emailCambio = (strcasecmp($email, (string)$cur['u_email']) !== 0);

/* Unicidad del email (si cambió) */
if ($emailCambio) {
  $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE email=? AND user_id<>? LIMIT 1");
  $stmt->bind_param("si", $email, $proveedor_id);
  $stmt->execute(); $dup = (bool)$stmt->get_result()->fetch_row(); $stmt->close();
  if ($dup) {
    header("Location: configuracion.php?err=" . urlencode("Ese email ya está en uso.")); exit();
  }
}

/* Detectar cambios reales */
$changed = false;
$changed = $changed || (strcmp($nombre,       (string)$cur['u_nombre'])    !== 0);
$changed = $changed || (strcmp($email,        (string)$cur['u_email'])     !== 0);
$changed = $changed || (strcmp($nombre_club,  (string)$cur['nombre_club']) !== 0);
$changed = $changed || (strcmp($telefono,     (string)$cur['telefono'])    !== 0);
$changed = $changed || (strcmp($direccion,    (string)$cur['direccion'])   !== 0);
$changed = $changed || (strcmp($ciudad,       (string)$cur['ciudad'])      !== 0);
$changed = $changed || (strcmp($descripcion,  (string)$cur['descripcion']) !== 0);

if (!$changed) {
  header("Location: configuracion.php?ok=" . urlencode("No hay cambios para guardar.")); exit();
}

/* Guardar y notificar admins */
$conn->begin_transaction();
try {
  $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=? WHERE user_id=? LIMIT 1");
  $stmt->bind_param("ssi", $nombre, $email, $proveedor_id);
  $stmt->execute(); $stmt->close();

  $stmt = $conn->prepare("
    INSERT INTO proveedores_detalle (proveedor_id, nombre_club, telefono, direccion, ciudad, descripcion)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      nombre_club=VALUES(nombre_club),
      telefono=VALUES(telefono),
      direccion=VALUES(direccion),
      ciudad=VALUES(ciudad),
      descripcion=VALUES(descripcion)
  ");
  $stmt->bind_param("isssss", $proveedor_id, $nombre_club, $telefono, $direccion, $ciudad, $descripcion);
  $stmt->execute(); $stmt->close();

  /* Notificación a TODOS los admins (solo si hubo cambios) */
  $titulo  = "Perfil de proveedor actualizado";
  $mensaje = "El proveedor #{$proveedor_id} actualizó su perfil.";
  $tipo    = "proveedor_perfil_actualizado";
  $origen  = "proveedor";

  $sqlN = "
    INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
    SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol = 'admin'
  ";
  $stmtN = $conn->prepare($sqlN);
  $stmtN->bind_param("ssss", $tipo, $origen, $titulo, $mensaje);
  $stmtN->execute(); $stmtN->close();

  $conn->commit();
} catch (Throwable $e) {
  $conn->rollback();
  header("Location: configuracion.php?err=" . urlencode("No se pudieron guardar los cambios.")); exit();
}

header("Location: configuracion.php?ok=" . urlencode("Perfil actualizado correctamente."));
exit();
