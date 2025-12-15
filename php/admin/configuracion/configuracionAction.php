<?php
// php/admin/configuracion/configuracionAction.php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header("Location: ../login.php"); exit();
}
$admin_id = (int)$_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';
if ($action !== 'update_profile') { header("Location: configuracion.php"); exit(); }

/* Normalizador */
$norm = function(?string $s, int $max){
  $s = trim((string)$s);
  $s = preg_replace('/\s+/u', ' ', $s);
  return mb_substr($s, 0, $max);
};
$nombre = $norm($_POST['nombre'] ?? '', 80);
$email  = $norm($_POST['email']  ?? '', 120);

$errors = [];
if ($nombre === '') $errors[] = "El nombre es obligatorio.";
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido.";

/* Email único (excluyéndose) */
if (!$errors) {
  $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE email=? AND user_id<>? LIMIT 1");
  $stmt->bind_param("si", $email, $admin_id);
  $stmt->execute(); $dup = (bool)$stmt->get_result()->fetch_row(); $stmt->close();
  if ($dup) $errors[] = "Ese email ya está en uso.";
}

if ($errors) {
  header("Location: configuracion.php?err=" . urlencode(implode(' ', $errors))); exit();
}

/* Guardar */
$stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=? WHERE user_id=? AND rol='admin' LIMIT 1");
$stmt->bind_param("ssi", $nombre, $email, $admin_id);
$stmt->execute(); $stmt->close();

header("Location: configuracion.php?ok=" . urlencode("Perfil actualizado correctamente."));
exit();
