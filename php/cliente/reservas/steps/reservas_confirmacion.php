<?php
/* =========================================================================
 * FILE: php/cliente/reservas/steps/reservas_confirmacion.php
 * ========================================================================= */
declare(strict_types=1);

require __DIR__ . '/../../../config.php';
require __DIR__ . '/../../../../lib/util.php';
include './../../includes/header.php';

function sess_user_id(): int {
  return (int)($_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 0));
}

function metodo_to_enum(string $m): string {
  // DB pagos.metodo enum('mercado_pago','tarjeta','club')
  return match ($m) {
    'mercadopago'  => 'mercado_pago',
    'mercado_pago' => 'mercado_pago',
    'tarjeta'      => 'tarjeta',
    'club'         => 'club',
    'efectivo'     => 'club',
    default        => 'club',
  };
}

$uid = sess_user_id();
if ($uid <= 0) {
  echo "<div class='page-wrap'><p>Error: sesión inválida.</p></div>";
  include './../../includes/footer.php';
  exit;
}

csrf_validate_or_die();

$reserva = $_SESSION['reserva'] ?? [];
$canchaId    = (int)($reserva['cancha_id'] ?? 0);
$fecha       = (string)($reserva['fecha'] ?? '');
$horaInicio  = (string)($reserva['hora_inicio'] ?? '');
$horaFin     = (string)($reserva['hora_fin'] ?? '');
$duracion    = (int)($reserva['duracion'] ?? 0);
$precioFinal = (float)($reserva['precio_final'] ?? 0);
$tipoReserva = (string)($reserva['tipo_reserva'] ?? 'equipo');
$splitPlan   = $reserva['split_plan'] ?? ['enabled' => false];
$metodoPost  = (string)($_POST['metodo'] ?? 'club');

if ($canchaId <= 0 || !$fecha || !$horaInicio || $duracion <= 0) {
  echo "<div class='page-wrap'><p>Error: faltan datos de la reserva (cancha/fecha/hora/duración).</p></div>";
  include './../../includes/footer.php';
  exit;
}

if (!$horaFin) {
  $dt = DateTime::createFromFormat('Y-m-d H:i', $fecha.' '.$horaInicio);
  $horaFin = $dt ? (clone $dt)->modify("+{$duracion} minutes")->format('H:i:s') : '00:00:00';
}

$horaIniSql = preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaInicio) ? $horaInicio : ($horaInicio.':00');
$horaFinSql = preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaFin) ? $horaFin : ($horaFin.':00');

$canchaNombre = "Cancha #$canchaId";
if ($stmt = $conn->prepare("SELECT nombre FROM canchas WHERE cancha_id = ?")) {
  $stmt->bind_param("i", $canchaId);
  $stmt->execute();
  if ($row = $stmt->get_result()->fetch_assoc()) $canchaNombre = (string)$row['nombre'];
  $stmt->close();
}

/**
 * REGLAS (según lo que pediste):
 * - reserva.estado SIEMPRE 'confirmada' (si no hay conflictos)
 * - pagos.estado SIEMPRE 'pendiente'
 * - pagos.fecha_pago:
 *     - se setea HOY si el pago fue efectivamente realizado (tarjeta approved / mp success / club)
 *     - si no, NULL
 */
$estadoReserva = 'confirmada'; // ✅ SIEMPRE confirmada

$metodoEnum = metodo_to_enum($metodoPost);

// Datos gateway (solo referencia / detalle)
$gateway = $_SESSION['gateway_hint'] ?? [];
$refGateway = isset($gateway['payment_id']) ? (string)$gateway['payment_id'] : null;
$detalle = $gateway ? json_encode($gateway, JSON_UNESCAPED_UNICODE) : null;

// Fecha pago (solo cuando el pago fue "efectuado")
$now = date('Y-m-d H:i:s');
$fechaPago = null;

// Club: consideramos que el pago se efectuó (si NO querés esto, cambiá a: $fechaPago = null;)
if ($metodoEnum === 'club') {
  $fechaPago = $now;
}

// Tarjeta: si MP status approved
if ($metodoEnum === 'tarjeta') {
  $st = (string)($gateway['status'] ?? '');
  if ($st === 'approved') {
    $fechaPago = $now;
  }
}

// MercadoPago: si callback success o si webhook setea $_SESSION['pago']
if ($metodoEnum === 'mercado_pago') {
  $hint = $_SESSION['mp_callback_hint'] ?? null;
  $statusCb = is_array($hint) ? (string)($hint['status'] ?? '') : '';
  if ($statusCb === 'success') {
    $fechaPago = $now;
    if (!empty($hint['payment_id'])) $refGateway = (string)$hint['payment_id'];
    $detalle = json_encode(['mp_callback' => $hint, 'at' => $now], JSON_UNESCAPED_UNICODE);
  }

  $p = $_SESSION['pago'] ?? null;
  if (is_array($p) && (($p['estado'] ?? '') === 'pagado')) {
    $fechaPago = (string)($p['fecha_pago'] ?? $now);
    if (!empty($p['payment_id'])) $refGateway = (string)$p['payment_id'];
    $detalle = json_encode($p, JSON_UNESCAPED_UNICODE);
  }
}

$insertedId = null;
$errorMsg = null;

