<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {

    $stmt = $conn->prepare("
        INSERT INTO reportes (nombre_reporte, descripcion, respuesta_proveedor, usuario_id, cancha_id, reserva_id, fecha_reporte, estado)
        VALUES (?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "sssiiiss",
        $_POST['nombre_reporte'],
        $_POST['descripcion'],
        $_POST['respuesta_proveedor'],
        $_POST['usuario_id'],
        $_POST['cancha_id'],
        $_POST['reserva_id'],
        $_POST['fecha_reporte'],
        $_POST['estado']
    );

    $stmt->execute();
    $stmt->close();
    header('Location: reportes.php');
    exit;
}

if ($action === 'edit') {

    $stmt = $conn->prepare("
        UPDATE reportes 
        SET nombre_reporte=?, descripcion=?, respuesta_proveedor=?, usuario_id=?, cancha_id=?, reserva_id=?, fecha_reporte=?, estado=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sssiiissi",
        $_POST['nombre_reporte'],
        $_POST['descripcion'],
        $_POST['respuesta_proveedor'],
        $_POST['usuario_id'],
        $_POST['cancha_id'],
        $_POST['reserva_id'],
        $_POST['fecha_reporte'],
        $_POST['estado'],
        $_POST['id']
    );

    $stmt->execute();
    $stmt->close();
    header('Location: reportes.php');
    exit;
}

if ($action === 'delete') {

    $stmt = $conn->prepare("DELETE FROM reportes WHERE id=?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    $stmt->close();

    header('Location: reportes.php');
    exit;
}
