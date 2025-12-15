<?php
// php/proveedor/configuracion/configuracionPasswordAction.php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header("Location: ../login.php"); exit();
}
$proveedor_id = (int)$_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';
if ($action !== 'change_password') { header("Location: configuracionPassword.php"); exit(); }

$old = (string)($_POST['old_password'] ?? '');
$new = (string)($_POST['new_password'] ?? '');
$rep = (string)($_POST['confirm_password'] ?? '');

/* === Política fuerte (igual a la de clientes) === */
function is_strong_password(string $pwd): bool {
  if (strlen($pwd) < 10) return false;
  $hasUpper = preg_match('/[A-Z]/', $pwd);
  $hasLower = preg_match('/[a-z]/', $pwd);
  $hasDigit = preg_match('/\d/',   $pwd);
  $hasSym   = preg_match('/[^A-Za-z0-9]/', $pwd);
  if (!($hasUpper && $hasLower && $hasDigit && $hasSym)) return false;

  $low = strtolower($pwd);
  $black = [
    'password','passw0rd','admin','qwerty','letmein','iloveyou',
    '123456','123456789','12345678','abc123','111111','000000'
  ];
  foreach ($black as $b) { if (strpos($low, $b) !== false) return false; }
  return true;
}

/* === Compara input contra stored (hash bcrypt o texto plano) === */
function matches_password(string $input, string $stored): bool {
  if (strlen($stored) >= 60 && str_starts_with($stored, '$2')) {
    return password_verify($input, $stored);
  }
  return hash_equals($stored, $input);
}

/* Validaciones */
$errors = [];
if ($old === '' || $new === '' || $rep === '') $errors[] = "Completá todos los campos.";
if ($new !== $rep) $errors[] = "La confirmación no coincide.";
if (!is_strong_password($new)) $errors[] = "La nueva contraseña no cumple la política (min. 10, con mayúscula, minúscula, dígito y símbolo).";
if (!$errors && hash_equals($old, $new)) $errors[] = "La nueva contraseña no puede ser igual a la actual.";

if ($errors) {
  header("Location: configuracionPassword.php?err=" . urlencode(implode(' ', $errors))); exit();
}

/* Traer contraseña actual (columna `contrasenia`) */
$stmt = $conn->prepare("SELECT contrasenia, nombre, email FROM usuarios WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stored = (string)($row['contrasenia'] ?? '');
if ($stored === '' || !matches_password($old, $stored)) {
  header("Location: configuracionPassword.php?err=" . urlencode("La contraseña actual es incorrecta.")); exit();
}

/* Actualizar SIEMPRE a Bcrypt */
$newHash = password_hash($new, PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE usuarios SET contrasenia=? WHERE user_id=? LIMIT 1");
$stmt->bind_param("si", $newHash, $proveedor_id);
$stmt->execute();
$stmt->close();

/* Notificar a TODOS los administradores */
$provNombre = (string)($row['nombre'] ?? '');
$provEmail  = (string)($row['email']  ?? '');
$sqlN = "
  INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
  SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol = 'admin'
";
$tipo    = 'proveedor_password_cambiada';
$origen  = 'proveedor';
$titulo  = 'Cambio de contraseña de proveedor';
$mensaje = "El proveedor #{$proveedor_id} ({$provNombre} - {$provEmail}) cambió su contraseña.";
$stmtN = $conn->prepare($sqlN);
$stmtN->bind_param("ssss", $tipo, $origen, $titulo, $mensaje);
$stmtN->execute();
$stmtN->close();

header("Location: configuracionPassword.php?ok=" . urlencode("Contraseña actualizada correctamente."));
exit();
