<?php
// php/proveedor/notificaciones/notificacionesAction.php

include '../../config.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$action       = $_POST['action'] ?? '';

if ($action === 'mark_read' || $action === 'mark_unread') {
    $notif_id = isset($_POST['notificacion_id']) ? (int)$_POST['notificacion_id'] : 0;

    if ($notif_id > 0) {
        $nuevoValor = ($action === 'mark_read') ? 1 : 0;

        $sql = "
            UPDATE notificaciones
            SET leida = ?
            WHERE notificacion_id = ? AND usuario_id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $nuevoValor, $notif_id, $proveedor_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: notificaciones.php");
    exit();
}

if ($action === 'mark_all_read') {
    $sql = "
        UPDATE notificaciones
        SET leida = 1
        WHERE usuario_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $proveedor_id);
    $stmt->execute();
    $stmt->close();

    header("Location: notificaciones.php");
    exit();
}

header("Location: notificaciones.php");
exit();
