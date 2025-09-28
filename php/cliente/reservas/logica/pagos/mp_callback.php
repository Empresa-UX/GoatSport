<?php
declare(strict_types=1);
require __DIR__ . '/../../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
ensure_session();

$status = $_GET['status'] ?? 'failure';

if ($status === 'success') {
  // Nota: podrías leer $_GET['payment_id'] y guardar referencia.
  $_SESSION['pago'] = [
    'metodo'     => 'mercadopago',
    'estado'     => 'pagado',
    'monto'      => 0, // en confirmación se toma el precio real de la cancha
    'fecha_pago' => date('Y-m-d H:i:s'),
    'payment_id' => $_GET['payment_id'] ?? null,
  ];
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
      <p><a href='../../../../reservas_pago.php'>Volver a elegir método</a></p></div>";
