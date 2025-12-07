<?php
include './../../config.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'add') {
    $nombre      = $_POST['nombre']      ?? '';
    $email       = $_POST['email']       ?? '';
    $contrasenia = $_POST['contrasenia'] ?? '';
    $puntos      = (int)($_POST['puntos'] ?? 0);
    $rol         = 'cliente';

    $sql = "INSERT INTO usuarios (nombre, email, contrasenia, rol, puntos)
            VALUES (?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $email, $contrasenia, $rol, $puntos);
    $stmt->execute();
    $stmt->close();

    header('Location: usuarios.php');
    exit;
}

if ($action === 'edit') {
    $user_id     = (int)($_POST['user_id'] ?? 0);
    $nombre      = $_POST['nombre']      ?? '';
    $email       = $_POST['email']       ?? '';
    $contrasenia = $_POST['contrasenia'] ?? '';
    $puntos      = (int)($_POST['puntos'] ?? 0);

    if ($user_id <= 0) {
        header('Location: usuarios.php');
        exit;
    }

    $sql = "UPDATE usuarios 
            SET nombre = ?, email = ?, contrasenia = ?, puntos = ?
            WHERE user_id = ? AND rol = 'cliente'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $nombre, $email, $contrasenia, $puntos, $user_id);
    $stmt->execute();
    $stmt->close();

    header('Location: usuarios.php');
    exit;
}

if ($action === 'delete') {
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE user_id = ? AND rol = 'cliente'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: usuarios.php');
    exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $data = null;
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE user_id = ? AND rol = 'cliente'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        $stmt->close();
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// fallback
header('Location: usuarios.php');
exit;
