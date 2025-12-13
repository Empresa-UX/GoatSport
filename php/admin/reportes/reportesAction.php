<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $stmt = $conn->prepare("
        INSERT INTO reportes (nombre_reporte, descripcion, respuesta_proveedor, usuario_id, cancha_id, reserva_id, fecha_reporte, estado, tipo_falla)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");
    $tipo_falla = $_POST['tipo_falla'] ?? 'cancha'; // por defecto como en la tabla
    $stmt->bind_param(
        "sssiiisss",
        $_POST['nombre_reporte'],
        $_POST['descripcion'],
        $_POST['respuesta_proveedor'],
        $_POST['usuario_id'],
        $_POST['cancha_id'],
        $_POST['reserva_id'],
        $_POST['fecha_reporte'],
        $_POST['estado'],
        $tipo_falla
    );
    $stmt->execute();
    $stmt->close();

    header('Location: reportes.php');
    exit;
}

if ($action === 'edit') {
    $stmt = $conn->prepare("
        UPDATE reportes 
        SET nombre_reporte=?,
            descripcion=?,
            respuesta_proveedor=?,
            usuario_id=?,
            cancha_id=?,
            reserva_id=?,
            fecha_reporte=?,
            estado=?,
            tipo_falla=?
        WHERE id=?
    ");
    $tipo_falla = $_POST['tipo_falla'] ?? 'cancha';
    $stmt->bind_param(
        "sssiiisssi",
        $_POST['nombre_reporte'],
        $_POST['descripcion'],
        $_POST['respuesta_proveedor'],
        $_POST['usuario_id'],
        $_POST['cancha_id'],
        $_POST['reserva_id'],
        $_POST['fecha_reporte'],
        $_POST['estado'],
        $tipo_falla,
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

/* === Nuevo: actualizar solo el estado (Pendiente -> Resuelto) === */
if ($action === 'update_estado') {
    $id     = (int)($_POST['id'] ?? 0);
    $estado = $_POST['estado'] ?? '';

    $ok = false;
    if ($id > 0 && $estado === 'Resuelto') {
        // Solo permitimos pasar de Pendiente a Resuelto
        $stmt = $conn->prepare("UPDATE reportes SET estado='Resuelto' WHERE id=? AND estado='Pendiente'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
    }

    // Para el click inline en reportes.php devolvemos JSON
    header('Content-Type: application/json');
    echo json_encode(['ok' => $ok]);
    exit;
}

/* fallback */
header('Location: reportes.php');
exit;
