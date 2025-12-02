<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

if ($action === 'edit') {
    $pago_id    = (int)($_POST['pago_id'] ?? 0);
    $estado     = $_POST['estado'] ?? 'pendiente';
    $fecha_pago = $_POST['fecha_pago'] ?? null;

    // Normalizamos fecha: si estado no es pagado, podemos dejarla NULL
    if ($estado !== 'pagado') {
        $fecha_pago = null;
    }

    // UPDATE solo si el pago pertenece a este proveedor
    $sql = "
        UPDATE pagos p
        INNER JOIN reservas r ON p.reserva_id = r.reserva_id
        INNERJOIN canchas c ON r.cancha_id = c.cancha_id
        SET p.estado = ?, p.fecha_pago = ?
        WHERE p.pago_id = ? AND c.proveedor_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $estado, $fecha_pago, $pago_id, $proveedor_id);
    $stmt->execute();
    $stmt->close();

    header("Location: pagos.php");
    exit();
}

// Para proveedor no permitimos add/delete directos
header("Location: pagos.php");
exit();
