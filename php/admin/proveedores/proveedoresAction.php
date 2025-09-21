<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if($action == 'add'){
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol) VALUES (?,?,?,?)");
    $rol = 'proveedor';
    $stmt->bind_param("ssss", $_POST['nombre'], $_POST['email'], $_POST['contrasenia'], $rol);
    $stmt->execute();
    $stmt->close();
    header('Location: proveedores.php');
    exit;
}

if($action == 'edit'){
    $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=?, contrasenia=? WHERE user_id=?");
    $stmt->bind_param("sssi", $_POST['nombre'], $_POST['email'], $_POST['contrasenia'], $_POST['user_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: proveedores.php');
    exit;
}

if($action == 'delete'){
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE user_id=?");
    $stmt->bind_param("i", $_POST['user_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: proveedores.php');
    exit;
}
?>
