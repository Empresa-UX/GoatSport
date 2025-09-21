<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if($action == 'add'){
    $stmt = $conn->prepare("INSERT INTO ranking (usuario_id, puntos, partidos, victorias) VALUES (?,?,?,?)");
    $stmt->bind_param("iiii", $_POST['usuario_id'], $_POST['puntos'], $_POST['partidos'], $_POST['victorias']);
    $stmt->execute();
    $stmt->close();
    header('Location: ranking.php');
}

if($action == 'edit'){
    $stmt = $conn->prepare("UPDATE ranking SET usuario_id=?, puntos=?, partidos=?, victorias=? WHERE ranking_id=?");
    $stmt->bind_param("iiiii", $_POST['usuario_id'], $_POST['puntos'], $_POST['partidos'], $_POST['victorias'], $_POST['ranking_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: ranking.php');
}

if($action == 'delete'){
    $stmt = $conn->prepare("DELETE FROM ranking WHERE ranking_id=?");
    $stmt->bind_param("i", $_POST['ranking_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: ranking.php');
}
?>
