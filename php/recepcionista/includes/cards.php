<?php
// ======================================================================
// file: php/recepcionista/includes/cards.php  (REEMPLAZO COMPLETO)
// Mismo diseño que tu cards simple (.cards/.card) pero con métricas:
// - Total Ingresos (pagos acreditados del proveedor)
// - Reservas hoy (del proveedor)
// - Reservas esta semana (lunes→domingo) del proveedor
// - Canchas (activas) del proveedor
// ======================================================================

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Asegura $conn si se incluye fuera del flujo normal
if (!isset($conn) || !($conn instanceof mysqli)) {
  require_once __DIR__ . '/../../config.php';
}

$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);
if ($proveedor_id <= 0) {
  // Si no hay proveedor, no mostramos cards (evita romper layout)
  return;
}

/* ---------- 1) Ingresos TOTALES (pagado) ---------- */
$totalIngresos = 0.0;
$sql = "
  SELECT COALESCE(SUM(p.monto),0) AS total_ingresos
  FROM pagos p
  JOIN reservas r ON r.reserva_id = p.reserva_id
  JOIN canchas  c ON c.cancha_id  = r.cancha_id
  WHERE p.estado = 'pagado'
    AND c.proveedor_id = ?
";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $proveedor_id);
  $stmt->execute();
  $totalIngresos = (float)($stmt->get_result()->fetch_assoc()['total_ingresos'] ?? 0);
  $stmt->close();
}

/* ---------- 2) Reservas HOY ---------- */
$reservasHoy = 0;
$sql = "
  SELECT COUNT(*) AS reservas_hoy
  FROM reservas r
  JOIN canchas c ON c.cancha_id = r.cancha_id
  WHERE c.proveedor_id = ?
    AND r.fecha = CURDATE()
";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $proveedor_id);
  $stmt->execute();
  $reservasHoy = (int)($stmt->get_result()->fetch_assoc()['reservas_hoy'] ?? 0);
  $stmt->close();
}

/* ---------- 3) Reservas de la SEMANA (lunes → domingo) ---------- */
$hoy = date('Y-m-d');
$dow = (int)date('N', strtotime($hoy));        // 1=lun ... 7=dom
$lunes   = date('Y-m-d', strtotime($hoy . ' -' . ($dow - 1) . ' days'));
$domingo = date('Y-m-d', strtotime($hoy . ' +' . (7 - $dow) . ' days'));

$reservasSemana = 0;
$sql = "
  SELECT COUNT(*) AS reservas_semana
  FROM reservas r
  JOIN canchas c ON c.cancha_id = r.cancha_id
  WHERE c.proveedor_id = ?
    AND r.fecha BETWEEN ? AND ?
";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("iss", $proveedor_id, $lunes, $domingo);
  $stmt->execute();
  $reservasSemana = (int)($stmt->get_result()->fetch_assoc()['reservas_semana'] ?? 0);
  $stmt->close();
}

/* ---------- 4) Canchas ACTIVAS ---------- */
$totalCanchasActivas = 0;
$sql = "SELECT COUNT(*) AS total_canchas_activas FROM canchas WHERE proveedor_id = ? AND activa = 1";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $proveedor_id);
  $stmt->execute();
  $totalCanchasActivas = (int)($stmt->get_result()->fetch_assoc()['total_canchas_activas'] ?? 0);
  $stmt->close();
}
?>

<div class="cards">
  <div class="card">
    <h3>Total Ingresos</h3>
    <p>$<?= number_format($totalIngresos, 2) ?></p>
  </div>
  <div class="card">
    <h3>Reservas hoy</h3>
    <p><?= $reservasHoy ?></p>
  </div>
  <div class="card">
    <h3>Reservas esta semana</h3>
    <p><?= $reservasSemana ?></p>
  </div>
  <div class="card">
    <h3>Canchas</h3>
    <p><?= $totalCanchasActivas ?></p>
  </div>
</div>
