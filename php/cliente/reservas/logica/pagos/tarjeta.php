<?php
/* =========================================================================
 * FILE: php/cliente/reservas/logica/pagos/tarjeta.php
 * ========================================================================= */
declare(strict_types=1);

require __DIR__ . '/../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
require __DIR__ . '/../../../../../config.mercadopago.php';

include __DIR__ . '/../../../includes/header.php';

function sess_user_id(): int {
  return (int)($_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 0));
}

$uid = sess_user_id();
$reserva = $_SESSION['reserva'] ?? [];

$canchaId = (int)($reserva['cancha_id'] ?? 0);
$fecha    = (string)($reserva['fecha'] ?? '');
$hora     = (string)($reserva['hora_inicio'] ?? '');

if ($uid <= 0 || $canchaId <= 0 || $fecha === '' || $hora === '') {
  http_response_code(400);
  echo "<div class='page-wrap'><p>Error: reserva incompleta.</p></div>";
  include __DIR__ . '/../../../includes/footer.php';
  exit;
}

$duracion = (int)($reserva['duracion'] ?? 0);

$splitPlan = $reserva['split_plan'] ?? ['enabled' => false];
$precioFinal = (float)($reserva['precio_final'] ?? 0);

$monto = (float)(
  !empty($splitPlan['enabled'])
    ? (float)($splitPlan['creator_amount'] ?? $precioFinal)
    : $precioFinal
);
$monto = round($monto, 2);

if ($monto <= 0) {
  http_response_code(400);
  echo "<div class='page-wrap'><p>Error: monto inválido.</p></div>";
  include __DIR__ . '/../../../includes/footer.php';
  exit;
}

$publicKey = (string)(defined('MP_PUBLIC_KEY') ? MP_PUBLIC_KEY : '');
if ($publicKey === '') {
  http_response_code(500);
  echo "<div class='page-wrap'><p>Error: MP_PUBLIC_KEY no configurada.</p></div>";
  include __DIR__ . '/../../../includes/footer.php';
  exit;
}

$canchaNombre = "Cancha #{$canchaId}";
if ($stmt = $conn->prepare("SELECT nombre FROM canchas WHERE cancha_id = ? LIMIT 1")) {
  $stmt->bind_param("i", $canchaId);
  $stmt->execute();
  $canchaNombre = (string)($stmt->get_result()->fetch_assoc()['nombre'] ?? $canchaNombre);
  $stmt->close();
}

$buyerEmail = 'cliente@example.com';
if ($stmt = $conn->prepare("SELECT email FROM usuarios WHERE user_id = ? LIMIT 1")) {
  $stmt->bind_param("i", $uid);
  $stmt->execute();
  $buyerEmail = (string)($stmt->get_result()->fetch_assoc()['email'] ?? $buyerEmail);
  $stmt->close();
}
?>
<style>
  .payment-summary{
    margin: 0 0 14px;
    padding: 14px 16px;
    border-radius: 14px;
    background: rgba(255,255,255,0.92);
    border: 1px solid rgba(255,255,255,0.65);
    box-shadow: 0 8px 22px rgba(0,0,0,.18);
    color: rgba(15,23,42,.95);
    font-size: 24px;
  }
  .payment-summary > div + div{ margin-top: 6px; }
  .payment-summary strong{ font-weight: 600; }
  .payment-summary .total{ margin-top: 8px; font-size: 15px; }
  .payment-summary .note{ margin-top: 10px; font-size: 12.5px; opacity: .85; }

  .mp-box{
    margin-top: 14px;
    border-radius: 14px;
    padding: 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.10);
  }

  .mp-form-shell{
    margin-top: 10px;
    border-radius: 14px;
    padding: 14px;
    background: rgba(255,255,255,0.92);
    border: 1px solid rgba(255,255,255,0.65);
    box-shadow: 0 10px 25px rgba(0,0,0,.22);
    color: rgba(15,23,42,0.95);
  }
  .mp-form-shell *{ color: inherit; }
  .mp-form-shell form{ background: transparent !important; }
  .mp-form-shell input,.mp-form-shell select{
    background: rgba(255,255,255,0.98) !important;
    border: 1px solid rgba(15,23,42,0.18) !important;
    border-radius: 12px !important;
    padding: 10px 12px !important;
    outline: none !important;
  }

  .mp-alert{
    display:none;
    margin-top:12px;
    padding:10px 12px;
    border-radius:12px;
    font-size:13px;
    line-height:1.25;
    white-space:pre-wrap;
  }
  .mp-alert.err{
    border: 1px solid rgba(239,68,68,.25);
    background: rgba(239,68,68,.10);
    color: rgba(127,29,29,1);
  }
  .mp-alert.ok{
    border: 1px solid rgba(16,185,129,.25);
    background: rgba(16,185,129,.10);
    color: rgba(6,95,70,1);
    font-weight:800;
  }
  .mp-loading{
    display:none;
    margin-top:12px;
    opacity:.85;
    font-size:13px;
  }
