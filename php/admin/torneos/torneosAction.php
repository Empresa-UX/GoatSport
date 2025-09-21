<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if($action == 'add'){
    $stmt = $conn->prepare("INSERT INTO torneos (nombre, creador_id, fecha_inicio, fecha_fin, estado) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sisss", $_POST['nombre'], $_POST['creador_id'], $_POST['fecha_inicio'], $_POST['fecha_fin'], $_POST['estado']);
    $stmt->execute();
    $stmt->close();
    header('Location: torneos.php');
}

if($action == 'edit'){
    $stmt = $conn->prepare("UPDATE torneos SET nombre=?, creador_id=?, fecha_inicio=?, fecha_fin=?, estado=? WHERE torneo_id=?");
    $stmt->bind_param("sisssi", $_POST['nombre'], $_POST['creador_id'], $_POST['fecha_inicio'], $_POST['fecha_fin'], $_POST['estado'], $_POST['torneo_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: torneos.php');
}

if($action == 'delete'){
    $stmt = $conn->prepare("DELETE FROM torneos WHERE torneo_id=?");
    $stmt->bind_param("i", $_POST['torneo_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: torneos.php');
}
?>
