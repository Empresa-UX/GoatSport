<?php
/* =========================================================================
 * FILE: php/cliente/reservas/steps/reservas_pago.php
 * ========================================================================= */
declare(strict_types=1);

require __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../../lib/util.php';

/* ===================== helpers ===================== */
function sess_user_id(): int {
  return (int)($_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 0));
}

function calc_promos_best(mysqli $conn, int $canchaId, string $fecha, string $horaInicioHHMM, int $duracion, int $userId): array {
  // Traer proveedor + precio/hora por cancha
  $stmt = $conn->prepare("SELECT proveedor_id, precio FROM canchas WHERE cancha_id = ? AND activa = 1 LIMIT 1");
  if (!$stmt) throw new Exception($conn->error);
  $stmt->bind_param("i", $canchaId);
  $stmt->execute();
  $cancha = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$cancha) throw new Exception('Cancha no encontrada/inactiva');

  $proveedorId = (int)$cancha['proveedor_id'];
  $precioHora  = (float)$cancha['precio'];

  $startDT = DateTime::createFromFormat('Y-m-d H:i', $fecha . ' ' . $horaInicioHHMM);
  if (!$startDT) throw new Exception('fecha/hora inválida');
  $endDT = clone $startDT;
  $endDT->modify('+' . $duracion . ' minutes');

  $horaIni = $startDT->format('H:i:s');
  $horaFin = $endDT->format('H:i:s');
  $dowStr  = (string)((int)$startDT->format('N')); // 1..7

  $precioBase = $precioHora * ($duracion / 60.0);

  // Reservas previas del usuario (por proveedor)
  $prevCount = 0;
  if ($userId > 0) {
    $st2 = $conn->prepare("
      SELECT COUNT(*) AS c
      FROM reservas r
      JOIN canchas c ON c.cancha_id = r.cancha_id
      WHERE r.creador_id = ?
        AND r.estado IN ('confirmada')
        AND c.proveedor_id = ?
    ");
    if ($st2) {
      $st2->bind_param("ii", $userId, $proveedorId);
      $st2->execute();
      $prevCount = (int)($st2->get_result()->fetch_assoc()['c'] ?? 0);
      $st2->close();
    }
  }

  $sql = "
    SELECT
      promocion_id, cancha_id, nombre, descripcion, porcentaje_descuento,
      fecha_inicio, fecha_fin, hora_inicio, hora_fin, dias_semana, minima_reservas
    FROM promociones
    WHERE activa = 1
      AND proveedor_id = ?
      AND (cancha_id IS NULL OR cancha_id = ?)
      AND ? BETWEEN fecha_inicio AND fecha_fin
      AND (dias_semana IS NULL OR dias_semana = '' OR FIND_IN_SET(?, dias_semana) > 0)
      AND (
        hora_inicio IS NULL OR hora_fin IS NULL
        OR NOT (hora_fin <= ? OR hora_inicio >= ?)
      )
      AND minima_reservas <= ?
    ORDER BY porcentaje_descuento DESC, cancha_id DESC, promocion_id ASC
  ";
  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception($conn->error);
  $stmt->bind_param("iissssi", $proveedorId, $canchaId, $fecha, $dowStr, $horaIni, $horaFin, $prevCount);
  $stmt->execute();
  $rs = $stmt->get_result();
  $promos = [];
  while ($p = $rs->fetch_assoc()) $promos[] = $p;
  $stmt->close();

  // aplicar SOLO la mejor promo
  $applied = [];
  $precioFinal = $precioBase;

  if (!empty($promos)) {
    $best = $promos[0];
    $pct = (float)$best['porcentaje_descuento'];
    $ahorro = round($precioBase * ($pct / 100.0), 2);
    $precioFinal = max(0, round($precioBase - $ahorro, 2));
    $applied[] = [
      'promocion_id' => (int)$best['promocion_id'],
      'nombre' => (string)$best['nombre'],
      'porcentaje_descuento' => (float)$best['porcentaje_descuento'],
      'ahorro' => (float)$ahorro,
    ];
  }

  return [
    'proveedor_id' => $proveedorId,
    'precio_base'  => round($precioBase, 2),
    'precio_final' => round($precioFinal, 2),
    'promos'       => $applied,
    'hora_ini_sql' => $horaIni,
    'hora_fin_sql' => $horaFin,
  ];
}

