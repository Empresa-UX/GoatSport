<?php
// php/recepcionista/configuracion/configuracionAction.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'recepcionista') {
  header("Location: ../login.php"); exit();
}

$recepcionista_id = (int)$_SESSION['usuario_id'];
$proveedor_id     = (int)($_SESSION['proveedor_id'] ?? 0);
$action           = $_POST['action'] ?? '';
if ($action !== 'update_profile') { header("Location: configuracion.php"); exit(); }

/* Normalizador */
$norm = function(?string $s, int $max){
  $s = trim((string)$s);
  $s = preg_replace('/\s+/u', ' ', $s);
  return mb_substr($s, 0, $max);
};

$nombre_nuevo = $norm($_POST['nombre'] ?? '', 80);
$email_nuevo  = $norm($_POST['email']  ?? '', 120);

$errors = [];
if ($nombre_nuevo === '') $errors[] = "El nombre es obligatorio.";
if ($email_nuevo === '' || !filter_var($email_nuevo, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido.";

/* Email único (excluye su propio user_id) */
if (!$errors) {
  $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE email=? AND user_id<>? LIMIT 1");
  $stmt->bind_param("si", $email_nuevo, $recepcionista_id);
  $stmt->execute(); $dup = (bool)$stmt->get_result()->fetch_row(); $stmt->close();
  if ($dup) $errors[] = "Ese email ya está en uso.";
}

if ($errors) {
  header("Location: configuracion.php?err=" . urlencode(implode(' ', $errors))); exit();
}

/* Leer valores actuales para detectar cambios */
$stmt = $conn->prepare("SELECT nombre, email FROM usuarios WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $recepcionista_id);
$stmt->execute(); $act = $stmt->get_result()->fetch_assoc(); $stmt->close();

$nombre_actual = $act['nombre'] ?? '';
$email_actual  = $act['email']  ?? '';

/* Guardar */
$stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=? WHERE user_id=? LIMIT 1");
$stmt->bind_param("ssi", $nombre_nuevo, $email_nuevo, $recepcionista_id);
$ok = $stmt->execute(); $stmt->close();

if (!$ok) {
  header("Location: configuracion.php?err=" . urlencode("No se pudieron guardar los cambios.")); exit();
}

/* Notificar si cambió nombre o email */
if ($nombre_nuevo !== $nombre_actual || $email_nuevo !== $email_actual) {
  $titulo  = "Recepcionista actualizó su perfil";
  $mensaje = sprintf(
    "El recepcionista (ID %d) actualizó su perfil:%s%s",
    $recepcionista_id,
    ($nombre_nuevo !== $nombre_actual) ? " Nombre: '{$nombre_actual}' → '{$nombre_nuevo}'." : "",
    ($email_nuevo  !== $email_actual)  ? " Email: '{$email_actual}' → '{$email_nuevo}'."     : ""
  );

  // Destinatarios: todos los admins + proveedor dueño (si hay)
  $destinatarios = [];

  $resAdmins = $conn->query("SELECT user_id FROM usuarios WHERE rol='admin'");
  if ($resAdmins) while($r=$resAdmins->fetch_assoc()) $destinatarios[] = (int)$r['user_id'];
  if ($proveedor_id > 0) $destinatarios[] = $proveedor_id;

  $destinatarios = array_values(array_unique(array_filter($destinatarios)));
  if (!empty($destinatarios)) {
    $sqlN = "INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje, creada_en, leida)
             VALUES (?, ?, 'recepcion', ?, ?, NOW(), 0)";
    $stmtN = $conn->prepare($sqlN);
    $tipo  = "perfil_actualizado";
    foreach ($destinatarios as $uid) {
      $stmtN->bind_param("isss", $uid, $tipo, $titulo, $mensaje);
      $stmtN->execute();
    }
    $stmtN->close();
  }
}

header("Location: configuracion.php?ok=" . urlencode("Perfil actualizado correctamente."));
exit();
