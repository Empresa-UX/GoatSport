<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $nombre        = $_POST['nombre']        ?? '';
    $creador_id    = (int)($_POST['creador_id'] ?? 0);
    $proveedor_id  = (int)($_POST['proveedor_id'] ?? 0); // 0 = sin asignar
    $fecha_inicio  = $_POST['fecha_inicio']  ?? '';
    $fecha_fin     = $_POST['fecha_fin']     ?? '';
    $estado        = $_POST['estado']        ?? 'abierto';
    $puntos        = (int)($_POST['puntos_ganador'] ?? 0);

    $sql = "
        INSERT INTO torneos
            (nombre, creador_id, proveedor_id, fecha_inicio, fecha_fin, estado, puntos_ganador)
        VALUES
            (?, ?, NULLIF(?,0), ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "siisssi",
        $nombre,
        $creador_id,
        $proveedor_id,
        $fecha_inicio,
        $fecha_fin,
        $estado,
        $puntos
    );
    $stmt->execute();
    $stmt->close();

    header('Location: torneos.php');
    exit;
}

if ($action === 'edit') {
    $torneo_id     = (int)($_POST['torneo_id'] ?? 0);
    $nombre        = $_POST['nombre']        ?? '';
    $creador_id    = (int)($_POST['creador_id'] ?? 0);
    $proveedor_id  = (int)($_POST['proveedor_id'] ?? 0); // 0 = sin asignar
    $fecha_inicio  = $_POST['fecha_inicio']  ?? '';
    $fecha_fin     = $_POST['fecha_fin']     ?? '';
    $estado        = $_POST['estado']        ?? 'abierto';
    $puntos        = (int)($_POST['puntos_ganador'] ?? 0);

    if ($torneo_id <= 0) {
        header('Location: torneos.php');
        exit;
    }

    $sql = "
        UPDATE torneos
        SET
            nombre        = ?,
            creador_id    = ?,
            proveedor_id  = NULLIF(?,0),
            fecha_inicio  = ?,
            fecha_fin     = ?,
            estado        = ?,
            puntos_ganador = ?
        WHERE torneo_id   = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "siisssii",
        $nombre,
        $creador_id,
        $proveedor_id,
        $fecha_inicio,
        $fecha_fin,
        $estado,
        $puntos,
        $torneo_id
    );
    $stmt->execute();
    $stmt->close();

    header('Location: torneos.php');
    exit;
}

if ($action === 'delete') {
    $torneo_id = (int)($_POST['torneo_id'] ?? 0);
    if ($torneo_id > 0) {
        $stmt = $conn->prepare("DELETE FROM torneos WHERE torneo_id = ?");
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: torneos.php');
    exit;
}

// fallback
header('Location: torneos.php');
exit;
