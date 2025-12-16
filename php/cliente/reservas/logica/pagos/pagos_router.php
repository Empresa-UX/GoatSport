<?php
declare(strict_types=1);

require __DIR__ . '/../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
ensure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  die('Método no permitido');
}

csrf_validate_or_die();

$metodo  = (string)($_POST['metodo'] ?? '');
$reserva = $_SESSION['reserva'] ?? null;

if (!$reserva || !isset($reserva['cancha_id'], $reserva['fecha'], $reserva['hora_inicio'])) {
  http_response_code(400);
  die('Reserva incompleta en sesión.');
}

switch ($metodo) {
  case 'tarjeta':
    header('Location: ./tarjeta.php');
    exit;

  case 'mercadopago':
  case 'mercado_pago':
    header('Location: ./mp.php');
    exit;

  case 'club':
  case 'efectivo':
    ?>
    <form id="fwd" method="post" action="../../steps/reservas_confirmacion.php">
      <?= csrf_input() ?>
      <input type="hidden" name="metodo" value="club">
    </form>
    <script>document.getElementById('fwd').submit();</script>
    <?php
    exit;

  default:
    http_response_code(400);
    echo 'Método inválido';
    exit;
}
