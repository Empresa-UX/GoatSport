<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

function detectarGanador($resultado, $j1, $j2) {
    if (!$resultado) return null;

    $res = strtolower($resultado);

    if (strpos($res, "j1") !== false) return $j1;
    if (strpos($res, "j2") !== false) return $j2;

    return null;
}

if ($action === 'add') {

    $torneo_id   = $_POST['torneo_id'];
    $j1          = $_POST['jugador1_id'];
    $j2          = $_POST['jugador2_id'];
    $fecha       = $_POST['fecha'];
    $resultado   = $_POST['resultado'];
    $reserva_id  = $_POST['reserva_id'] ?: null;

    $ganador = detectarGanador($resultado, $j1, $j2);

    $stmt = $conn->prepare("
        INSERT INTO partidos (torneo_id, jugador1_id, jugador2_id, fecha, resultado, ganador_id, reserva_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiissii", $torneo_id, $j1, $j2, $fecha, $resultado, $ganador, $reserva_id);
    $stmt->execute();
    $stmt->close();

    header('Location: partidos.php');
    exit;
}

if ($action === 'edit') {

    $partido_id  = $_POST['partido_id'];
    $torneo_id   = $_POST['torneo_id'];
    $j1          = $_POST['jugador1_id'];
    $j2          = $_POST['jugador2_id'];
    $fecha       = $_POST['fecha'];
    $resultado   = $_POST['resultado'];
    $reserva_id  = $_POST['reserva_id'] ?: null;

    $ganador = detectarGanador($resultado, $j1, $j2);

    $stmt = $conn->prepare("
        UPDATE partidos 
        SET torneo_id=?, jugador1_id=?, jugador2_id=?, fecha=?, resultado=?, ganador_id=?, reserva_id=?
        WHERE partido_id=?
    ");
    $stmt->bind_param("iiissiii",
        $torneo_id, $j1, $j2, $fecha, $resultado, $ganador, $reserva_id, $partido_id
    );
    $stmt->execute();
    $stmt->close();

    header('Location: partidos.php');
    exit;
}

if ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM partidos WHERE partido_id=?");
    $stmt->bind_param("i", $_POST['partido_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: partidos.php');
    exit;
}