function build_split_plan(mysqli $conn, int $canchaId, float $precioFinal, bool $dividir, array $partsRaw, int $userId): array {
  // cantidad de participantes a cargar depende de capacidad (misma lógica que tu front)
  $cap = 0;
  $st = $conn->prepare("SELECT capacidad FROM canchas WHERE cancha_id=? LIMIT 1");
  if ($st) {
    $st->bind_param("i",$canchaId);
    $st->execute();
    $cap = (int)($st->get_result()->fetch_assoc()['capacidad'] ?? 0);
    $st->close();
  }
  $extraCount = ($cap >= 4) ? 3 : 1;     // los que se cargan en UI
  $totalPlayers = $dividir ? (1 + $extraCount) : 1; // creador + extras (si divide)

  $tipo = ($extraCount === 1) ? 'individual' : 'equipo';

  if (!$dividir) {
    return [
      'enabled' => false,
      'tipo_reserva' => $tipo,
      'creator_amount' => round($precioFinal, 2),
      'participants' => [], // no hay
    ];
  }

  // shares para totalPlayers (creador + extras)
  // para evitar drift: distribuimos y ajustamos el último
  $share = round($precioFinal / $totalPlayers, 2);
  $shares = array_fill(0, $totalPlayers, $share);
  $shares[$totalPlayers-1] = round($precioFinal - $share * ($totalPlayers-1), 2);

  // extras vienen de part[1..extraCount] (en tu front empieza en 1)
  $participants = [];
  $invitedSharesSum = 0.0;
  $creatorAmount = $shares[0];

  for ($i=1; $i<= $extraCount; $i++){
    $slotAmount = $shares[$i] ?? $share;
    $p = $partsRaw[$i] ?? [];
    $mode = ($p['mode'] ?? '');
    // tu front manda radios p{i}_mode, pero si no lo mandás, asumimos reg
    if ($mode !== 'inv' && $mode !== 'reg') $mode = 'reg';

    if ($mode === 'inv') {
      $first = trim((string)($p['first'] ?? ''));
      $last  = trim((string)($p['last'] ?? ''));
      $participants[] = [
        'slot' => $i,
        'mode' => 'inv',
        'first' => $first,
        'last'  => $last,
        'user_id' => null,
        'email' => null,
        'amount' => round($slotAmount, 2),
      ];
      $invitedSharesSum += $slotAmount;
    } else {
      $email = trim((string)($p['email'] ?? ''));
      $uid = null;
      if ($email !== '') {
        $s2 = $conn->prepare("SELECT user_id FROM usuarios WHERE rol='cliente' AND email=? LIMIT 1");
        if ($s2) {
          $s2->bind_param("s",$email);
          $s2->execute();
          $uid = (int)($s2->get_result()->fetch_assoc()['user_id'] ?? 0);
          $s2->close();
          if ($uid <= 0) $uid = null;
        }
      }
      $participants[] = [
        'slot' => $i,
        'mode' => 'reg',
        'first' => null,
        'last'  => null,
        'user_id' => $uid,
        'email' => $email ?: null,
        'amount' => round($slotAmount, 2),
      ];
    }
  }

  // el creador cubre invitados (si los hay)
// ✅ NO sumar invitados al creador.
// Invitado también “divide” el total, pero su pago queda fuera del gateway (offline / pendiente).
$creatorAmount = round($creatorAmount, 2);

  return [
    'enabled' => true,
    'tipo_reserva' => $tipo,
    'creator_amount' => $creatorAmount,
    'participants' => $participants,
    'total_players' => $totalPlayers,
    'share_base' => $share,
    'invited_extra' => round($invitedSharesSum, 2),
  ];
}

