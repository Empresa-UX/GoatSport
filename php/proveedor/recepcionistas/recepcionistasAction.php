<?php
include '../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405); echo 'Método no permitido'; exit;
}

$csrf = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
  http_response_code(403); echo 'CSRF inválido'; exit;
}

function back(string $to='recepcionistas.php'): void { header('Location: '.$to); exit; }
function back_err(array $errors, array $old=[], string $to='recepcionistasForm.php'): void {
  $_SESSION['flash_errors']=$errors;
  $_SESSION['flash_old']=$old;
  header('Location: '.$to); exit;
}
function set_flash(string $msg): void { $_SESSION['flash']=$msg; }

/**
 * NOTIFICAR ADMINS (corregido):
 * - roles posibles: admin / administrador / superadmin
 * - origen: proveedor (evita filtros típicos por origen)
 */
function notify_admins(mysqli $conn, string $tipo, string $titulo, string $mensaje): void {
  $admins = $conn->query("
    SELECT user_id
    FROM usuarios
    WHERE rol IN ('admin','administrador','superadmin')
  ");
  if (!$admins) {
    error_log("[notify_admins] Query admins failed: ".$conn->error);
    return;
  }

  // si no hay admins, no rompe flujo
  if ($admins->num_rows === 0) {
    error_log("[notify_admins] No admins found with rol admin/administrador/superadmin");
    $admins->free();
    return;
  }

  $st = $conn->prepare("
    INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje, creada_en, leida)
    VALUES (?, ?, 'proveedor', ?, ?, NOW(), 0)
  ");
  if (!$st) {
    error_log("[notify_admins] Prepare insert failed: ".$conn->error);
    $admins->free();
    return;
  }

  while ($a = $admins->fetch_assoc()) {
    $uid = (int)$a['user_id'];
    $st->bind_param("isss", $uid, $tipo, $titulo, $mensaje);
    if (!$st->execute()) {
      error_log("[notify_admins] Insert failed for admin {$uid}: ".$st->error);
    }
  }

  $st->close();
  $admins->free();
}

/* Verificar pertenencia */
function own_recepcionista(mysqli $conn, int $recep_id, int $proveedor_id): bool {
  $st = $conn->prepare("
    SELECT 1
    FROM recepcionista_detalle rd
    INNER JOIN usuarios u ON u.user_id=rd.recepcionista_id
    WHERE rd.recepcionista_id=? AND rd.proveedor_id=? AND u.rol='recepcionista'
    LIMIT 1
  ");
  $st->bind_param("ii", $recep_id, $proveedor_id);
  $st->execute();
  $ok = (bool)$st->get_result()->fetch_row();
  $st->close();
  return $ok;
}

/* Password random */
function gen_password(int $len=10): string {
  $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
  $max = strlen($alphabet) - 1;
  $out = '';
  for ($i=0; $i<$len; $i++) {
    $out .= $alphabet[random_int(0, $max)];
  }
  return $out;
}

$action = $_POST['action'] ?? '';

/* ===================== ADD ===================== */
if ($action === 'add') {
  $nombre = trim($_POST['nombre'] ?? '');
  $email  = trim($_POST['email'] ?? '');

  $old = ['nombre'=>$nombre,'email'=>$email];

  $err = [];
  if ($nombre === '' || mb_strlen($nombre) > 100) $err[]='Nombre requerido (máx 100).';
  if ($email === '' || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err[]='Email inválido.';
  if ($err) back_err($err, $old, 'recepcionistasForm.php');

  // email único
  $chk = $conn->prepare("SELECT 1 FROM usuarios WHERE email=? LIMIT 1");
  $chk->bind_param("s", $email);
  $chk->execute();
  $exists = (bool)$chk->get_result()->fetch_row();
  $chk->close();
  if ($exists) back_err(['Ese email ya está registrado.'], $old, 'recepcionistasForm.php');

  $plain = gen_password(10);
  $hash  = password_hash($plain, PASSWORD_BCRYPT);

  $conn->begin_transaction();
  try {
    $insU = $conn->prepare("
      INSERT INTO usuarios (nombre,email,contrasenia,rol)
      VALUES (?,?,?,'recepcionista')
    ");
    $insU->bind_param("sss", $nombre, $email, $hash);
    $insU->execute();
    $recep_id = (int)$conn->insert_id;
    $insU->close();

    $insR = $conn->prepare("
      INSERT INTO recepcionista_detalle (recepcionista_id, proveedor_id)
      VALUES (?,?)
    ");
    $insR->bind_param("ii", $recep_id, $proveedor_id);
    $insR->execute();
    $insR->close();

    $conn->commit();
  } catch (Throwable $e) {
    $conn->rollback();
    error_log("[add recep] ".$e->getMessage());
    back_err(['No se pudo crear el recepcionista.'], $old, 'recepcionistasForm.php');
  }

  // notificar admins (ahora sí)
  notify_admins(
    $conn,
    'recepcionista_creado',
    'Recepcionista creado',
    "El proveedor #{$proveedor_id} creó el recepcionista #{$recep_id} ({$nombre}, {$email})."
  );

  $_SESSION['flash_pass_alert'] = [
    'email' => $email,
    'password' => $plain,
  ];
  set_flash('Recepcionista creado y asignado.');
  back('recepcionistas.php');
}

/* ===================== DELETE ===================== */
if ($action === 'delete') {
  $recep_id = (int)($_POST['recepcionista_id'] ?? 0);
  if ($recep_id <= 0) { set_flash('ID inválido.'); back('recepcionistas.php'); }

  if (!own_recepcionista($conn, $recep_id, $proveedor_id)) {
    http_response_code(403); echo 'No autorizado'; exit;
  }

  $info = $conn->prepare("SELECT nombre,email FROM usuarios WHERE user_id=? AND rol='recepcionista' LIMIT 1");
  $info->bind_param("i", $recep_id);
  $info->execute();
  $row = $info->get_result()->fetch_assoc() ?: [];
  $info->close();
  $nombre = (string)($row['nombre'] ?? ('#'.$recep_id));
  $email  = (string)($row['email'] ?? '');

  $conn->begin_transaction();
  try {
    $delR = $conn->prepare("DELETE FROM recepcionista_detalle WHERE recepcionista_id=? AND proveedor_id=?");
    $delR->bind_param("ii", $recep_id, $proveedor_id);
    $delR->execute();
    $delR->close();

    $delU = $conn->prepare("DELETE FROM usuarios WHERE user_id=? AND rol='recepcionista'");
    $delU->bind_param("i", $recep_id);
    $delU->execute();
    $delU->close();

    $conn->commit();
  } catch (Throwable $e) {
    $conn->rollback();
    error_log("[delete recep] ".$e->getMessage());
    set_flash('No se pudo eliminar el recepcionista.');
    back('recepcionistas.php');
  }

  notify_admins(
    $conn,
    'recepcionista_eliminado',
    'Recepcionista eliminado',
    "El proveedor #{$proveedor_id} eliminó el recepcionista #{$recep_id} ({$nombre}".($email? ", {$email}":"").")."
  );

  set_flash('Recepcionista eliminado.');
  back('recepcionistas.php');
}

back('recepcionistas.php');
