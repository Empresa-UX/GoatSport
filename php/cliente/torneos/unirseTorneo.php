<?php
// php/cliente/torneos/unirseTorneo.php

session_start();
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../login.php");
    exit();
}

$cliente_id = $_SESSION['usuario_id'];
$torneo_id  = isset($_POST['torneo_id']) ? (int)$_POST['torneo_id'] : 0;

if ($torneo_id <= 0) {
    header("Location: torneos.php");
    exit();
}

// 1) Verificar que el torneo existe y está abierto
$sql = "
    SELECT t.torneo_id, t.nombre, t.estado, t.proveedor_id, u.nombre AS club
    FROM torneos t
    JOIN usuarios u ON t.proveedor_id = u.user_id
    WHERE t.torneo_id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$result = $stmt->get_result();
$torneo = $result->fetch_assoc();
$stmt->close();

if (!$torneo || $torneo['estado'] !== 'abierto') {
    header("Location: torneos.php");
    exit();
}

$proveedor_id = (int)$torneo['proveedor_id'];
$nombre_torneo = $torneo['nombre'];
$nombre_club   = $torneo['club'];

// 2) Verificar si YA está inscripto
$sql = "
    SELECT participacion_id
    FROM participaciones
    WHERE jugador_id = ? AND torneo_id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $cliente_id, $torneo_id);
$stmt->execute();
$result = $stmt->get_result();
$ya = $result->fetch_assoc();
$stmt->close();

if ($ya) {
    header("Location: torneos.php");
    exit();
}

// 3) Insertar participación
$sql = "
    INSERT INTO participaciones (jugador_id, reserva_id, torneo_id, es_creador, estado)
    VALUES (?, NULL, ?, 0, 'aceptada')
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $cliente_id, $torneo_id);
$stmt->execute();
$stmt->close();

// 4) NOTIFICACIONES

// 4.a) Notificación al jugador
$titulo_jugador  = "Inscripción a torneo " . $nombre_torneo;
$mensaje_jugador = "Te has unido al torneo \"" . $nombre_torneo . "\" en el club " . $nombre_club . ".";

$sqlNotif = "
    INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, link_tipo, link_id)
    VALUES (?, 'torneo', ?, ?, 'torneo', ?)
";
$stmt = $conn->prepare($sqlNotif);
$stmt->bind_param("issi", $cliente_id, $titulo_jugador, $mensaje_jugador, $torneo_id);
$stmt->execute();
$stmt->close();

// 4.b) Notificación al proveedor
// Traemos nombre del jugador
$sql = "SELECT nombre FROM usuarios WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

$nombre_jugador = $res ? $res['nombre'] : 'Un jugador';

$titulo_prov  = "Nuevo inscripto en torneo " . $nombre_torneo;
$mensaje_prov = $nombre_jugador . " se ha inscrito en tu torneo \"" . $nombre_torneo . "\".";

$stmt = $conn->prepare($sqlNotif);
$stmt->bind_param("issi", $proveedor_id, $titulo_prov, $mensaje_prov, $torneo_id);
$stmt->execute();
$stmt->close();

header("Location: torneos.php");
exit();