/* ===================== AJAX: promos_preview (JSON) ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'promos_preview') {
  header('Content-Type: application/json; charset=utf-8');
  try {
    $canchaId   = (int)($_POST['cancha_id'] ?? 0);
    $fecha      = trim((string)($_POST['fecha'] ?? ''));
    $horaInicio = trim((string)($_POST['hora_inicio'] ?? ''));
    $duracion   = (int)($_POST['duracion'] ?? 0);

    if ($canchaId <= 0) throw new Exception('cancha_id inválido');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) throw new Exception('fecha inválida');
    if (!preg_match('/^\d{2}:\d{2}$/', $horaInicio)) throw new Exception('hora_inicio inválida');
    if ($duracion <= 0) throw new Exception('duracion inválida');

    $uid = sess_user_id();
    $calc = calc_promos_best($conn, $canchaId, $fecha, $horaInicio, $duracion, $uid);

    echo json_encode([
      'ok' => true,
      'data' => [
        'promos' => $calc['promos'],
        'precio_base' => $calc['precio_base'],
        'precio_final' => $calc['precio_final'],
      ]
    ], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
  }
}
/* ===================== FIN AJAX ===================== */

include './../../includes/header.php';

/* ====== Flujo normal de pago (HTML) ====== */
$uid = sess_user_id();
if ($uid <= 0) { echo "<div class='page-wrap'><p>Error: sesión inválida.</p></div>"; include './../../includes/footer.php'; exit; }

// si venís desde reservas.php (POST real)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancha_id'], $_POST['fecha'], $_POST['hora_inicio'])) {
  $canchaId = (int)$_POST['cancha_id'];
  $fecha    = (string)$_POST['fecha'];
  $horaRaw  = substr((string)$_POST['hora_inicio'], 0, 5);
  $duracion = (int)($_POST['duracion'] ?? 0);
  if ($duracion <= 0) $duracion = 60;

  $dividir = isset($_POST['dividir_costos']) && (string)$_POST['dividir_costos'] === '1';

  // normalizamos part[i][...]
  $partsRaw = [];
  // tu form manda part[1..n][email/first/last] pero el mode NO está dentro de part.
  // lo metemos: p{i}_mode -> part[i]['mode']
  $part = $_POST['part'] ?? [];
  if (is_array($part)) {
    foreach ($part as $k => $v) {
      $idx = (int)$k;
      if ($idx <= 0) continue;
      if (!is_array($v)) $v = [];
      $partsRaw[$idx] = $v;
    }
  }
  // enganchar radios p{i}_mode
  for ($i=1; $i<=5; $i++){
    $k = "p{$i}_mode";
    if (isset($_POST[$k])) {
      if (!isset($partsRaw[$i])) $partsRaw[$i] = [];
      $partsRaw[$i]['mode'] = (string)$_POST[$k];
    }
  }

  // Calculamos precio final REAL server-side (no confiamos en precio_total del cliente)
  $calc = calc_promos_best($conn, $canchaId, $fecha, $horaRaw, $duracion, $uid);

  // hora_fin real
  $dtIni = DateTime::createFromFormat('Y-m-d H:i', $fecha.' '.$horaRaw);
  $dtFin = $dtIni ? (clone $dtIni)->modify("+{$duracion} minutes") : null;

  $splitPlan = build_split_plan($conn, $canchaId, (float)$calc['precio_final'], $dividir, $partsRaw, $uid);

  $_SESSION['reserva'] = [
    'cancha_id'   => $canchaId,
    'fecha'       => $fecha,
    'hora_inicio' => $horaRaw,
    'duracion'    => $duracion,
    'hora_fin'    => $dtFin ? $dtFin->format('H:i:s') : $calc['hora_fin_sql'],
    'precio_base' => $calc['precio_base'],
    'precio_final'=> $calc['precio_final'],
    'promos'      => $calc['promos'],
    'dividir'     => $splitPlan['enabled'] ? 1 : 0,
    'tipo_reserva'=> $splitPlan['tipo_reserva'],
    'split_plan'  => $splitPlan,
  ];

  // limpiamos hints de pagos previos
  unset($_SESSION['gateway_hint'], $_SESSION['mp_callback_hint']);
}

