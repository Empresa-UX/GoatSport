// RUTA: /php/cliente/reservas/logica/pagos/mp.php
<?php
declare(strict_types=1);
require __DIR__ . '/../../../../config.php';
require __DIR__ . '/../../../../../lib/util.php';
require __DIR__ . '/../../../../../config.mercadopago.php';
ensure_session();

/* Helpers */
function request_origin(): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme  = $isHttps ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? (($_SERVER['SERVER_NAME'] ?? 'localhost') . (isset($_SERVER['SERVER_PORT']) ? ':'.$_SERVER['SERVER_PORT'] : ''));
    return $scheme . '://' . $host;
}
function abort_with(string $msg, array $ctx = []): void {
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

/* Datos de la cancha */
$canchaNombre = "Cancha #$canchaId";
$monto = 0.0;
if ($stmt = $conn->prepare("SELECT nombre, precio FROM canchas WHERE cancha_id = ?")) {
    $stmt->bind_param("i", $canchaId);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $canchaNombre = $row['nombre'] ?? $canchaNombre;
        $monto = (float)($row['precio'] ?? 0);
    }
    $stmt->close();
}
if ($monto <= 0) { abort_with('Monto inválido.'); }

/* back_urls absolutas usando el origen real */
$origin     = request_origin(); // ej: http://localhost:3000
$callback   = $origin . "/php/cliente/reservas/logica/pagos/mp_callback.php";
$successUrl = $callback . '?status=success';
$failureUrl = $callback . '?status=failure';
$pendingUrl = $callback . '?status=pending';

$externalRef = 'RES-'.$canchaId.'-'.$fecha.'-'.$hora;

/* Payload Checkout Pro
   Nota: QUITAMOS auto_return para evitar el error en dev con localhost.
   Cuando tengas HTTPS público, reactivá: "auto_return" => "approved"
*/
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
    // "auto_return" => "approved", // <- reactivar SOLO cuando uses HTTPS público
    // "notification_url" => $origin . "/php/cliente/reservas/logica/pagos/mp_webhook.php", // opcional
];

/* Llamada */
$res = mp_api_post('/checkout/preferences', $payload);

if (!($res['ok'] ?? false)) {
    $detail = $res['body'] ?? ($res['raw'] ?? ($res['error'] ?? 'Error desconocido'));
    abort_with('Error MP al crear preferencia.', [
        'http'    => $res['http'] ?? null,
        'detail'  => $detail,
        'payload' => $payload,
        'ca'      => curl_ca_debug(),
    ]);
}

/* Redirigir a MP */
$init = $res['body']['init_point'] ?? ($res['body']['sandbox_init_point'] ?? null);
if (!$init) {
    abort_with('init_point no disponible en respuesta de MP.', [
        'response' => $res['body'] ?? null,
        'payload'  => $payload,
    ]);
}
header("Location: ".$init);
exit;
