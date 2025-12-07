<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {

    $usuario_id = (int)$_POST['usuario_id'];
    $puntos     = (int)$_POST['puntos'];
    $victorias  = (int)$_POST['victorias'];
    $derrotas   = (int)$_POST['derrotas'];
    $partidos   = $victorias + $derrotas;

    $stmt = $conn->prepare("
        INSERT INTO ranking (usuario_id, puntos, partidos, victorias, derrotas)
        VALUES (?,?,?,?,?)
    ");
    $stmt->bind_param("iiiii", $usuario_id, $puntos, $partidos, $victorias, $derrotas);
    $stmt->execute();
    $stmt->close();

    header('Location: ranking.php');
    exit();
}

if ($action === 'edit') {

    $ranking_id = (int)$_POST['ranking_id'];
    $usuario_id = (int)$_POST['usuario_id'];
    $puntos     = (int)$_POST['puntos'];
    $victorias  = (int)$_POST['victorias'];
    $derrotas   = (int)$_POST['derrotas'];
    $partidos   = $victorias + $derrotas;

    $stmt = $conn->prepare("
        UPDATE ranking
        SET usuario_id = ?, puntos = ?, partidos = ?, victorias = ?, derrotas = ?
        WHERE ranking_id = ?
    ");
    $stmt->bind_param("iiiiii", $usuario_id, $puntos, $partidos, $victorias, $derrotas, $ranking_id);
    $stmt->execute();
    $stmt->close();

    header('Location: ranking.php');
    exit();
}

if ($action === 'delete') {

    $ranking_id = (int)$_POST['ranking_id'];

    $stmt = $conn->prepare("DELETE FROM ranking WHERE ranking_id = ?");
    $stmt->bind_param("i", $ranking_id);
    $stmt->execute();
    $stmt->close();

    header('Location: ranking.php');
    exit();
}

// fallback
header('Location: ranking.php');
exit();
