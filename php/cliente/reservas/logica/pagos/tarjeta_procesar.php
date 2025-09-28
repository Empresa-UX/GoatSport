<?php
declare(strict_types=1);
require __DIR__ . '/../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
require __DIR__ . '/../../../../../config.mercadopago.php';
ensure_session();
header('Content-Type: application/json; charset=utf-8');

// DEMO: aprobar siempre para ver el flujo end-to-end
if (defined('MP_DEMO_FORCE_APPROVED') && MP_DEMO_FORCE_APPROVED === true) {
  $reserva = $_SESSION['reserva'] ?? null;
  if (!$reserva) { http_response_code(400); echo json_encode(['error'=>'Reserva no encontrada']); exit; }

  $canchaId = (int)$reserva['cancha_id']; $monto = 0.0;
  if ($stmt = $conn->prepare("SELECT precio FROM canchas WHERE cancha_id = ?")) {
    $stmt->bind_param("i",$canchaId); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    $monto = (float)($row['precio'] ?? 0);
  }

  $_SESSION['pago'] = [
    'metodo'     => 'tarjeta',
    'estado'     => 'pagado',
    'monto'      => $monto,
    'fecha_pago' => date('Y-m-d H:i:s'),
    'payment_id' => 'DEMO-'.bin2hex(random_bytes(6)),
  ];
  echo json_encode(['ok'=>true,'demo'=>true]); exit;
}

// ======= REAL (cuando quites DEMO) =======
// Logs en /php/logs
$logDir = __DIR__ . '/../../../../logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
$logFile = $logDir.'/mp_card_payments.log';
$log = function(string $msg, array $ctx = []) use ($logFile) {
  @file_put_contents($logFile, date('c')." $msg ".json_encode($ctx, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
};

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) { http_response_code(400); echo json_encode(['error'=>'Payload no JSON']); exit; }

$card = $body['cardFormData'] ?? null;
if (!$card) { http_response_code(400); echo json_encode(['error'=>'cardFormData faltante']); exit; }

$token       = (string)($card['token'] ?? '');
$pmid        = (string)($card['payment_method_id'] ?? '');
$issuerId    = $card['issuer_id'] ?? ($card['issuer']['id'] ?? null);
$installments= (int)($card['installments'] ?? 1);
$payerEmail  = (string)($card['payer']['email'] ?? 'comprador@example.com');
$idType      = strtoupper(trim((string)($card['payer']['identification']['type'] ?? 'DNI')));
$idNumberRaw = (string)($card['payer']['identification']['number'] ?? '12345678');
$idNumber    = preg_replace('/\D+/', '', $idNumberRaw) ?: '12345678';
$holderName  = (string)($card['cardholder']['name'] ?? '');

if (!$token || !$pmid) { http_response_code(400); echo json_encode(['error'=>'Faltan token o payment_method_id']); exit; }
if (!MP_ACCESS_TOKEN)  { http_response_code(500); echo json_encode(['error'=>'MP_ACCESS_TOKEN no configurado']); exit; }

$reserva = $_SESSION['reserva'] ?? null;
if (!$reserva) { http_response_code(400); echo json_encode(['error'=>'Reserva no encontrada en sesión']); exit; }

$canchaId = (int)$reserva['cancha_id'];
$monto = 0.0;
if ($stmt = $conn->prepare("SELECT precio FROM canchas WHERE cancha_id = ?")) {
  $stmt->bind_param("i",$canchaId); $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
  $monto = (float)($row['precio'] ?? 0);
}
if ($monto <= 0) { http_response_code(400); echo json_encode(['error'=>'Monto inválido']); exit; }

$externalRef = 'RES-'.$reserva['cancha_id'].'-'.$reserva['fecha'].'-'.$reserva['hora_inicio'];

// USAR mp_api_post() DE lib/util.php (NO redefinirla aquí)
$payload = [
  "transaction_amount" => (float)$monto,
  "token"              => $token,
  "description"        => "Reserva cancha ".$reserva['cancha_id']." ".$reserva['fecha']." ".$reserva['hora_inicio'],
  "installments"       => $installments,
  "payment_method_id"  => $pmid,
  "payer" => [
    "email" => $payerEmail,
    "identification" => [ "type" => $idType ?: "DNI", "number" => $idNumber ],
    "first_name" => explode(' ', $holderName)[0] ?? '',
    "last_name"  => trim(substr($holderName, strlen(explode(' ', $holderName)[0] ?? ''))) ?: '',
  ],
  "binary_mode" => true,
  "external_reference" => $externalRef
];
if ($issuerId) { $payload["issuer_id"] = (int)$issuerId; }

$idem = 'idem-'.bin2hex(random_bytes(16));
$log('REQUEST /v1/payments', ['payload'=>$payload]);

$res = mp_api_post('/v1/payments', $payload, ['X-Idempotency-Key: '.$idem]); // <-- de util.php
$log('RESPONSE /v1/payments', ['http'=>$res['http'] ?? 0, 'raw'=>$res['raw'] ?? ($res['error'] ?? null)]);

if (!($res['ok'] ?? false)) {
  http_response_code($res['http'] ?? 500);
  echo json_encode(['error'=>'MP HTTP', 'detail'=>$res['raw'] ?? ($res['error'] ?? 'unknown')]); exit;
}

$status = $res['body']['status'] ?? 'in_process';
$payment_id = $res['body']['id'] ?? null;

if ($status === 'approved') {
  $_SESSION['pago'] = [
    'metodo'     => 'tarjeta',
    'estado'     => 'pagado',
    'monto'      => $monto,
    'fecha_pago' => date('Y-m-d H:i:s'),
    'payment_id' => $payment_id,
  ];
  echo json_encode(['ok'=>true,'payment_id'=>$payment_id]); exit;
}

http_response_code(402);
echo json_encode([
  'error'  => 'Estado: '.$status,
  'status' => $status,
  'payment_id' => $payment_id,
  'mp' => $res['body'] ?? null
]);