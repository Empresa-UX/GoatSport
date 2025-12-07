<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

function normalizarFechaHora(?string $valor): string {
    if (!$valor) return '';
    // viene como Y-m-dTH:i desde datetime-local
    $ts = strtotime($valor);
    if (!$ts) return '';
    return date('Y-m-d H:i:s', $ts);
}

if ($action === 'add') {
    $reserva_id        = (int)($_POST['reserva_id'] ?? 0);
    $jugador_id        = (int)($_POST['jugador_id'] ?? 0);
    $monto             = (float)($_POST['monto'] ?? 0);
    $metodo            = $_POST['metodo'] ?? 'club';
    $referencia        = $_POST['referencia_gateway'] ?? '';
    $detalle           = $_POST['detalle'] ?? '';
    $estado            = $_POST['estado'] ?? 'pendiente';
    $fecha_pago_in     = $_POST['fecha_pago'] ?? '';
    $fecha_pago_db     = normalizarFechaHora($fecha_pago_in);

    $sql = "
        INSERT INTO pagos 
            (reserva_id, jugador_id, monto, metodo, referencia_gateway, detalle, estado, fecha_pago)
        VALUES 
            (?,?,?,?,?,?,?, NULLIF(?, ''))
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iidsssss",
        $reserva_id,
        $jugador_id,
        $monto,
        $metodo,
        $referencia,
        $detalle,
        $estado,
        $fecha_pago_db
    );
    $stmt->execute();
    $stmt->close();

    header('Location: pagos.php');
    exit;
}

if ($action === 'edit') {
    $pago_id           = (int)($_POST['pago_id'] ?? 0);
    $reserva_id        = (int)($_POST['reserva_id'] ?? 0);
    $jugador_id        = (int)($_POST['jugador_id'] ?? 0);
    $monto             = (float)($_POST['monto'] ?? 0);
    $metodo            = $_POST['metodo'] ?? 'club';
    $referencia        = $_POST['referencia_gateway'] ?? '';
    $detalle           = $_POST['detalle'] ?? '';
    $estado            = $_POST['estado'] ?? 'pendiente';
    $fecha_pago_in     = $_POST['fecha_pago'] ?? '';
    $fecha_pago_db     = normalizarFechaHora($fecha_pago_in);

    if ($pago_id <= 0) {
        header('Location: pagos.php');
        exit;
    }

    $sql = "
        UPDATE pagos 
        SET 
            reserva_id = ?,
            jugador_id = ?,
            monto = ?,
            metodo = ?,
            referencia_gateway = ?,
            detalle = ?,
            estado = ?,
            fecha_pago = NULLIF(?, '')
        WHERE pago_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iidsssssi",
        $reserva_id,
        $jugador_id,
        $monto,
        $metodo,
        $referencia,
        $detalle,
        $estado,
        $fecha_pago_db,
        $pago_id
    );
    $stmt->execute();
    $stmt->close();

    header('Location: pagos.php');
    exit;
}

if ($action === 'delete') {
    $pago_id = (int)($_POST['pago_id'] ?? 0);
    if ($pago_id > 0) {
        $stmt = $conn->prepare("DELETE FROM pagos WHERE pago_id = ?");
        $stmt->bind_param("i", $pago_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: pagos.php');
    exit;
}

// fallback
header('Location: pagos.php');
exit;