</style>

<div class="page-wrap">
  <div class="payment-container">
    <div class="mp-box">

      <div class="payment-summary">
        <div>
          <strong><?= h($canchaNombre) ?></strong>
          — <?= h($fecha) ?> <?= h($hora) ?><?= $duracion ? " (" . (int)$duracion . " min)" : "" ?>
        </div>

        <?php if (!empty($splitPlan['enabled'])): ?>
          <div>
            Dividir costos: <strong>Sí</strong> — Tu parte ahora:
            <strong>$ <?= number_format($monto, 2, ',', '.') ?></strong>
          </div>
          <div>
            Total reserva:
            <strong>$ <?= number_format((float)($reserva['precio_final'] ?? 0), 2, ',', '.') ?></strong>
          </div>
        <?php else: ?>
          <div class="total">
            Total a pagar ahora (con promos):
            <strong>$ <?= number_format($monto, 2, ',', '.') ?></strong>
          </div>
        <?php endif; ?>
      </div>

      <label style="display:none;">
        Email comprador:
        <input id="buyerEmail" type="email" value="<?= h($buyerEmail) ?>">
      </label>

      <div class="mp-form-shell">
        <div id="cardPaymentBrick_container"></div>
      </div>

      <div id="alertBox" class="mp-alert err"></div>
      <div id="okBox" class="mp-alert ok">Pago enviado. Redirigiendo…</div>
      <div id="loadingBox" class="mp-loading">Procesando pago…</div>
    </div>

    <div class="payment-footer" style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn-next" href="../../steps/reservas_pago.php" style="text-decoration:none;">← Volver</a>
    </div>
  </div>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
(function(){
  const PUBLIC_KEY = "<?= h($publicKey) ?>";
  const amount = Number("<?= number_format($monto, 2, '.', '') ?>");

  const alertBox = document.getElementById('alertBox');
  const okBox = document.getElementById('okBox');
  const loadingBox = document.getElementById('loadingBox');

  const showErr = (msg, extra) => {
    if (alertBox) {
      alertBox.style.display = 'block';
      alertBox.textContent = msg + (extra ? "\nDetalle: " + (typeof extra === 'string' ? extra : JSON.stringify(extra)) : "");
    }
    if (okBox) okBox.style.display = 'none';
    if (loadingBox) loadingBox.style.display = 'none';
    console.error('Pago error:', extra || msg);
  };

  const showOk = (msg) => {
    if (okBox) {
      okBox.style.display = 'block';
      okBox.textContent = msg;
    }
    if (loadingBox) loadingBox.style.display = 'none';
  };

  const showLoading = (on) => {
    if (!loadingBox) return;
    loadingBox.style.display = on ? 'block' : 'none';
  };

  if (!PUBLIC_KEY) { showErr('MP_PUBLIC_KEY no configurada'); return; }
  if (!amount || amount <= 0) { showErr('Monto inválido'); return; }

  const mp = new MercadoPago(PUBLIC_KEY, { locale: "es-AR" });
  const bricks = mp.bricks();

  bricks.create("cardPayment", "cardPaymentBrick_container", {
    initialization: {
      amount,
      payer: { email: (document.getElementById('buyerEmail')?.value || "cliente@example.com") }
    },
    callbacks: {
      onReady: () => console.log('CardPayment listo'),
      onError: (error) => showErr("No se pudo inicializar el formulario", error),

      onSubmit: async (cardFormData) => {
        try {
          if (alertBox) alertBox.style.display = 'none';
          if (okBox) okBox.style.display = 'none';
          showLoading(true);

          const res = await fetch("./tarjeta_procesar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ cardFormData })
          });

          const text = await res.text();
          let json;
          try { json = JSON.parse(text); } catch { json = { error: 'Respuesta no JSON', detail: text }; }

          if (!res.ok || json.error) {
            showErr("Pago rechazado o inválido", json);
            return;
          }

          showOk("Pago enviado. Redirigiendo…");

          const f = document.createElement('form');
          f.method = 'post';
          f.action = '../../steps/reservas_confirmacion.php';
          f.innerHTML = `<?= str_replace("\n","",csrf_input()) ?><input type="hidden" name="metodo" value="tarjeta">`;
          document.body.appendChild(f);
          f.submit();

        } catch (e) {
          showErr("Error de red", e);
        } finally {
          showLoading(false);
        }
      }
    }
  });
})();
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
