<?php
declare(strict_types=1);
// estás en /php/cliente/reservas/logica/pagos/
require __DIR__ . '/../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
require __DIR__ . '/../../../../../config.mercadopago.php';
require __DIR__ . '/../../../includes/header.php';

ensure_session();

$reserva = $_SESSION['reserva'] ?? [];
$canchaId = (int)($reserva['cancha_id'] ?? 0);
$fecha    = (string)($reserva['fecha'] ?? '');
$hora     = (string)($reserva['hora_inicio'] ?? '');
if ($canchaId <= 0 || !$fecha || !$hora) { http_response_code(400); die('Reserva incompleta.'); }

$canchaNombre = "Cancha #$canchaId"; $monto = 0.0;
if ($stmt = $conn->prepare("SELECT nombre, precio FROM canchas WHERE cancha_id = ?")) {
  $stmt->bind_param("i", $canchaId); $stmt->execute();
  if ($row = $stmt->get_result()->fetch_assoc()) { $canchaNombre = $row['nombre']; $monto = (float)$row['precio']; }
  $stmt->close();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><title>Pagar con tarjeta</title>
  <style>
    .page-wrap{max-width:720px; margin: auto;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif}
    .hint{background:#0f172a;color:#e2e8f0;padding:8px 12px;border-radius:8px;margin:10px 0;font-size:14px}
    .btn{padding:10px 14px;border:0;border-radius:10px;cursor:pointer}
    .back{background:#334155;color:#fff}
    .brand{color:#38bdf8}
    .err{background:#fee2e2;color:#7f1d1d;padding:8px 12px;border-radius:8px;margin:10px 0;font-size:14px;display:none;white-space:pre-wrap}
    .ok{background:#ecfdf5;color:#065f46;padding:8px 12px;border-radius:8px;margin:10px 0;font-size:14px;display:none}
  </style>
</head>
<body>
<div class="page-wrap">
  <h1 class="brand">Pagar con tarjeta</h1>
  <p><?= h($canchaNombre) ?> — <?= h($fecha) ?> <?= h($hora) ?> — <strong>$ <?= number_format($monto,2,',','.') ?></strong></p>

  <label style="display:block;margin:8px 0;">Email comprador:
    <input id="buyerEmail" type="email" value="cristianchejo55@gmail.com" style="width:100%;max-width:360px;">
  </label>

  <div id="alertBox" class="err"></div>
  <div id="okBox" class="ok">Pago aprobado. Redirigiendo…</div>
  <div id="cardPaymentBrick_container" style="margin-top:12px;"></div>

  <!-- estás en /php/cliente/reservas/logica/pagos/ -->
  <form id="cancel" method="get" action="../../steps/reservas.php" style="margin-top:16px;">
    <input type="hidden" name="cancha" value="<?= (int)$canchaId ?>">
  </form>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
  (function(){
    const PUBLIC_KEY = "<?= h(MP_PUBLIC_KEY) ?>";
    const alertBox = document.getElementById('alertBox');
    const okBox = document.getElementById('okBox');
    const showErr = (msg, extra) => {
      alertBox.style.display='block'; okBox.style.display='none';
      alertBox.textContent = msg + (extra ? "\nDetalle: " + (typeof extra==='string'?extra:JSON.stringify(extra)) : "");
      console.error('Pago error:', extra || msg);
    };
    const showOk = (msg) => { okBox.style.display='block'; okBox.textContent = msg; }

    if (!PUBLIC_KEY) { showErr('MP_PUBLIC_KEY no configurada'); return; }

    const mp = new MercadoPago(PUBLIC_KEY, { locale: "es-AR" });
    const bricks = mp.bricks();
    const amount = Number("<?= number_format($monto,2,'.','') ?>");

    bricks.create("cardPayment", "cardPaymentBrick_container", {
      initialization: {
        amount,
        payer: { email: document.getElementById('buyerEmail').value || "cliente@example.com" }
      },
      callbacks: {
        onReady: () => console.log('CardPayment listo'),
        onError: (error) => showErr("No se pudo inicializar el formulario", error),
        onSubmit: async (cardFormData) => {
          try {
            // archivo real en tu carpeta
            const res  = await fetch("./tarjeta_procesar.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ cardFormData })
            });
            const text = await res.text();
            let json; try { json = JSON.parse(text); } catch { json = { error: 'Respuesta no JSON', detail: text }; }
            if (!res.ok || json.error) { showErr("Pago rechazado o inválido", json); return; }
            showOk("Pago aprobado. Redirigiendo…");

            // confirmación vive en /php/cliente/reservas/steps/
            const f = document.createElement('form');
            f.method = 'post'; f.action = '../../steps/reservas_confirmacion.php';
            f.innerHTML = `<?= str_replace("\n","",csrf_input()) ?><input type="hidden" name="metodo" value="tarjeta">`;
            document.body.appendChild(f); f.submit();
          } catch (e) { showErr("Error de red", e); }
        }
      }
    });
  })();
</script>
</body>
</html>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>
