<?php
/* =====================================================================
 * file: php/recepcionista/clientes/clientesAction.php
 * COMPLETO: reemplaza el archivo con esto
 * ===================================================================== */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config.php';

$nombre    = trim($_POST['nombre']    ?? '');
$email     = trim($_POST['email']     ?? '');
$password  = (string)($_POST['password'] ?? '');
$password2 = (string)($_POST['password_confirm'] ?? '');
$telefono  = trim($_POST['telefono']  ?? '');

if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
  header("Location: clientes.php?err=Datos incompletos"); exit;
}
if ($password !== $password2) {
  header("Location: clientes.php?err=Las contraseñas no coinciden"); exit;
}

/* Seguridad: política de contraseñas */
function is_strong_password(string $pwd): bool {
  if (strlen($pwd) < 10) return false;
  $hasUpper = preg_match('/[A-Z]/', $pwd);
  $hasLower = preg_match('/[a-z]/', $pwd);
  $hasDigit = preg_match('/\d/',   $pwd);
  $hasSym   = preg_match('/[^A-Za-z0-9]/', $pwd);
  if (!($hasUpper && $hasLower && $hasDigit && $hasSym)) return false;

  // por qué: bloquear variantes muy comunes
  $low = strtolower($pwd);
  $black = [
    'password','passw0rd','admin','qwerty','letmein','iloveyou',
    '123456','123456789','12345678','abc123','111111','000000'
  ];
  foreach ($black as $b) { if (strpos($low, $b) !== false) return false; }

  return true;
}

if (!is_strong_password($password)) {
  header("Location: clientes.php?err=La contraseña no cumple la política (min. 10, mayúscula, minúscula, dígito y símbolo)"); exit;
}

/* Email único */
$check = $conn->prepare("SELECT 1 FROM usuarios WHERE email = ? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$exists = (bool)$check->get_result()->fetch_row();
$check->close();
if ($exists) { header("Location: clientes.php?err=El email ya está registrado"); exit; }

$hash = password_hash($password, PASSWORD_DEFAULT);
$rol  = 'cliente';

$conn->begin_transaction();
try {
  /* 1) usuarios */
  $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol) VALUES (?,?,?,?)");
  $stmt->bind_param("ssss", $nombre, $email, $hash, $rol);
  $stmt->execute();
  $cliente_id = (int)$stmt->insert_id;
  $stmt->close();

  /* 2) cliente_detalle (siempre) */
  $tel = ($telefono !== '') ? $telefono : null;     // por qué: queremos fila, el teléfono puede ser NULL
  $insDet = $conn->prepare("INSERT INTO cliente_detalle (cliente_id, telefono) VALUES (?, ?)");
  $insDet->bind_param("is", $cliente_id, $tel);
  $insDet->execute();
  $insDet->close();

  /* 3) ranking (fila base) */
  $insRank = $conn->prepare("INSERT INTO ranking (usuario_id) VALUES (?)");
  $insRank->bind_param("i", $cliente_id);
  $insRank->execute();
  $insRank->close();

  /* 4) Notificar admins */
  $sqlN = "
    INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
    SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol = 'admin'
  ";
  $tipo    = 'cliente_alta';
  $origen  = 'recepcion';
  $titulo  = "Nuevo cliente #{$cliente_id}";
  $mensaje = "Un nuevo cliente se ha registrado en GoatSport.";
  $stmtN = $conn->prepare($sqlN);
  $stmtN->bind_param("ssss", $tipo, $origen, $titulo, $mensaje);
  $stmtN->execute();
  $stmtN->close();

  $conn->commit();
  header("Location: clientes.php?ok=1&id={$cliente_id}");
  exit;
} catch (Throwable $e) {
  $conn->rollback();
  if (strpos(strtolower($e->getMessage()), 'duplicate') !== false) {
    header("Location: clientes.php?err=El email ya está registrado"); exit;
  }
  header("Location: clientes.php?err=Error al crear cliente"); exit;
}
