<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $proveedor_id   = (int)($_POST['proveedor_id'] ?? 0);
    $nombre         = $_POST['nombre'] ?? '';
    $descripcion    = $_POST['descripcion'] ?? '';
    $ubicacion      = $_POST['ubicacion'] ?? '';
    $tipo           = $_POST['tipo'] ?? '';
    $capacidad      = $_POST['capacidad'] !== '' ? (int)$_POST['capacidad'] : null;
    $precio         = (float)($_POST['precio'] ?? 0);
    $hora_apertura  = $_POST['hora_apertura'] ?: null;
    $hora_cierre    = $_POST['hora_cierre'] ?: null;
    $duracion       = (int)($_POST['duracion_turno'] ?? 60);
    $activa         = isset($_POST['activa']) ? 1 : 0;

    $sql = "
        INSERT INTO canchas 
        (proveedor_id, nombre, descripcion, ubicacion, tipo, capacidad, precio, hora_apertura, hora_cierre, duracion_turno, activa)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssidsiii",
        $proveedor_id,
        $nombre,
        $descripcion,
        $ubicacion,
        $tipo,
        $capacidad,
        $precio,
        $hora_apertura,
        $hora_cierre,
        $duracion,
        $activa
    );
    $stmt->execute();
    $stmt->close();

    header('Location: canchas.php');
    exit;
}

if ($action === 'edit') {
    $cancha_id      = (int)($_POST['cancha_id'] ?? 0);
    $proveedor_id   = (int)($_POST['proveedor_id'] ?? 0);
    $nombre         = $_POST['nombre'] ?? '';
    $descripcion    = $_POST['descripcion'] ?? '';
    $ubicacion      = $_POST['ubicacion'] ?? '';
    $tipo           = $_POST['tipo'] ?? '';
    $capacidad      = $_POST['capacidad'] !== '' ? (int)$_POST['capacidad'] : null;
    $precio         = (float)($_POST['precio'] ?? 0);
    $hora_apertura  = $_POST['hora_apertura'] ?: null;
    $hora_cierre    = $_POST['hora_cierre'] ?: null;
    $duracion       = (int)($_POST['duracion_turno'] ?? 60);
    $activa         = isset($_POST['activa']) ? 1 : 0;

    if ($cancha_id <= 0) {
        header('Location: canchas.php');
        exit;
    }

    $sql = "
        UPDATE canchas
        SET proveedor_id = ?,
            nombre       = ?,
            descripcion  = ?,
            ubicacion    = ?,
            tipo         = ?,
            capacidad    = ?,
            precio       = ?,
            hora_apertura= ?,
            hora_cierre  = ?,
            duracion_turno = ?,
            activa       = ?
        WHERE cancha_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssidsiiii",
        $proveedor_id,
        $nombre,
        $descripcion,
        $ubicacion,
        $tipo,
        $capacidad,
        $precio,
        $hora_apertura,
        $hora_cierre,
        $duracion,
        $activa,
        $cancha_id
    );
    $stmt->execute();
    $stmt->close();

    header('Location: canchas.php');
    exit;
}

if ($action === 'delete') {
    $cancha_id = (int)($_POST['cancha_id'] ?? 0);

    if ($cancha_id > 0) {
        $stmt = $conn->prepare("DELETE FROM canchas WHERE cancha_id = ?");
        $stmt->bind_param("i", $cancha_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: canchas.php');
    exit;
}

if ($action === 'toggle') {
    $cancha_id = (int)($_POST['cancha_id'] ?? 0);

    if ($cancha_id > 0) {
        $sql = "UPDATE canchas SET activa = 1 - activa WHERE cancha_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cancha_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: canchas.php');
    exit;
}

if ($_POST['action'] == 'aprobar') {
    $id = intval($_POST['cancha_id']);
    $conn->query("UPDATE canchas SET estado='aprobado' WHERE cancha_id=$id");
    header("Location: ../canchasDisponibles/canchasDisponibles.php");
    exit;
}

if ($_POST['action'] == 'denegar') {
    $id = intval($_POST['cancha_id']);
    $conn->query("UPDATE canchas SET estado='denegado' WHERE cancha_id=$id");
    header("Location: ../canchasDisponibles/canchasDisponibles.php");
    exit;
}

// fallback
header('Location: canchas.php');
exit;
