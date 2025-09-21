<?php
include './../../config.php';

$action = $_REQUEST['action'] ?? '';

if($action == 'add'){
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol, puntos) VALUES (?,?,?,?,?)");
    $rol = 'cliente';
    $stmt->bind_param("ssssi", $_POST['nombre'], $_POST['email'], $_POST['contrasenia'], $rol, $_POST['puntos']);
    $stmt->execute();
    $stmt->close();
    header('Location: usuarios.php');
}

if($action == 'edit'){
    $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=?, contrasenia=?, puntos=? WHERE user_id=?");
    $stmt->bind_param("sssii", $_POST['nombre'], $_POST['email'], $_POST['contrasenia'], $_POST['puntos'], $_POST['user_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: usuarios.php');
}

if($action == 'delete'){
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE user_id=?");
    $stmt->bind_param("i", $_POST['user_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: usuarios.php');
}

if($action == 'get'){
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM usuarios WHERE user_id=$id");
    echo json_encode($result->fetch_assoc());
}
?>
