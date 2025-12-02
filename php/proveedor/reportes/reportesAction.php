<?php
// php/proveedor/reportes/reportesAction.php

session_start();
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

if ($action !== 'update_estado') {
    header("Location: reportes.php");
    exit();
}

$reporte_id = (int)($_POST['id'] ?? 0);
$nuevo_estado = $_POST['estado'] ?? 'Pendiente';

if (!$reporte_id || !in_array($nuevo_estado, ['Pendiente', 'Resuelto'])) {
    header("Location: reportes.php");
    exit();
}

// ¿Viene respuesta_proveedor? (solo desde el form detallado)
$respuesta_proveedor = isset($_POST['respuesta_proveedor'])
    ? trim($_POST['respuesta_proveedor'])
    : null;

// 1) Verificamos que el reporte pertenece a este proveedor y traemos datos para notificación
$sql = "
    SELECT 
        r.id,
        r.usuario_id,
        r.nombre_reporte,
        r.estado,
        r.respuesta_proveedor,
        c.proveedor_id AS prov_cancha,
        c2.proveedor_id AS prov_reserva
    FROM reportes r
    LEFT JOIN canchas c ON r.cancha_id = c.cancha_id
    LEFT JOIN reservas res ON r.reserva_id = res.reserva_id
    LEFT JOIN canchas c2 ON res.cancha_id = c2.cancha_id
    WHERE r.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reporte_id);
$stmt->execute();
$result = $stmt->get_result();
$reporte = $result->fetch_assoc();
$stmt->close();

if (
    !$reporte ||
    !(
        ($reporte['prov_cancha'] && $reporte['prov_cancha'] == $proveedor_id) ||
        ($reporte['prov_reserva'] && $reporte['prov_reserva'] == $proveedor_id)
    )
) {
    header("Location: reportes.php");
    exit();
}

$estado_anterior = $reporte['estado'];

// 2) Actualizar estado (y respuesta si vino)
if ($respuesta_proveedor !== null) {
    $sql = "UPDATE reportes SET estado = ?, respuesta_proveedor = ? WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $nuevo_estado, $respuesta_proveedor, $reporte_id);
} else {
    $sql = "UPDATE reportes SET estado = ? WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevo_estado, $reporte_id);
}
$stmt->execute();
$stmt->close();

// 3) Si pasó a Resuelto, mandamos notificación al jugador
if ($estado_anterior !== 'Resuelto' && $nuevo_estado === 'Resuelto') {
    $usuario_destino = $reporte['usuario_id'];
    $tipo    = 'reporte_resuelto';
    $titulo  = 'Tu reporte ha sido resuelto';
    $mensaje = 'Tu reporte "' . $reporte['nombre_reporte'] . '" fue marcado como Resuelto. ¡Gracias por avisar!';

    $sql = "
        INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje)
        VALUES (?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $usuario_destino, $tipo, $titulo, $mensaje);
    $stmt->execute();
    $stmt->close();
}

header("Location: reportes.php");
exit();
