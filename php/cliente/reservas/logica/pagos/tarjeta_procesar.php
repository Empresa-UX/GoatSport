<?php
/* =========================================================================
 * FILE: php/cliente/reservas/logica/pagos/tarjeta_procesar.php
 * ========================================================================= */
declare(strict_types=1);

require __DIR__ . '/../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
require __DIR__ . '/../../../../../config.mercadopago.php';

ensure_session();
header('Content-Type: application/json; charset=utf-8');

$reserva = $_SESSION['reserva'] ?? null;
if (!is_array($reserva) || empty($reserva['cancha_id']) || empty($reserva['fecha']) || empty($reserva['hora_inicio'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Reserva no encontrada o incompleta']);
  exit;
}

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
  echo json_encode(['error' => 'Monto inválido']);
  exit;
}

/* ====================== DEMO ====================== */
if (defined('MP_DEMO_FORCE_APPROVED') && MP_DEMO_FORCE_APPROVED === true) {
  $_SESSION['gateway_hint'] = [
    'gateway' => 'tarjeta_demo',
    'status'  => 'approved',
    'payment_id' => 'DEMO-' . bin2hex(random_bytes(6)),
    'amount' => $monto,
    'external_reference' => 'DEMO-' . bin2hex(random_bytes(4)),
    'at' => date('c'),
  ];
  echo json_encode(['ok' => true, 'demo' => true]);
  exit;
}

/* ====================== REAL ====================== */
if (!defined('MP_ACCESS_TOKEN') || !MP_ACCESS_TOKEN) {
  http_response_code(500);
  echo json_encode(['error' => 'MP_ACCESS_TOKEN no configurado']);
  exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
  http_response_code(400);
  echo json_encode(['error' => 'Payload no JSON']);
  exit;
}

$card = $body['cardFormData'] ?? null;
if (!is_array($card)) {
  http_response_code(400);
  echo json_encode(['error' => 'cardFormData faltante']);
  exit;
}

$token        = (string)($card['token'] ?? '');
$pmid         = (string)($card['payment_method_id'] ?? '');
$issuerId     = $card['issuer_id'] ?? ($card['issuer']['id'] ?? null);
$installments = (int)($card['installments'] ?? 1);

$payerEmail = (string)($card['payer']['email'] ?? '');
if ($payerEmail === '') $payerEmail = 'cliente@example.com';

$idType      = strtoupper(trim((string)($card['payer']['identification']['type'] ?? 'DNI')));
$idNumberRaw = (string)($card['payer']['identification']['number'] ?? '12345678');
$idNumber    = preg_replace('/\D+/', '', $idNumberRaw) ?: '12345678';

$holderName  = trim((string)($card['cardholder']['name'] ?? ''));

if ($token === '' || $pmid === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Faltan token o payment_method_id']);
  exit;
}
if ($installments < 1) $installments = 1;

$canchaId = (int)$reserva['cancha_id'];
$fecha    = (string)$reserva['fecha'];
$hora     = (string)$reserva['hora_inicio'];

$externalRef = 'PAYCARD-' . $canchaId . '-' . $fecha . '-' . $hora . '-' . bin2hex(random_bytes(4));

$first = '';
$last  = '';
if ($holderName !== '') {
  $parts = preg_split('/\s+/', $holderName);
  $first = $parts[0] ?? '';
  $last  = trim(substr($holderName, strlen($first)));
}

$payload = [
  "transaction_amount" => (float)$monto,
  "token"              => $token,
  "description"        => "Pago reserva cancha {$canchaId} {$fecha} {$hora}",
  "installments"       => $installments,
  "payment_method_id"  => $pmid,
  "payer" => [
    "email" => $payerEmail,
    "identification" => [
      "type"   => $idType ?: "DNI",
      "number" => $idNumber
    ],
    "first_name" => $first,
    "last_name"  => $last,
  ],
  "binary_mode" => true,
  "external_reference" => $externalRef,
];

if (!empty($issuerId)) {
  $payload["issuer_id"] = (int)$issuerId;
}

/**
 * ✅ Idempotency estable por sesión (si recargan / reintentan no duplica cobro).
 * Si querés 1 idempotency por intento, guardalo con timestamp y regeneralo sólo cuando cambie el monto.
 */
if (empty($_SESSION['mp_card_idem'])) {
  $_SESSION['mp_card_idem'] = 'idem-card-' . bin2hex(random_bytes(16));
}
$idem = (string)$_SESSION['mp_card_idem'];

$res = mp_api_post('/v1/payments', $payload, ['X-Idempotency-Key: ' . $idem]);

if (!($res['ok'] ?? false)) {
  http_response_code($res['http'] ?? 500);
  echo json_encode([
    'error'  => 'MP HTTP',
    'detail' => $res['raw'] ?? ($res['error'] ?? 'unknown'),
  ]);
  exit;
}

$status     = (string)($res['body']['status'] ?? 'in_process');
$payment_id = $res['body']['id'] ?? null;

/**
 * Importante:
 * - Aunque venga "approved", vos dijiste que con tarjeta queda "pendiente" (verificación humana).
 * - Yo NO lo fuerzo acá porque depende de tu lógica en reservas_confirmacion.php.
 *   Si vos querés forzarlo, acá podés mapear approved -> in_process/pending_manual.
 */
$_SESSION['gateway_hint'] = [
  'gateway' => 'tarjeta',
  'status'  => $status,
  'payment_id' => $payment_id,
  'external_reference' => $externalRef,
  'amount' => $monto,
  'at' => date('c'),
];

echo json_encode(['ok' => true, 'payment_id' => $payment_id, 'status' => $status]);
exit;