$reserva = $_SESSION['reserva'] ?? null;
if (!$reserva || !isset($reserva['cancha_id'], $reserva['fecha'], $reserva['hora_inicio'], $reserva['duracion'])) {
  echo "<div class='page-wrap'><p>Error: faltan datos de la reserva en sesión. Volvé a seleccionar horario.</p></div>";
  include './../../includes/footer.php';
  exit;
}

$canchaId = (int)$reserva['cancha_id'];
$fecha    = (string)$reserva['fecha'];
$horaRaw  = (string)$reserva['hora_inicio'];
$duracion = (int)$reserva['duracion'];

$canchaNombre = "Cancha #{$canchaId}";
if ($stmt = $conn->prepare("SELECT nombre FROM canchas WHERE cancha_id=? LIMIT 1")) {
  $stmt->bind_param("i",$canchaId);
  $stmt->execute();
  $canchaNombre = (string)($stmt->get_result()->fetch_assoc()['nombre'] ?? $canchaNombre);
  $stmt->close();
}

// monto que paga el creador (si divide, su parte; si no divide, total)
$splitPlan = $reserva['split_plan'] ?? [];
$payerAmount = (float)($splitPlan['enabled'] ? ($splitPlan['creator_amount'] ?? $reserva['precio_final']) : $reserva['precio_final']);

?>
<div class="page-wrap">
  <div class="flow-header">
    <h1>Flujo de Reserva</h1>
    <div class="steps-row">
      <div class="step"><span class="circle">1</span><span class="label">Selección del horario</span></div>
      <div class="step active"><span class="circle">2</span><span class="label">Abono</span></div>
      <div class="step"><span class="circle">3</span><span class="label">Confirmación</span></div>
    </div>
  </div>

  <div class="payment-container">
    <div class="payment-title" style="margin-top:6px; margin-bottom: 30px;">Seleccione su método de pago</div>

    <form id="paymentForm" method="post" action="../logica/pagos/pagos_router.php" novalidate>
      <?= csrf_input() ?>
      <input type="hidden" name="metodo" id="metodoInput" value="">
      <div class="payment-options" role="list">
        <div class="payment-card" data-metodo="tarjeta" role="listitem" tabindex="0" aria-pressed="false">
          <img src="./../../../../img/tarjeta_credito_debido.png" alt="Tarjeta">
          <span>Tarjeta de crédito / débito</span>
        </div>
        <div class="payment-card" data-metodo="mercadopago" role="listitem" tabindex="0" aria-pressed="false">
          <img src="./../../../../img/mercado_pago.png" alt="MercadoPago">
          <span>Mercado Pago</span>
        </div>
        <div class="payment-card" data-metodo="club" role="listitem" tabindex="0" aria-pressed="false">
          <img src="./../../../../img/pagar_presencial.png" alt="Club">
          <span>Pagar en el club</span>
        </div>
      </div>
      <div class="payment-footer" style="margin-top:18px;">
        <button type="button" class="btn-next" id="continueBtn">Continuar</button>
      </div>
    </form>
  </div>
</div>

<?php include './../../includes/footer.php'; ?>

<script>
(function () {
  const cards = Array.from(document.querySelectorAll('.payment-card'));
  const metodoInput = document.getElementById('metodoInput');
  const continueBtn = document.getElementById('continueBtn');

  function clearSelection() {
    cards.forEach(c => { c.classList.remove('selected'); c.setAttribute('aria-pressed', 'false'); });
  }
  function selectCard(card) {
    clearSelection();
    card.classList.add('selected');
    card.setAttribute('aria-pressed', 'true');
    metodoInput.value = card.dataset.metodo || '';
  }

  cards.forEach(card => {
    card.addEventListener('click', () => selectCard(card));
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectCard(card); }
    });
  });

  continueBtn.addEventListener('click', () => {
    if (!metodoInput.value) { alert('Por favor seleccione un método de pago antes de continuar.'); return; }
    document.getElementById('paymentForm').submit();
  });
})();
</script>
