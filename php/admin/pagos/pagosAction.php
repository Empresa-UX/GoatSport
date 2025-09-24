<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if($action == 'add'){
    $stmt = $conn->prepare("INSERT INTO pagos (reserva_id, jugador_id, monto, estado, fecha_pago) VALUES (?,?,?,?,?)");
    $stmt->bind_param("iidss", $_POST['reserva_id'], $_POST['jugador_id'], $_POST['monto'], $_POST['estado'], $_POST['fecha_pago']);
    $stmt->execute();
    $stmt->close();
    header('Location: pagos.php');
}

if($action == 'edit'){
    $stmt = $conn->prepare("UPDATE pagos SET reserva_id=?, jugador_id=?, monto=?, estado=?, fecha_pago=? WHERE pago_id=?");
    $stmt->bind_param("iidsdi", $_POST['reserva_id'], $_POST['jugador_id'], $_POST['monto'], $_POST['estado'], $_POST['fecha_pago'], $_POST['pago_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: pagos.php');
}


if($action == 'delete'){
    $stmt = $conn->prepare("DELETE FROM pagos WHERE pago_id=?");
    $stmt->bind_param("i", $_POST['pago_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: pagos.php');
}
?>
