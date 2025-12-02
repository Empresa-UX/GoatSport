<?php
// php/proveedor/reservas/reservasAction.php

include '../../config.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

if ($action === 'edit') {
    $reserva_id = (int) ($_POST['reserva_id'] ?? 0);
    $nuevo_estado = $_POST['estado'] ?? 'pendiente';

    // 1) Traer info de la reserva (para notificación y validar propiedad)
    $sql = "
        SELECT 
            r.reserva_id,
            r.creador_id,
            r.fecha,
            r.hora_inicio,
            r.hora_fin,
            c.nombre AS cancha,
            c.proveedor_id
        FROM reservas r
        INNER JOIN canchas c ON r.cancha_id = c.cancha_id
        WHERE r.reserva_id = ? AND c.proveedor_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $reserva_id, $proveedor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $reserva = $res->fetch_assoc();
    $stmt->close();

    if (!$reserva) {
        header("Location: reservas.php");
        exit();
    }

    $creador_id = (int) $reserva['creador_id'];
    $fecha = $reserva['fecha'];
    $hora_inicio = substr($reserva['hora_inicio'], 0, 5);
    $hora_fin = substr($reserva['hora_fin'], 0, 5);
    $cancha = $reserva['cancha'];

    // 2) Actualizar estado de la reserva
    $sql = "
        UPDATE reservas r
        INNER JOIN canchas c ON r.cancha_id = c.cancha_id
        SET r.estado = ?
        WHERE r.reserva_id = ? AND c.proveedor_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $nuevo_estado, $reserva_id, $proveedor_id);
    $stmt->execute();
    $stmt->close();

    // 3) Crear notificación al jugador
    $estado_legible = '';
    switch ($nuevo_estado) {
        case 'confirmada':
            $estado_legible = 'confirmada';
            break;
        case 'pendiente':
            $estado_legible = 'pendiente';
            break;
        case 'cancelada':
            $estado_legible = 'cancelada';
            break;
        case 'no_show':
            $estado_legible = 'marcada como no presentada';
            break;
        default:
            $estado_legible = $nuevo_estado;
    }

    $titulo = "Actualización de tu reserva";
    $mensaje = "Tu reserva en la cancha \"" . $cancha . "\" para el "
        . $fecha . " de " . $hora_inicio . " a " . $hora_fin
        . " ha sido " . $estado_legible . ".";

    $sqlNotif = "
    INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje)
    VALUES (?, ?, ?, ?)
";

    $tipo = 'reserva_estado';
    $stmt = $conn->prepare($sqlNotif);
    $stmt->bind_param("isss", $creador_id, $tipo, $titulo, $mensaje);
    $stmt->execute();
    $stmt->close();

}

// No soportamos add/delete para proveedor por ahora
header("Location: reservas.php");
exit();
