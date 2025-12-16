
<?php
/* =========================================================================
 * FILE: php/cliente/reservas/steps/reservas_pago.php
 * ========================================================================= */
include './../../../config.php';
require_once __DIR__ . '/../../../../lib/util.php';

/* ===================== AJAX: promos_preview (JSON) ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'promos_preview') {
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

    // Calcular hora_fin y DOW ISO (1=Lun..7=Dom)
    $startDT = DateTime::createFromFormat('Y-m-d H:i', $fecha . ' ' . $horaInicio);
    if (!$startDT) throw new Exception('fecha/hora inválida');

    $endDT = clone $startDT;
    $endDT->modify('+' . $duracion . ' minutes');

    $horaIni = $startDT->format('H:i:s');
    $horaFin = $endDT->format('H:i:s');
    $dowStr  = (string)((int)$startDT->format('N')); // ISO: 1..7

    $precioBase = $precioHora * ($duracion / 60.0);

    // Reservas previas del usuario (por proveedor) para minima_reservas
    $userId = (int)($_SESSION['user_id'] ?? 0);
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

    // Buscar promos aplicables (por proveedor y cancha o global)
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
    $stmt->bind_param(
      "iissssi",
      $proveedorId,
      $canchaId,
      $fecha,
      $dowStr,
      $horaIni,
      $horaFin,
      $prevCount
    );
    $stmt->execute();
    $rs = $stmt->get_result();
    $promos = [];
    while ($p = $rs->fetch_assoc()) $promos[] = $p;
    $stmt->close();

    // Política: aplicar SOLO la mejor promo (máximo %)
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

    echo json_encode([
      'ok' => true,
      'data' => [
        'promos' => $applied,
        'precio_base' => round($precioBase, 2),
        'precio_final' => round($precioFinal, 2),
        // útil para depurar en consola; podés borrar luego
        'debug' => [
          'proveedor_id' => $proveedorId,
          'cancha_id' => $canchaId,
          'fecha' => $fecha,
          'dow_iso' => (int)$dowStr,
          'hora_inicio' => $horaIni,
          'hora_fin' => $horaFin,
          'prev_reservas' => $prevCount
        ]
      ]
    ], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
  }
}
/* ===================== FIN AJAX: promos_preview ===================== */

include './../../includes/header.php';

/* ====== Flujo normal de pago (HTML) ====== */
$reserva_sess = $_SESSION['reserva'] ?? [];
$canchaId = $reserva_sess['cancha_id'] ?? ($_POST['cancha_id'] ?? ($_GET['cancha'] ?? null));
$fecha    = $reserva_sess['fecha'] ?? ($_POST['fecha'] ?? null);
$horaRaw  = $reserva_sess['hora_inicio'] ?? ($_POST['hora_inicio'] ?? null);
$duracion = (int)($reserva_sess['duracion'] ?? ($_POST['duracion'] ?? 0));

if (!$canchaId || !$fecha || !$horaRaw) {
  echo "<div class='page-wrap'><p>Error: faltan datos de la reserva (cancha, fecha u hora).</p>";
  echo "<p>GET: " . htmlspecialchars(json_encode($_GET)) . "</p>";
  echo "<p>POST: " . htmlspecialchars(json_encode($_POST)) . "</p>";
  echo "<p>SESSION: " . htmlspecialchars(json_encode($_SESSION['reserva'] ?? [])) . "</p></div>";
  include './../../includes/footer.php';
  exit();
}

$canchaNombre = "Cancha #{$canchaId}";
$canchaPrecio = null;
if ($conn) {
  $stmt = $conn->prepare("SELECT nombre, precio FROM canchas WHERE cancha_id = ?");
  if ($stmt) {
    $stmt->bind_param("i", $canchaId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      $canchaNombre = $row['nombre'];
      $canchaPrecio = $row['precio'];
    }
    $stmt->close();
  }
}

// Guardar sesión desde POST normal (si llegás acá por submit real)
// Importante: guardar duracion real y hora_fin real
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancha_id'], $_POST['fecha'], $_POST['hora_inicio'])) {
  $dur = (int)($_POST['duracion'] ?? 0);
  if ($dur <= 0) $dur = 60; // fallback
  $ini = DateTime::createFromFormat('Y-m-d H:i', $_POST['fecha'] . ' ' . substr($_POST['hora_inicio'],0,5));
  $fin = $ini ? (clone $ini)->modify("+{$dur} minutes") : null;

  $_SESSION['reserva'] = [
    'cancha_id'   => (int)$_POST['cancha_id'],
    'fecha'       => $_POST['fecha'],
    'hora_inicio' => substr($_POST['hora_inicio'],0,5),
    'duracion'    => $dur,
    'hora_fin'    => $fin ? $fin->format('H:i:s') : null,
  ];
}
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
    <div class="payment-title" style="margin-top:6px;">Seleccione su método de pago</div>
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
        <div class="payment-card" data-metodo="efectivo" role="listitem" tabindex="0" aria-pressed="false">
          <img src="./../../../../img/pagar_presencial.png" alt="Efectivo">
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
