<?php
/* =========================================================================
 * FILE: php/cliente/reservas/logica/horarios.php
 * ========================================================================= */
declare(strict_types=1);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

$configPath = __DIR__ . '/../../../config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.php no encontrado', 'path' => $configPath], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $configPath;

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'DB no disponible']);
    exit;
}

$canchaId = intval($_GET['cancha_id'] ?? 0);
$fecha    = $_GET['fecha'] ?? ''; // YYYY-MM-DD
if ($canchaId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['error' => 'params']); exit;
}

/* Cancha: horas; slot fijo de 30 minutos */
$cancha = null;
if ($stmt = $conn->prepare("SELECT hora_apertura, hora_cierre FROM canchas WHERE cancha_id=?")) {
    $stmt->bind_param("i", $canchaId);
    $stmt->execute();
    $cancha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$cancha) { echo json_encode(['error'=>'cancha']); exit; }

$apertura = $cancha['hora_apertura'] ?: '08:00:00';
$cierre   = $cancha['hora_cierre']   ?: '22:00:00';
$slotMin  = 30; // ← fuerza intervalos de 30 min

/* Reservas del día (no canceladas) */
$reservas = [];
if ($stmt = $conn->prepare("
    SELECT reserva_id, hora_inicio, hora_fin
    FROM reservas
    WHERE cancha_id = ? AND fecha = ? AND (estado IS NULL OR estado != 'cancelada')
")) {
    $stmt->bind_param("is", $canchaId, $fecha);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $hi = substr($row['hora_inicio'], 0, 8);
        $hf = substr($row['hora_fin'],   0, 8);
        $reservas[] = [
            'reserva_id' => (int)$row['reserva_id'],
            'inicio'     => $hi,
            'fin'        => $hf,
            'inicio_min' => intval(substr($hi, 0, 2)) * 60 + intval(substr($hi, 3, 2)),
            'fin_min'    => intval(substr($hf, 0, 2)) * 60 + intval(substr($hf, 3, 2))
        ];
    }
    $stmt->close();
}

/* Bloqueos desde eventos_especiales si existe */
$bloques = [];
$dayBlocked = false;
$hasEventos = false;
if ($chk = $conn->query("SHOW TABLES LIKE 'eventos_especiales'")) {
    $hasEventos = $chk->num_rows > 0;
    $chk->close();
}
if ($hasEventos) {
    if ($stmt = $conn->prepare("
        SELECT fecha_inicio, fecha_fin, tipo
        FROM eventos_especiales
        WHERE cancha_id=? AND tipo='bloqueo'
          AND DATE(?) BETWEEN DATE(fecha_inicio) AND DATE(fecha_fin)
    ")) {
        $stmt->bind_param("is", $canchaId, $fecha);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($e = $res->fetch_assoc()) {
            $fi = new DateTime($e['fecha_inicio']);
            $ff = new DateTime($e['fecha_fin']);
            $fullDay = ($fi->format('H:i:s') === '00:00:00' && in_array($ff->format('H:i:s'), ['23:59:59','00:00:00'], true));
            if ($fullDay) {
                $dayBlocked = true;
            } else {
                $bloques[] = [
                    'inicio'     => $fi->format('H:i:s'),
                    'fin'        => $ff->format('H:i:s'),
                    'inicio_min' => ((int)$fi->format('H'))*60 + (int)$fi->format('i'),
                    'fin_min'    => ((int)$ff->format('H'))*60 + (int)$ff->format('i'),
                ];
            }
        }
        $stmt->close();
    }
}

/* Hoy: minutos transcurridos (para bloquear slots cuyo INICIO ya pasó) */
$todayMin = null;
$hoy = (new DateTime('today'))->format('Y-m-d');
if ($fecha === $hoy) {
    $now = new DateTime();
    $todayMin = intval($now->format('H'))*60 + intval($now->format('i'));
}

/* Respuesta */
echo json_encode([
  'apertura'    => $apertura,
  'cierre'      => $cierre,
  'slot_min'    => $slotMin,   // 30 min
  'reservas'    => $reservas,
  'bloques'     => $bloques,
  'day_blocked' => $dayBlocked,
  'today_min'   => $todayMin,  // minutos desde 00:00
], JSON_UNESCAPED_UNICODE);
