<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if($action == 'add'){
    $stmt = $conn->prepare("INSERT INTO partidos (torneo_id, jugador1_id, jugador2_id, fecha, resultado) VALUES (?,?,?,?,?)");
    $stmt->bind_param("iiiss", $_POST['torneo_id'], $_POST['jugador1_id'], $_POST['jugador2_id'], $_POST['fecha'], $_POST['resultado']);
    $stmt->execute();
    $stmt->close();
    header('Location: partidos.php');
}

if($action == 'edit'){
    $stmt = $conn->prepare("UPDATE partidos SET torneo_id=?, jugador1_id=?, jugador2_id=?, fecha=?, resultado=? WHERE partido_id=?");
    $stmt->bind_param("iiissi", $_POST['torneo_id'], $_POST['jugador1_id'], $_POST['jugador2_id'], $_POST['fecha'], $_POST['resultado'], $_POST['partido_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: partidos.php');
}

if($action == 'delete'){
    $stmt = $conn->prepare("DELETE FROM partidos WHERE partido_id=?");
    $stmt->bind_param("i", $_POST['partido_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: partidos.php');
}
?>
