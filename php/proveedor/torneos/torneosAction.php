<?php
// php/proveedor/torneos/torneosAction.php

include '../../config.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $nombre         = trim($_POST['nombre'] ?? '');
    $fecha_inicio   = $_POST['fecha_inicio'] ?? null;
    $fecha_fin      = $_POST['fecha_fin'] ?? null;
    $estado         = $_POST['estado'] ?? 'abierto';
    $puntos_ganador = (int)($_POST['puntos_ganador'] ?? 0);

    if ($nombre === '' || !$fecha_inicio || !$fecha_fin) {
        header("Location: torneosForm.php");
        exit();
    }

    $sql = "
        INSERT INTO torneos (nombre, creador_id, proveedor_id, fecha_inicio, fecha_fin, estado, puntos_ganador)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "siisssi",
        $nombre,
        $proveedor_id,   // creador_id = proveedor logueado
        $proveedor_id,   // proveedor_id = dueño del club
        $fecha_inicio,
        $fecha_fin,
        $estado,
        $puntos_ganador
    );
    $stmt->execute();
    $stmt->close();

    header("Location: torneos.php");
    exit();
}

if ($action === 'edit') {
    $torneo_id      = (int)($_POST['torneo_id'] ?? 0);
    $nombre         = trim($_POST['nombre'] ?? '');
    $fecha_inicio   = $_POST['fecha_inicio'] ?? null;
    $fecha_fin      = $_POST['fecha_fin'] ?? null;
    $estado         = $_POST['estado'] ?? 'abierto';
    $puntos_ganador = (int)($_POST['puntos_ganador'] ?? 0);

    if ($torneo_id <= 0 || $nombre === '' || !$fecha_inicio || !$fecha_fin) {
        header("Location: torneos.php");
        exit();
    }

    $sql = "
        UPDATE torneos
        SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, estado = ?, puntos_ganador = ?
        WHERE torneo_id = ? AND proveedor_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssiiii",
        $nombre,
        $fecha_inicio,
        $fecha_fin,
        $estado,
        $puntos_ganador,
        $torneo_id,
        $proveedor_id
    );
    $stmt->execute();
    $stmt->close();

    header("Location: torneos.php");
    exit();
}

if ($action === 'delete') {
    $torneo_id = (int)($_POST['torneo_id'] ?? 0);
    if ($torneo_id > 0) {
        // Opcional: borrar participaciones y partidos del torneo primero
        /*
        $stmt = $conn->prepare("DELETE FROM participaciones WHERE torneo_id = ?");
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM partidos WHERE torneo_id = ?");
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        $stmt->close();
        */

        $sql = "DELETE FROM torneos WHERE torneo_id = ? AND proveedor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $torneo_id, $proveedor_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: torneos.php");
    exit();
}

header("Location: torneos.php");
exit();
