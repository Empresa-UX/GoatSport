<?php
declare(strict_types=1);
require __DIR__ . '/../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
require __DIR__ . '/../../../../../config.mercadopago.php';

ensure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo 'Method Not Allowed'; exit;
}

$raw = file_get_contents('php://input');
$event = json_decode($raw, true) ?: [];

/** Esperamos { type: "payment", data: { id: "1234" } } (o "action") */
$type = (string)($event['type'] ?? $event['action'] ?? '');
$pid  = (string)($event['data']['id'] ?? '');

if ($type !== 'payment' || $pid === '') {
  http_response_code(200); echo 'ignored'; exit;
}

/** Consultar pago en MP */
$resp = mp_api_get('/v1/payments/'.urlencode($pid));
if (!($resp['ok'] ?? false)) {
  http_response_code(500); echo 'mp fetch error'; exit;
}
$p = $resp['body'];

/** Validaciones negocio */
$expectedRef  = $_SESSION['mp_external_ref'] ?? null;
$expectedAmt  = $_SESSION['mp_esperado']['monto']   ?? null;
$expectedCurr = $_SESSION['mp_esperado']['moneda']  ?? null;

$approved = ($p['status'] ?? '') === 'approved';
$currOK   = (string)($p['currency_id'] ?? '') === (string)$expectedCurr;
$amtPaid  = (float)($p['transaction_amount'] ?? -1.0);
$amountOK = round($amtPaid, 2) === round((float)$expectedAmt, 2);
$refOK    = (string)($p['external_reference'] ?? '') === (string)$expectedRef;

if ($approved && $amountOK && $currOK && $refOK) {
  $_SESSION['pago'] = [
    'metodo'     => 'mercadopago',
    'estado'     => 'pagado',
    'monto'      => $amtPaid,
    'fecha_pago' => date('Y-m-d H:i:s'),
    'payment_id' => (string)$p['id'],
  ];
  // TODO: persistir en DB: tabla pagos + actualizar reserva (idempotente)
  http_response_code(200); echo 'ok'; exit;
}

http_response_code(200);
echo 'mismatch'; // Por qué: evita filtrar info a terceros.