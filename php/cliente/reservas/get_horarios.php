<?php
header('Content-Type: application/json; charset=utf-8');

$configPath = __DIR__ . '/../../config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.php no encontrado']);
    exit;
}
require_once $configPath;

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'DB no disponible']);
    exit;
}

$canchaId = intval($_GET['cancha_id'] ?? 0);
$fecha    = $_GET['fecha'] ?? '';

if ($canchaId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode([]); exit;
}

$stmt = $conn->prepare("
    SELECT reserva_id, hora_inicio, hora_fin
    FROM reservas
    WHERE cancha_id = ? AND fecha = ? AND estado != 'cancelada'
");
if (!$stmt) { echo json_encode([]); exit; }

$stmt->bind_param("is", $canchaId, $fecha);
$stmt->execute();
$result = $stmt->get_result();

$horarios = [];
while ($row = $result->fetch_assoc()) {
    $hi = substr($row['hora_inicio'], 0, 8);
    $hf = substr($row['hora_fin'], 0, 8);

    $inicio_min = intval(substr($hi, 0, 2)) * 60 + intval(substr($hi, 3, 2));
    $fin_min    = intval(substr($hf, 0, 2)) * 60 + intval(substr($hf, 3, 2));

    $horarios[] = [
        'reserva_id' => (int)$row['reserva_id'],
        'inicio'     => $hi,
        'fin'        => $hf,
        'inicio_min' => $inicio_min,
        'fin_min'    => $fin_min
    ];
}
$stmt->close();

echo json_encode($horarios);
