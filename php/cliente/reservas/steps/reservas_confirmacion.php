<?php
require __DIR__ . '/../../../config.php';
include './../../includes/header.php';
require __DIR__ . '/../../../../lib/util.php';
ensure_session();

$reserva = $_SESSION['reserva'] ?? [];
$canchaId    = $reserva['cancha_id'] ?? null;
$fecha       = $reserva['fecha'] ?? null;
$horaInicio  = $reserva['hora_inicio'] ?? null;
$horaFin     = $reserva['hora_fin'] ?? null;
$metodo      = $_POST['metodo'] ?? null;

if (!$canchaId || !$fecha || !$horaInicio) {
    echo "<div class='page-wrap'><p>Error: faltan datos de la reserva (cancha, fecha u hora).</p></div>";
    include './../includes/footer.php'; exit;
}
function parseHora(string $hora = null): ?DateTime {
    if (!$hora) return null;
    foreach (['H:i:s', 'H:i'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, trim($hora));
        if ($dt) return $dt;
    }
    try { return new DateTime($hora); } catch (Exception) { return null; }
}
function formatHora(DateTime $dt): string { return $dt->format('H:i:s'); }

$dtInicio = parseHora($horaInicio);
if (!$dtInicio) { echo "<div class='page-wrap'><p>Error: hora inválida.</p></div>"; include './../includes/footer.php'; exit; }
$dtFin = $horaFin ? parseHora($horaFin) : (clone $dtInicio)->add(new DateInterval('PT90M'));
$hora_inicio_sql = formatHora($dtInicio);
$hora_fin_sql    = formatHora($dtFin);

$canchaNombre = "Cancha #$canchaId";
$canchaPrecio = null;
if ($stmt = $conn->prepare("SELECT nombre, precio FROM canchas WHERE cancha_id = ?")) {
    $stmt->bind_param("i", $canchaId);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $canchaNombre = $row['nombre'];
        $canchaPrecio = (float)$row['precio'];
    }
    $stmt->close();
}

$usuarioId = intval($_SESSION['usuario_id'] ?? 0);
if ($usuarioId <= 0) { echo "<div class='page-wrap'><p>Error: sesión de usuario inválida.</p></div>"; include './../includes/footer.php'; exit; }

$estadoReserva = ($metodo === 'efectivo') ? 'pendiente' : 'confirmada';
$insertedId = null; $errorMsg = null;

try {
    $conn->begin_transaction();
    $chk = $conn->prepare("
        SELECT COUNT(*) AS cnt 
        FROM reservas 
        WHERE cancha_id = ? AND fecha = ? 
          AND estado != 'cancelada' 
          AND NOT (hora_fin <= ? OR hora_inicio >= ?)
    ");
    $chk->bind_param("isss", $canchaId, $fecha, $hora_inicio_sql, $hora_fin_sql);
    $chk->execute();
    $conflictos = intval($chk->get_result()->fetch_assoc()['cnt'] ?? 0);
    $chk->close();

    if ($conflictos > 0) {
        $conn->rollback();
        $errorMsg = "Lo sentimos, el horario seleccionado ya fue reservado por otro usuario.";
    } else {
        $ins = $conn->prepare("
            INSERT INTO reservas (cancha_id, creador_id, fecha, hora_inicio, hora_fin, estado) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ins->bind_param("iissss", $canchaId, $usuarioId, $fecha, $hora_inicio_sql, $hora_fin_sql, $estadoReserva);
        $ins->execute();
        $insertedId = $ins->insert_id;
        $ins->close();

        // === Insertar en tu tabla `pagos` ===
        $estadoPago = ($metodo === 'efectivo') ? 'pendiente' : 'pagado';
        $montoPago  = $canchaPrecio ?? 0.0;

        if ($estadoPago === 'pagado') {
            $stmtPago = $conn->prepare("
                INSERT INTO pagos (reserva_id, jugador_id, monto, estado, fecha_pago)
                VALUES (?, ?, ?, 'pagado', NOW())
            ");
            $stmtPago->bind_param("iid", $insertedId, $usuarioId, $montoPago);
            $stmtPago->execute(); $stmtPago->close();
        } else {
            $stmtPago = $conn->prepare("
                INSERT INTO pagos (reserva_id, jugador_id, monto, estado, fecha_pago)
                VALUES (?, ?, ?, 'pendiente', NULL)
            ");
            $stmtPago->bind_param("iid", $insertedId, $usuarioId, $montoPago);
            $stmtPago->execute(); $stmtPago->close();
        }

        unset($_SESSION['pago']);
        $conn->commit();
        unset($_SESSION['reserva']);
    }
} catch (Exception $e) {
    $conn->rollback();
    $errorMsg = "Error interno: " . $e->getMessage();
}
?>
<div class="page-wrap" style="max-width:900px; margin:30px auto;">
    <div class="flow-header">
        <h1>Flujo de Reserva</h1>
        <div class="steps-row">
            <div class="step"><span class="circle">1</span><span class="label">Selección del horario</span></div>
            <div class="step"><span class="circle">2</span><span class="label">Abono</span></div>
            <div class="step active"><span class="circle">3</span><span class="label">Confirmación</span></div>
        </div>
    </div>

    <div class="confirmation-container">
        <?php if ($errorMsg): ?>
            <div class="confirmation-title error">No fue posible completar la reserva</div>
            <div class="summary"><div><strong>Motivo:</strong> <?= h($errorMsg) ?></div></div>
            <div style="text-align:center; margin-top:20px;">
                <a href="reservas.php?cancha=<?= (int)$canchaId ?>" class="btn back">Volver a elegir horario</a>
            </div>
        <?php else: ?>
            <div class="confirmation-title">Reserva confirmada</div>
            <div class="summary">
                <div><strong>ID reserva:</strong> <?= (int)$insertedId ?></div>
                <div><strong>Cancha:</strong> <?= h($canchaNombre) ?></div>
                <div><strong>Fecha:</strong> <?= h($fecha) ?></div>
                <div><strong>Hora:</strong> <?= h($hora_inicio_sql . ' - ' . $hora_fin_sql) ?></div>
                <div><strong>Método de pago:</strong> <?= h(ucfirst($metodo ?? 'No elegido')) ?></div>
                <div><strong>Estado de la reserva:</strong> <?= h(ucfirst($estadoReserva)) ?></div>
                <?php if ($canchaPrecio !== null): ?>
                    <div><strong>Precio:</strong> $ <?= number_format((float)$canchaPrecio, 2, ',', '.') ?></div>
                <?php endif; ?>
            </div>
            <div style="display:flex; gap:10px; justify-content:space-between; margin-top:20px;">
                <a href="/php/cliente/historial_estadisticas/historial_estadisticas.php" class="btn back">Ver mis reservas</a>
                <a href="/php/cliente/home_cliente.php" class="btn confirm">Volver al inicio</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include './../../includes/footer.php'; ?>
