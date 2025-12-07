<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if ($action == 'add') {
    $nombre      = $_POST['nombre'] ?? '';
    $email       = $_POST['email'] ?? '';
    $contrasenia = $_POST['contrasenia'] ?? '';
    $rol         = 'proveedor';

    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $nombre, $email, $contrasenia, $rol);
    $stmt->execute();
    $stmt->close();

    header('Location: proveedores.php');
    exit;
}

if ($action == 'edit') {
    $user_id     = (int)($_POST['user_id'] ?? 0);
    $nombre      = $_POST['nombre'] ?? '';
    $email       = $_POST['email'] ?? '';
    $contrasenia = $_POST['contrasenia'] ?? '';

    if ($user_id <= 0) {
        header('Location: proveedores.php');
        exit;
    }

    if ($contrasenia === '') {
        // No cambiar contraseña
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE user_id = ? AND rol = 'proveedor'");
        $stmt->bind_param("ssi", $nombre, $email, $user_id);
    } else {
        // Actualizar también contraseña
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, contrasenia = ? WHERE user_id = ? AND rol = 'proveedor'");
        $stmt->bind_param("sssi", $nombre, $email, $contrasenia, $user_id);
    }

    $stmt->execute();
    $stmt->close();

    header('Location: proveedores.php');
    exit;
}

if ($action == 'delete') {
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        // Solo eliminamos si realmente es proveedor
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE user_id = ? AND rol = 'proveedor'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: proveedores.php');
    exit;
}

header('Location: proveedores.php');
exit;
