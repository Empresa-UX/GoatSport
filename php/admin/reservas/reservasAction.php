<?php
include './../../config.php';

$action = $_REQUEST['action'] ?? '';

if ($action == 'add') {
    $cancha_id    = (int)($_POST['cancha_id'] ?? 0);
    $creador_id   = (int)($_POST['creador_id'] ?? 0);
    $fecha        = $_POST['fecha'] ?? '';
    $hora_inicio  = $_POST['hora_inicio'] ?? '';
    $hora_fin     = $_POST['hora_fin'] ?? '';
    $precio_total = $_POST['precio_total'] ?? 0;
    $tipo_reserva = $_POST['tipo_reserva'] ?? 'equipo';
    $estado       = $_POST['estado'] ?? 'pendiente';

    // Pequeña validación de horas: si fin <= inicio, forzamos +1h
    if ($hora_inicio && $hora_fin && $hora_fin <= $hora_inicio) {
        $hi = strtotime($hora_inicio);
        $hora_fin = date('H:i:s', $hi + 3600);
    }

    $stmt = $conn->prepare("
        INSERT INTO reservas 
        (cancha_id, creador_id, fecha, hora_inicio, hora_fin, precio_total, tipo_reserva, estado)
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "iisssdss",
        $cancha_id,
        $creador_id,
        $fecha,
        $hora_inicio,
        $hora_fin,
        $precio_total,
        $tipo_reserva,
        $estado
    );
    $stmt->execute();
    $stmt->close();

    header('Location: reservas.php');
    exit();
}

if ($action == 'edit') {
    $reserva_id   = (int)($_POST['reserva_id'] ?? 0);
    $cancha_id    = (int)($_POST['cancha_id'] ?? 0);
    $creador_id   = (int)($_POST['creador_id'] ?? 0);
    $fecha        = $_POST['fecha'] ?? '';
    $hora_inicio  = $_POST['hora_inicio'] ?? '';
    $hora_fin     = $_POST['hora_fin'] ?? '';
    $precio_total = $_POST['precio_total'] ?? 0;
    $tipo_reserva = $_POST['tipo_reserva'] ?? 'equipo';
    $estado       = $_POST['estado'] ?? 'pendiente';

    if ($hora_inicio && $hora_fin && $hora_fin <= $hora_inicio) {
        $hi = strtotime($hora_inicio);
        $hora_fin = date('H:i:s', $hi + 3600);
    }

    $stmt = $conn->prepare("
        UPDATE reservas 
        SET cancha_id = ?, 
            creador_id = ?, 
            fecha = ?, 
            hora_inicio = ?, 
            hora_fin = ?, 
            precio_total = ?, 
            tipo_reserva = ?, 
            estado = ?
        WHERE reserva_id = ?
    ");
    $stmt->bind_param(
        "iisssdssi",
        $cancha_id,
        $creador_id,
        $fecha,
        $hora_inicio,
        $hora_fin,
        $precio_total,
        $tipo_reserva,
        $estado,
        $reserva_id
    );
    $stmt->execute();
    $stmt->close();

    header('Location: reservas.php');
    exit();
}

if ($action == 'delete') {
    $reserva_id = (int)($_POST['reserva_id'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM reservas WHERE reserva_id = ?");
    $stmt->bind_param("i", $reserva_id);
    $stmt->execute();
    $stmt->close();

    header('Location: reservas.php');
    exit();
}

// si llega algo raro, volvemos
header('Location: reservas.php');
exit();
