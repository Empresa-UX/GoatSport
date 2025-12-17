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

if ($canchaId <= 0 || !$fecha || !$horaInicio || $duracion <= 0 || $precioFinal <= 0) {
  echo "<div class='page-wrap'><p>Error: faltan datos de la reserva (cancha/fecha/hora/duración/precio).</p></div>";
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
  if ($row = $stmt->get_result()->fetch_assoc()) {
    $canchaNombre = (string)($row['nombre'] ?? $canchaNombre);
  }
  $stmt->close();
}

/* =========================================================
   REGLAS:
   - Reserva SIEMPRE confirmada (si no hay conflictos)
   - Pagos: 1 sola fila (del creador)
     - pagos.monto = TOTAL real de la reserva (precioFinal)
     - pagos.detalle = SOLO nombres separados por coma (si está dividido), sino NULL
   - pagos.estado SIEMPRE 'pendiente'
   - pagos.fecha_pago = HOY si el pago se efectuó (tarjeta/MP/club), sino NULL
   ========================================================= */

$estadoReserva = 'confirmada';
$metodoEnum = metodo_to_enum($metodoPost);

// Total real de la reserva (con promo aplicada) => DB pagos.monto
$montoTotalReserva = round($precioFinal, 2);

// Monto cobrado al jugador (lo que paga "ahora" por el flujo)
$montoCobradoJugador = $montoTotalReserva;
if (!empty($splitPlan['enabled'])) {
  $montoCobradoJugador = round((float)($splitPlan['creator_amount'] ?? $montoTotalReserva), 2);
}

// Datos gateway
$gateway = $_SESSION['gateway_hint'] ?? [];
$refGateway = isset($gateway['payment_id']) ? (string)$gateway['payment_id'] : null;

// Fecha pago (solo cuando se efectuó)
$now = date('Y-m-d H:i:s');
$fechaPago = null;

// Club: lo consideramos "efectuado" en este flujo (si NO querés esto, comentá estas 2 líneas)
if ($metodoEnum === 'club') {
  $fechaPago = $now;
}

// Tarjeta: si gateway status approved
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
    if (is_array($hint) && !empty($hint['payment_id'])) {
      $refGateway = (string)$hint['payment_id'];
    }
  }

  $p = $_SESSION['pago'] ?? null;
  if (is_array($p) && (($p['estado'] ?? '') === 'pagado')) {
    $fechaPago = (string)($p['fecha_pago'] ?? $now);
    if (!empty($p['payment_id'])) $refGateway = (string)$p['payment_id'];
  }
}

/* =========================================================
   DIVIDIDO EN:
   - 2 variables:
     - $divididoEnDb: lo que guardo en BD (solo nombres, coma)
     - $divididoEnUi: lo que muestro en UI (solo nombres, coma)
   ========================================================= */
$divididoEnDb = null;
$divididoEnUi = null;

if (!empty($splitPlan['enabled'])) {
  $nombres = [];

  foreach (($splitPlan['participants'] ?? []) as $p) {

    // Invitados
    if (($p['mode'] ?? '') === 'inv') {
      $full = trim((string)($p['first'] ?? '') . ' ' . (string)($p['last'] ?? ''));
      if ($full !== '') $nombres[] = $full;
      continue;
    }

    // Registrados: buscar nombre real por user_id
    if (($p['mode'] ?? '') === 'reg') {
      $pid = (int)($p['user_id'] ?? 0);
      if ($pid > 0) {
        $nm = '';
        if ($st = $conn->prepare("SELECT nombre FROM usuarios WHERE user_id=? LIMIT 1")) {
          $st->bind_param("i", $pid);
          $st->execute();
          $nm = (string)($st->get_result()->fetch_assoc()['nombre'] ?? '');
          $st->close();
        }
        $nm = trim($nm);
        if ($nm !== '') {
          $nombres[] = $nm;
        }
      }
    }
  }

  $nombres = array_values(array_unique(array_filter(array_map('trim', $nombres))));
  if (!empty($nombres)) {
    $divididoEnDb = implode(', ', $nombres); // ✅ solo nombres
    $divididoEnUi = $divididoEnDb;           // ✅ mismo texto para UI
  }
}

// Lo que va a BD en pagos.detalle (solo nombres si dividido, si no NULL)
$detalle = $divididoEnDb;

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

    // Insert reserva (SIEMPRE confirmada)
    $ins = $conn->prepare("
      INSERT INTO reservas (cancha_id, creador_id, fecha, hora_inicio, hora_fin, precio_total, tipo_reserva, estado)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param("iisssdss", $canchaId, $uid, $fecha, $horaIniSql, $horaFinSql, $precioFinal, $tipoReserva, $estadoReserva);
    $ins->execute();
    $insertedId = (int)$ins->insert_id;
    $ins->close();

    // Participación del creador (aceptada)
    if ($stp = $conn->prepare("
      INSERT INTO participaciones (jugador_id, reserva_id, es_creador, estado)
      VALUES (?, ?, 1, 'aceptada')
    ")) {
      $stp->bind_param("ii", $uid, $insertedId);
      $stp->execute();
      $stp->close();
    }

    // Participaciones de registrados (sin crear pagos)
    if (!empty($splitPlan['enabled'])) {
      $parts = $splitPlan['participants'] ?? [];
      foreach ($parts as $p) {
        if (($p['mode'] ?? '') !== 'reg') continue;
        $pid = (int)($p['user_id'] ?? 0);
        if ($pid <= 0) continue;

        if ($stp2 = $conn->prepare("
          INSERT INTO participaciones (jugador_id, reserva_id, es_creador, estado)
          VALUES (?, ?, 0, 'pendiente')
        ")) {
          $stp2->bind_param("ii", $pid, $insertedId);
          $stp2->execute();
          $stp2->close();
        }
      }
    }

    // Insert 1 SOLO pago (del creador)
    $stPay = $conn->prepare("
      INSERT INTO pagos (reserva_id, jugador_id, monto, metodo, referencia_gateway, detalle, estado, fecha_pago)
      VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)
    ");
    $stPay->bind_param(
      "iidssss",
      $insertedId,
      $uid,
      $montoTotalReserva, // TOTAL reserva
      $metodoEnum,
      $refGateway,
      $detalle,          // ✅ solo nombres separados por coma, o NULL
      $fechaPago
    );
    $stPay->execute();
    $stPay->close();

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

        <hr style="opacity:.2; margin:10px 0">

        <div><strong>Total reserva (pagos.monto):</strong> $ <?= number_format($montoTotalReserva, 2, ',', '.') ?></div>
        <div><strong>Cobrado al jugador:</strong> $ <?= number_format($montoCobradoJugador, 2, ',', '.') ?></div>
        <div><strong>Fecha de pago:</strong> <?= $fechaPago ? h($fechaPago) : '-' ?></div>

        <?php if ($divididoEnUi): ?>
          <div style="margin-top:8px;"><strong>Dividido en:</strong> <?= h($divididoEnUi) ?></div>
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
