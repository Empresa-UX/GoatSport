<?php
/* =========================================================================
 * FILE: php/cliente/reservas/pagos/mp_callback.php
 * ========================================================================= */
declare(strict_types=1);
require __DIR__ . '/../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
ensure_session();

$status     = $_GET['status'] ?? 'failure';
$payment_id = $_GET['payment_id'] ?? ($_GET['collection_id'] ?? null);

/** Por qué: no confirmamos acá, solo UX. */
$_SESSION['mp_callback_hint'] = [
  'status' => $status,
  'payment_id' => $payment_id,
];

if ($status === 'success') {
  ?>
  <form id="ok" method="post" action="../../steps/reservas_confirmacion.php">
    <?= csrf_input() ?>
    <input type="hidden" name="metodo" value="mercadopago">
  </form>
  <script>document.getElementById('ok').submit();</script>
  <?php
  exit;
}

echo "<div class='page-wrap'><p>El pago no fue aprobado (estado: ".h($status).").</p>
      <p><a href='../../steps/reservas_pago.php'>Volver a elegir método</a></p></div>";
exit;
