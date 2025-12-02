<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    
    $sql = "INSERT INTO eventos_especiales 
        (cancha_id, proveedor_id, titulo, descripcion, fecha_inicio, fecha_fin, tipo)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iisssss",
        $_POST['cancha_id'],
        $proveedor_id,
        $_POST['titulo'],
        $_POST['descripcion'],
        $_POST['fecha_inicio'],
        $_POST['fecha_fin'],
        $_POST['tipo']
    );
    $stmt->execute();
    $stmt->close();

} elseif ($action === 'edit') {

    $sql = "UPDATE eventos_especiales 
            SET cancha_id=?, titulo=?, descripcion=?, fecha_inicio=?, fecha_fin=?, tipo=?
            WHERE evento_id=? AND proveedor_id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssssii",
        $_POST['cancha_id'],
        $_POST['titulo'],
        $_POST['descripcion'],
        $_POST['fecha_inicio'],
        $_POST['fecha_fin'],
        $_POST['tipo'],
        $_POST['evento_id'],
        $proveedor_id
    );
    $stmt->execute();
    $stmt->close();

} elseif ($action === 'delete') {

    $sql = "DELETE FROM eventos_especiales 
            WHERE evento_id=? AND proveedor_id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $_POST['evento_id'], $proveedor_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: eventos.php");
exit();
