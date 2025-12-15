<?php
// php/admin/configuracion/configuracionPasswordAction.php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header("Location: ../login.php"); exit();
}
$admin_id = (int)$_SESSION['usuario_id'];
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

/* Obtener contrasenia (legado o ya hasheada) */
$stmt = $conn->prepare("SELECT contrasenia FROM usuarios WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();

$current = (string)($row['contrasenia'] ?? '');

/* Verificación legado:
   - Si comienza con prefijos típicos de hash -> usar password_verify
   - Si no, comparar texto plano.
*/
$isHash = false;
foreach (['$2y$','$2a$','$argon2i$','$argon2id$'] as $pref){ if (str_starts_with($current, $pref)) { $isHash = true; break; } }

$okOld = $isHash ? password_verify($old, $current) : hash_equals($current, $old);
if (!$okOld) {
  header("Location: configuracionPassword.php?err=" . urlencode("La contraseña actual es incorrecta.")); exit();
}

/* Actualizar a hash seguro */
$newHash = password_hash($new, PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE usuarios SET contrasenia=? WHERE user_id=? LIMIT 1");
$stmt->bind_param("si", $newHash, $admin_id);
$stmt->execute(); $stmt->close();

header("Location: configuracionPassword.php?ok=" . urlencode("Contraseña actualizada correctamente."));
exit();
