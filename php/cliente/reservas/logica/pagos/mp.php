<?php
/* =========================================================================
 * FILE: php/cliente/reservas/pagos/mp.php
 * ========================================================================= */
declare(strict_types=1);
require __DIR__ . '/../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
require __DIR__ . '/../../../../../config.mercadopago.php';

ensure_session();

function abort_with(string $msg, array $ctx = []): never {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo $msg;
  if ($ctx) { echo "\n\nDEBUG:\n" . json_encode($ctx, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); }
  exit;
}

/* Validar sesión/reserva */
$reserva = $_SESSION['reserva'] ?? null;
if (!$reserva || !isset($reserva['cancha_id'], $reserva['fecha'], $reserva['hora_inicio'])) {
  abort_with('Reserva no encontrada en sesión.');
}
$canchaId = (int)$reserva['cancha_id'];
$fecha    = (string)$reserva['fecha'];
$hora     = (string)$reserva['hora_inicio'];

/* Datos de la cancha (usa $conn de config.php) */
$canchaNombre = "Cancha #$canchaId";
$monto = 0.0;
/** @var mysqli $conn */
if (isset($conn) && $stmt = $conn->prepare("SELECT nombre, precio FROM canchas WHERE cancha_id = ?")) {
  $stmt->bind_param("i", $canchaId);
  $stmt->execute();
  if ($row = $stmt->get_result()->fetch_assoc()) {
    $canchaNombre = $row['nombre'] ?? $canchaNombre;
    $monto = (float)($row['precio'] ?? 0);
  }
  $stmt->close();
}
if ($monto <= 0) { abort_with('Monto inválido.'); }

/* URLs */
$origin     = request_origin();
$callback   = $origin . "/php/cliente/reservas/logica/pagos/mp_callback.php";
$successUrl = $callback . '?status=success';
$failureUrl = $callback . '?status=failure';
$pendingUrl = $callback . '?status=pending';

/** Webhook: usa constante; si seguís en localhost, MP no va a poder llamarte. */
$notificationUrl = MP_NOTIFICATION_URL;

$externalRef = 'RES-'.$canchaId.'-'.$fecha.'-'.$hora;
$_SESSION['mp_external_ref'] = $externalRef;
$_SESSION['mp_esperado']     = ['monto'=>$monto, 'moneda'=>'ARS'];

/* Payload Checkout Pro */
$payload = [
  "external_reference" => $externalRef,
  "items" => [[
    "title"       => $canchaNombre." (".$fecha." ".$hora.")",
    "quantity"    => 1,
    "currency_id" => "ARS",
    "unit_price"  => (float)$monto,
  ]],
  "back_urls" => [
    "success" => $successUrl,
    "failure" => $failureUrl,
    "pending" => $pendingUrl,
  ],
  "notification_url" => $notificationUrl,
  // "auto_return" => "approved", // habilitalo cuando tengas HTTPS público
];

$idem = 'idem-'.bin2hex(random_bytes(8));
$res = mp_api_post('/checkout/preferences', $payload, ["X-Idempotency-Key: $idem"]);

if (!($res['ok'] ?? false)) {
  $detail = $res['body'] ?? ($res['raw'] ?? ($res['error'] ?? 'Error desconocido'));
  abort_with('Error MP al crear preferencia.', [
    'http'    => $res['http'] ?? null,
    'detail'  => $detail,
    'payload' => $payload,
    'ca'      => curl_ca_debug(),
  ]);
}

$init = ($res['body']['sandbox_init_point'] ?? null) ?: ($res['body']['init_point'] ?? null);
if (!$init) {
  abort_with('init_point no disponible en respuesta de MP.', [
    'response' => $res['body'] ?? null,
    'payload'  => $payload,
  ]);
}
header("Location: ".$init);
exit;