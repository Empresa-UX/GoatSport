<?php
// php/proveedor/canchas/canchasAction.php

include '../../config.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $nombre          = trim($_POST['nombre'] ?? '');
    $ubicacion       = trim($_POST['ubicacion'] ?? '');
    $tipo            = trim($_POST['tipo'] ?? '');
    $capacidad       = $_POST['capacidad'] !== '' ? (int)$_POST['capacidad'] : null;
    $precio          = (float)($_POST['precio'] ?? 0);
    $hora_apertura   = $_POST['hora_apertura'] ?: null;
    $hora_cierre     = $_POST['hora_cierre'] ?: null;
    $duracion_turno  = (int)($_POST['duracion_turno'] ?? 60);
    $activa          = isset($_POST['activa']) ? (int)$_POST['activa'] : 1;

    $sql = "
        INSERT INTO canchas (
            proveedor_id, nombre, ubicacion, tipo, capacidad, precio,
            hora_apertura, hora_cierre, duracion_turno, activa
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssidsiiiii",
        $proveedor_id,
        $nombre,
        $ubicacion,
        $tipo,
        $capacidad,
        $precio,
        $hora_apertura,
        $hora_cierre,
        $duracion_turno,
        $activa
    );
    $stmt->execute();
    $stmt->close();

    header("Location: canchas.php");
    exit();
}

if ($action === 'edit') {
    $cancha_id       = (int)($_POST['cancha_id'] ?? 0);
    $nombre          = trim($_POST['nombre'] ?? '');
    $ubicacion       = trim($_POST['ubicacion'] ?? '');
    $tipo            = trim($_POST['tipo'] ?? '');
    $capacidad       = $_POST['capacidad'] !== '' ? (int)$_POST['capacidad'] : null;
    $precio          = (float)($_POST['precio'] ?? 0);
    $hora_apertura   = $_POST['hora_apertura'] ?: null;
    $hora_cierre     = $_POST['hora_cierre'] ?: null;
    $duracion_turno  = (int)($_POST['duracion_turno'] ?? 60);
    $activa          = isset($_POST['activa']) ? (int)$_POST['activa'] : 1;

    $sql = "
        UPDATE canchas
        SET nombre = ?, ubicacion = ?, tipo = ?, capacidad = ?, precio = ?,
            hora_apertura = ?, hora_cierre = ?, duracion_turno = ?, activa = ?
        WHERE cancha_id = ? AND proveedor_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssidsiihii",
        $nombre,
        $ubicacion,
        $tipo,
        $capacidad,
        $precio,
        $hora_apertura,
        $hora_cierre,
        $duracion_turno,
        $activa,
        $cancha_id,
        $proveedor_id
    );
    $stmt->execute();
    $stmt->close();

    header("Location: canchas.php");
    exit();
}

if ($action === 'toggle') {
    $cancha_id = (int)($_POST['cancha_id'] ?? 0);
    $activa    = (int)($_POST['activa'] ?? 0);

    $sql = "UPDATE canchas SET activa = ? WHERE cancha_id = ? AND proveedor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $activa, $cancha_id, $proveedor_id);
    $stmt->execute();
    $stmt->close();

    header("Location: canchas.php");
    exit();
}

/*
// OPCIONAL: delete
if ($action === 'delete') {
    $cancha_id = (int)($_POST['cancha_id'] ?? 0);

    $sql = "DELETE FROM canchas WHERE cancha_id = ? AND proveedor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cancha_id, $proveedor_id);
    $stmt->execute();
    $stmt->close();

    header("Location: canchas.php");
    exit();
}
*/

header("Location: canchas.php");
exit();
