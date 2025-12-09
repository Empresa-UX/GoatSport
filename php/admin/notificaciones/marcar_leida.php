<?php
include './../../config.php';

if (!isset($_POST['id'])) {
    exit("ID inválido");
}

$id = intval($_POST['id']);

// Obtener estado actual
$res = $conn->query("SELECT leida FROM notificaciones WHERE notificacion_id = $id");
$row = $res->fetch_assoc();

$nuevoEstado = $row['leida'] == 0 ? 1 : 0;

// Actualizar
$stmt = $conn->prepare("UPDATE notificaciones SET leida = ? WHERE notificacion_id = ?");
$stmt->bind_param("ii", $nuevoEstado, $id);
$stmt->execute();

$stmt->close();

// Redirigir a la misma página de notificaciones
header("Location: notificaciones.php");  
exit();
