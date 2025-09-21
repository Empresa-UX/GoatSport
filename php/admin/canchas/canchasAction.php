<?php
include './../../config.php';

$action = $_REQUEST['action'] ?? '';

if($action == 'add'){
    $stmt = $conn->prepare("INSERT INTO canchas (nombre, ubicacion, tipo, capacidad, precio) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssii", $_POST['nombre'], $_POST['ubicacion'], $_POST['tipo'], $_POST['capacidad'], $_POST['precio']);
    $stmt->execute();
    $stmt->close();
    header('Location: canchas.php');
}

if($action == 'edit'){
    $stmt = $conn->prepare("UPDATE canchas SET nombre=?, ubicacion=?, tipo=?, capacidad=?, precio=? WHERE cancha_id=?");
    $stmt->bind_param("sssiii", $_POST['nombre'], $_POST['ubicacion'], $_POST['tipo'], $_POST['capacidad'], $_POST['precio'], $_POST['cancha_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: canchas.php');
}

if($action == 'delete'){
    $stmt = $conn->prepare("DELETE FROM canchas WHERE cancha_id=?");
    $stmt->bind_param("i", $_POST['cancha_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: canchas.php');
}

if($action == 'get'){
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM canchas WHERE cancha_id=$id");
    echo json_encode($result->fetch_assoc());
}
?>
