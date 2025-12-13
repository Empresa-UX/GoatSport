<?php
// php/recepcionista/configuracion/configuracionAction.php

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'recepcionista') {
    header("Location: ../login.php");
    exit();
}

$recepcionista_id = (int)$_SESSION['usuario_id'];
$proveedor_id     = (int)($_SESSION['proveedor_id'] ?? 0);
$action           = $_POST['action'] ?? '';

if ($action !== 'update_profile') {
    header("Location: configuracion.php");
    exit();
}

/* Inputs */
$nombre_nuevo = trim($_POST['nombre'] ?? '');
$email_nuevo  = trim($_POST['email']  ?? '');

/* Validaciones */
if ($nombre_nuevo === '' || $email_nuevo === '') {
    header("Location: configuracion.php?err=" . urlencode("Nombre y email son obligatorios."));
    exit();
}

/* Leer nombre actual para detectar cambio */
$sql = "SELECT nombre, email FROM usuarios WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recepcionista_id);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();
$stmt->close();

$nombre_actual = $actual['nombre'] ?? '';
$email_actual  = $actual['email']  ?? '';

/* Update */
$sql = "UPDATE usuarios SET nombre = ?, email = ? WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $nombre_nuevo, $email_nuevo, $recepcionista_id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    header("Location: configuracion.php?err=" . urlencode("No se pudo actualizar el perfil."));
    exit();
}

/* Notificar SOLO si cambió el nombre (nombre o apellido) */
if ($nombre_nuevo !== $nombre_actual) {
    $titulo  = "Recepcionista actualizó su nombre";
    $mensaje = sprintf(
        "El recepcionista (ID %d) cambió su nombre de '%s' a '%s'.",
        $recepcionista_id,
        $nombre_actual,
        $nombre_nuevo
    );

    // Destinatarios: todos los admins + proveedor del club (si hay)
    $destinatarios = [];

    $resAdmins = $conn->query("SELECT user_id FROM usuarios WHERE rol = 'admin'");
    if ($resAdmins) {
        while ($r = $resAdmins->fetch_assoc()) { $destinatarios[] = (int)$r['user_id']; }
    }
    if ($proveedor_id > 0) { $destinatarios[] = $proveedor_id; }

    // Dedup
    $destinatarios = array_values(array_unique(array_filter($destinatarios)));

    if (!empty($destinatarios)) {
        $sqlN = "INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje, creada_en, leida)
                 VALUES (?, ?, ?, ?, ?, NOW(), 0)";
        $stmtN = $conn->prepare($sqlN);
        $tipo   = "perfil_actualizado";
        $origen = "recepcion"; // enum válido en tu tabla

        foreach ($destinatarios as $uid) {
            $stmtN->bind_param("issss", $uid, $tipo, $origen, $titulo, $mensaje);
            $stmtN->execute();
        }
        $stmtN->close();
    }
}

header("Location: configuracion.php?ok=" . urlencode("Perfil actualizado correctamente."));
exit();