try {
  $conn->begin_transaction();

  // Chequear conflictos de horario
  $chk = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM reservas
    WHERE cancha_id = ? AND fecha = ?
      AND estado != 'cancelada'
      AND NOT (hora_fin <= ? OR hora_inicio >= ?)
  ");
  $chk->bind_param("isss", $canchaId, $fecha, $horaIniSql, $horaFinSql);
  $chk->execute();
  $conflictos = (int)($chk->get_result()->fetch_assoc()['cnt'] ?? 0);
  $chk->close();

  if ($conflictos > 0) {
    $conn->rollback();
    $errorMsg = "Lo sentimos, el horario seleccionado ya fue reservado por otro usuario.";
  } else {

    // Insert reserva (siempre confirmada)
    $ins = $conn->prepare("
      INSERT INTO reservas (cancha_id, creador_id, fecha, hora_inicio, hora_fin, precio_total, tipo_reserva, estado)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param("iisssdss", $canchaId, $uid, $fecha, $horaIniSql, $horaFinSql, $precioFinal, $tipoReserva, $estadoReserva);
    $ins->execute();
    $insertedId = (int)$ins->insert_id;
    $ins->close();

    // Participación del creador
    if ($stp = $conn->prepare("
      INSERT INTO participaciones (jugador_id, reserva_id, es_creador, estado)
      VALUES (?, ?, 1, 'aceptada')
    ")) {
      $stp->bind_param("ii", $uid, $insertedId);
      $stp->execute();
      $stp->close();
    }

    // Insert pagos
    if (!empty($splitPlan['enabled'])) {
      $creatorAmount = (float)($splitPlan['creator_amount'] ?? $precioFinal);
      $parts = $splitPlan['participants'] ?? [];

      // pago del creador (estado SIEMPRE pendiente, fecha_pago condicional)
      $stPay = $conn->prepare("
        INSERT INTO pagos (reserva_id, jugador_id, monto, metodo, referencia_gateway, detalle, estado, fecha_pago)
        VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)
      ");
      $stPay->bind_param("iidssss", $insertedId, $uid, $creatorAmount, $metodoEnum, $refGateway, $detalle, $fechaPago);
      $stPay->execute();
      $stPay->close();

      // pagos participantes registrados + participaciones + notificaciones
      foreach ($parts as $p) {
        if (($p['mode'] ?? '') !== 'reg') continue;
        $pid = (int)($p['user_id'] ?? 0);
        $amt = (float)($p['amount'] ?? 0);
        if ($pid <= 0 || $amt <= 0) continue;

        // participacion
        if ($stp2 = $conn->prepare("
          INSERT INTO participaciones (jugador_id, reserva_id, es_creador, estado)
          VALUES (?, ?, 0, 'pendiente')
        ")) {
          $stp2->bind_param("ii", $pid, $insertedId);
          $stp2->execute();
          $stp2->close();
        }

        // pago pendiente del participante
        $m = 'club';
        if ($stPay2 = $conn->prepare("
          INSERT INTO pagos (reserva_id, jugador_id, monto, metodo, referencia_gateway, detalle, estado, fecha_pago)
          VALUES (?, ?, ?, ?, NULL, NULL, 'pendiente', NULL)
        ")) {
          $stPay2->bind_param("iids", $insertedId, $pid, $amt, $m);
          $stPay2->execute();
          $stPay2->close();
        }

        // notificación
        $tit = "Pago pendiente de reserva #".$insertedId;
        $msg = "Tenés un pago pendiente de $ ".number_format($amt,2,',','.')." para la reserva #".$insertedId." (".$fecha." ".$horaIniSql." - ".$horaFinSql.").";
        if ($stN = $conn->prepare("
          INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
          VALUES (?, 'pago_pendiente', 'sistema', ?, ?)
        ")) {
          $stN->bind_param("iss", $pid, $tit, $msg);
          $stN->execute();
          $stN->close();
        }
      }

    } else {
      // No divide: un solo pago (estado SIEMPRE pendiente, fecha_pago condicional)
      $monto = $precioFinal;
      $stPay = $conn->prepare("
        INSERT INTO pagos (reserva_id, jugador_id, monto, metodo, referencia_gateway, detalle, estado, fecha_pago)
        VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)
      ");
      $stPay->bind_param("iidssss", $insertedId, $uid, $monto, $metodoEnum, $refGateway, $detalle, $fechaPago);
      $stPay->execute();
      $stPay->close();
    }

    $conn->commit();

    // limpiar sesión de flujo
    unset($_SESSION['reserva'], $_SESSION['gateway_hint'], $_SESSION['mp_callback_hint'], $_SESSION['pago']);
  }

} catch (Throwable $e) {
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
      <div class="confirmation-title">Reserva creada</div>
      <div class="summary">
        <div><strong>ID reserva:</strong> <?= (int)$insertedId ?></div>
        <div><strong>Cancha:</strong> <?= h($canchaNombre) ?></div>
        <div><strong>Fecha:</strong> <?= h($fecha) ?></div>
        <div><strong>Hora:</strong> <?= h(substr($horaIniSql,0,5) . ' - ' . substr($horaFinSql,0,5)) ?></div>
        <div><strong>Método elegido:</strong> <?= h(ucfirst($metodoPost)) ?></div>
        <div><strong>Estado de la reserva:</strong> Confirmada</div>
        <div><strong>Estado del pago:</strong> Pendiente</div>
        <div><strong>Fecha de pago:</strong> <?= $fechaPago ? h($fechaPago) : '-' ?></div>
        <div><strong>Total:</strong> $ <?= number_format($precioFinal, 2, ',', '.') ?></div>
        <div class="muted" style="margin-top:6px;">El pago queda en pendiente hasta verificación del club.</div>
      </div>
      <div style="display:flex; gap:10px; justify-content:space-between; margin-top:20px;">
        <a href="/php/cliente/historial_estadisticas/historial_estadisticas.php" class="btn back">Ver mis reservas</a>
        <a href="/php/cliente/home_cliente.php" class="btn confirm">Volver al inicio</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include './../../includes/footer.php'; ?>
