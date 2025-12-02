<?php
// php/proveedor/configuracion/configuracionAction.php

session_start();
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

if ($action !== 'update_profile') {
    header("Location: configuracion.php");
    exit();
}

$nombre       = trim($_POST['nombre'] ?? '');
$email        = trim($_POST['email'] ?? '');
$nombre_club  = trim($_POST['nombre_club'] ?? '');
$telefono     = trim($_POST['telefono'] ?? '');
$direccion    = trim($_POST['direccion'] ?? '');
$ciudad       = trim($_POST['ciudad'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');

// Validaciones básicas
if ($nombre === '' || $email === '') {
    header("Location: configuracion.php?err=" . urlencode("Nombre y email son obligatorios."));
    exit();
}

// 1) Actualizar datos básicos del usuario
$sql = "UPDATE usuarios SET nombre = ?, email = ? WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $nombre, $email, $proveedor_id);
$stmt->execute();
$stmt->close();

// 2) Upsert en proveedores_detalle (INSERT o UPDATE según exista)
$sql = "
    INSERT INTO proveedores_detalle 
        (proveedor_id, nombre_club, telefono, direccion, ciudad, descripcion)
    VALUES
        (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        nombre_club = VALUES(nombre_club),
        telefono    = VALUES(telefono),
        direccion   = VALUES(direccion),
        ciudad      = VALUES(ciudad),
        descripcion = VALUES(descripcion)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isssss",
    $proveedor_id,
    $nombre_club,
    $telefono,
    $direccion,
    $ciudad,
    $descripcion
);
$stmt->execute();
$stmt->close();

header("Location: configuracion.php?ok=" . urlencode("Perfil actualizado correctamente."));
exit();
