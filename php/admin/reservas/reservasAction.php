<?php
include './../../config.php';

$action = $_REQUEST['action'] ?? '';

if($action == 'add'){
    $stmt = $conn->prepare("INSERT INTO reservas (cancha_id, creador_id, fecha, hora_inicio, hora_fin, estado) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iissss", $_POST['cancha_id'], $_POST['creador_id'], $_POST['fecha'], $_POST['hora_inicio'], $_POST['hora_fin'], $_POST['estado']);
    $stmt->execute();
    $stmt->close();
    header('Location: reservas.php');
}

if($action == 'edit'){
    $stmt = $conn->prepare("UPDATE reservas SET cancha_id=?, creador_id=?, fecha=?, hora_inicio=?, hora_fin=?, estado=? WHERE reserva_id=?");
    $stmt->bind_param("iissssi", $_POST['cancha_id'], $_POST['creador_id'], $_POST['fecha'], $_POST['hora_inicio'], $_POST['hora_fin'], $_POST['estado'], $_POST['reserva_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: reservas.php');
}

if($action == 'delete'){
    $stmt = $conn->prepare("DELETE FROM reservas WHERE reserva_id=?");
    $stmt->bind_param("i", $_POST['reserva_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: reservas.php');
}

if($action == 'get'){
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM reservas WHERE reserva_id=$id");
    echo json_encode($result->fetch_assoc());
}
?>
