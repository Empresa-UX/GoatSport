<?php
/* =====================================================================
 * file: php/recepcionista/clientes/clientesAction.php
 * COMPLETO: reemplaza el archivo con esto
 * ===================================================================== */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config.php';

$nombre   = trim($_POST['nombre']   ?? '');
$email    = trim($_POST['email']    ?? '');
$password = (string)($_POST['password'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

if ($nombre === '' || $email === '' || $password === '') {
    header("Location: clientes.php?err=Datos incompletos"); exit;
}

// Email único
$check = $conn->prepare("SELECT 1 FROM usuarios WHERE email = ? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$exists = (bool)$check->get_result()->fetch_row();
$check->close();
if ($exists) { header("Location: clientes.php?err=El email ya está registrado"); exit; }

// Crear usuario (hash)
$hash = password_hash($password, PASSWORD_DEFAULT);
$rol  = 'cliente';

$stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol, puntos) VALUES (?,?,?,?,0)");
$stmt->bind_param("ssss", $nombre, $email, $hash, $rol);
try {
    $stmt->execute();
    $cliente_id = (int)$stmt->insert_id;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    header("Location: clientes.php?err=Error al crear usuario"); exit;
}

// Teléfono opcional
if ($telefono !== '') {
    $ins = $conn->prepare("INSERT INTO cliente_detalle (cliente_id, telefono) VALUES (?, ?)");
    $ins->bind_param("is", $cliente_id, $telefono);
    $ins->execute();
    $ins->close();
}

/* ===================== Notificar a ADMINS ===================== */
/* 1 fila por admin, con origen='recepcion' y mensaje genérico */
$sqlN = "
INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
SELECT user_id, ?, ?, ?, ?
FROM usuarios
WHERE rol = 'admin'
";
$tipo    = 'cliente_alta';
$origen  = 'recepcion';
$titulo  = "Nuevo cliente #{$cliente_id}";
$mensaje = "Un nuevo cliente se ha registrado en GoatSport.";

$stmtN = $conn->prepare($sqlN);
$stmtN->bind_param("ssss", $tipo, $origen, $titulo, $mensaje);
$stmtN->execute();
$stmtN->close();
/* ============================================================= */

header("Location: clientes.php?ok=1&id={$cliente_id}");
exit;
