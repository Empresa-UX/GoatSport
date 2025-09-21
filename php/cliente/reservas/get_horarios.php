<?php
// No HTML antes de <?php
header('Content-Type: application/json; charset=utf-8');

// Ajustá la ruta a config si hace falta
$configPath = __DIR__ . '/../../config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.php no encontrado']);
    exit;
}
require_once $configPath; // debe definir $conn (mysqli)

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'DB no disponible']);
    exit;
}

$canchaId = isset($_GET['cancha_id']) ? intval($_GET['cancha_id']) : 0;
$fecha = $_GET['fecha'] ?? '';

// Validar YYYY-MM-DD
if ($canchaId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT reserva_id, hora_inicio, hora_fin
    FROM reservas
    WHERE cancha_id = ? AND fecha = ? AND estado != 'cancelada'
");
if (!$stmt) {
    echo json_encode([]); exit;
}
$stmt->bind_param("is", $canchaId, $fecha);
if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode([]);
    exit;
}
$result = $stmt->get_result();

$horarios = [];
while ($row = $result->fetch_assoc()) {
    $hi = substr($row['hora_inicio'], 0, 8); // "HH:MM:SS"
    $hf = substr($row['hora_fin'], 0, 8);

    // convertir a minutos desde medianoche
    $partsHi = explode(':', $hi);
    $partsHf = explode(':', $hf);
    $inicio_min = intval($partsHi[0]) * 60 + intval($partsHi[1]);
    $fin_min    = intval($partsHf[0]) * 60 + intval($partsHf[1]);

    $horarios[] = [
        'reserva_id' => intval($row['reserva_id']),
        'inicio'     => $hi,
        'fin'        => $hf,
        'inicio_min' => $inicio_min,
        'fin_min'    => $fin_min
    ];
}
$stmt->close();

echo json_encode($horarios);
exit;
