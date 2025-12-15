<?php
// php/recepcionista/configuracion/configuracionPasswordAction.php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'recepcionista') {
  header("Location: ../login.php"); exit();
}
$recepcionista_id = (int)$_SESSION['usuario_id'];
$proveedor_id     = (int)($_SESSION['proveedor_id'] ?? 0);

$action = $_POST['action'] ?? '';
if ($action !== 'change_password') { header("Location: configuracionPassword.php"); exit(); }

$old = (string)($_POST['old_password'] ?? '');
$new = (string)($_POST['new_password'] ?? '');
$rep = (string)($_POST['confirm_password'] ?? '');

$errors = [];
if ($old === '' || $new === '' || $rep === '') $errors[] = "Completá todos los campos.";

/* Política robusta (min 10 + mayus + minus + dígito + símbolo) */
if (strlen($new) < 10 ||
    !preg_match('/[A-Z]/', $new) ||
    !preg_match('/[a-z]/', $new) ||
    !preg_match('/\d/',     $new) ||
    !preg_match('/[^A-Za-z0-9]/', $new)) {
  $errors[] = "La nueva contraseña no cumple la política (min. 10, mayúscula, minúscula, dígito y símbolo).";
}
if ($new !== $rep) $errors[] = "La confirmación no coincide.";

if ($errors) {
  header("Location: configuracionPassword.php?err=" . urlencode(implode(' ', $errors))); exit();
}

/* Leer contrasenia actual (puede ser texto plano o hash) */
$stmt = $conn->prepare("SELECT contrasenia FROM usuarios WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $recepcionista_id);
$stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();

$current = (string)($row['contrasenia'] ?? '');
$isHash = false;
foreach (['$2y$','$2a$','$argon2i$','$argon2id$'] as $pref){ if (str_starts_with($current, $pref)) { $isHash = true; break; } }

$okOld = $isHash ? password_verify($old, $current) : hash_equals($current, $old);
if (!$okOld) {
  header("Location: configuracionPassword.php?err=" . urlencode("La contraseña actual es incorrecta.")); exit();
}

/* Actualizar a hash seguro */
$newHash = password_hash($new, PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE usuarios SET contrasenia=? WHERE user_id=? LIMIT 1");
$stmt->bind_param("si", $newHash, $recepcionista_id);
$stmt->execute(); $stmt->close();

/* Notificaciones: admins + proveedor dueño (si hay) */
$destinatarios = [];
$resAdmins = $conn->query("SELECT user_id FROM usuarios WHERE rol='admin'");
if ($resAdmins) while($r=$resAdmins->fetch_assoc()) $destinatarios[] = (int)$r['user_id'];
if ($proveedor_id > 0) $destinatarios[] = $proveedor_id;
$destinatarios = array_values(array_unique(array_filter($destinatarios)));

if (!empty($destinatarios)) {
  $sqlN = "INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje, creada_en, leida)
           VALUES (?, 'perfil_actualizado', 'recepcion', 'Cambio de contraseña', 'Un recepcionista actualizó su contraseña.', NOW(), 0)";
  $stmtN = $conn->prepare($sqlN);
  foreach ($destinatarios as $uid) {
    $stmtN->bind_param("i", $uid);
    $stmtN->execute();
  }
  $stmtN->close();
}

header("Location: configuracionPassword.php?ok=" . urlencode("Contraseña actualizada correctamente."));
exit();
