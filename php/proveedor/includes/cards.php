<?php
// ======================================================================
// file: php/proveedor/includes/cards.php
// Cards proveedor (mismo diseño .cards/.card):
// - Ingresos totales
// - Ingresos del mes
// - Ingresos diarios
// - Canchas a disposición (activas y aprobadas)
// ======================================================================

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Asegura $conn si se incluye por fuera
if (!isset($conn) || !($conn instanceof mysqli)) {
  require_once __DIR__ . '/../../config.php';
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  // No rompemos el layout si no hay sesión válida
  return;
}

$proveedor_id = (int)$_SESSION['usuario_id'];

/* --------- 1) Ingresos TOTALES (pagos acreditados) --------- */
$ingTot = 0.0;
$sql = "
  SELECT COALESCE(SUM(p.monto),0) AS total
  FROM pagos p
  JOIN reservas r ON r.reserva_id = p.reserva_id
  JOIN canchas  c ON c.cancha_id  = r.cancha_id
  WHERE p.estado = 'pagado'
    AND c.proveedor_id = ?
";
if ($st = $conn->prepare($sql)) {
  $st->bind_param("i", $proveedor_id);
  $st->execute();
  $ingTot = (float)($st->get_result()->fetch_assoc()['total'] ?? 0);
  $st->close();
}

/* --------- 2) Ingresos del MES (por fecha de reserva) --------- */
$ingMes = 0.0;
$sql = "
  SELECT COALESCE(SUM(p.monto),0) AS total
  FROM pagos p
  JOIN reservas r ON r.reserva_id = p.reserva_id
  JOIN canchas  c ON c.cancha_id  = r.cancha_id
  WHERE p.estado = 'pagado'
    AND c.proveedor_id = ?
    AND r.fecha BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND LAST_DAY(CURDATE())
";
if ($st = $conn->prepare($sql)) {
  $st->bind_param("i", $proveedor_id);
  $st->execute();
  $ingMes = (float)($st->get_result()->fetch_assoc()['total'] ?? 0);
  $st->close();
}

/* --------- 3) Ingresos DIARIOS (por fecha de reserva = hoy) --------- */
$ingDia = 0.0;
$sql = "
  SELECT COALESCE(SUM(p.monto),0) AS total
  FROM pagos p
  JOIN reservas r ON r.reserva_id = p.reserva_id
  JOIN canchas  c ON c.cancha_id  = r.cancha_id
  WHERE p.estado = 'pagado'
    AND c.proveedor_id = ?
    AND r.fecha = CURDATE()
";
if ($st = $conn->prepare($sql)) {
  $st->bind_param("i", $proveedor_id);
  $st->execute();
  $ingDia = (float)($st->get_result()->fetch_assoc()['total'] ?? 0);
  $st->close();
}

/* --------- 4) Canchas a disposición (activas y aprobadas) --------- */
$canchasDisp = 0;
$sql = "
  SELECT COUNT(*) AS cant
  FROM canchas
  WHERE proveedor_id = ?
    AND activa = 1
    AND estado = 'aprobado'
";
if ($st = $conn->prepare($sql)) {
  $st->bind_param("i", $proveedor_id);
  $st->execute();
  $canchasDisp = (int)($st->get_result()->fetch_assoc()['cant'] ?? 0);
  $st->close();
}
?>

<div class="cards">
  <div class="card">
    <h3>Ingresos totales</h3>
    <p>$<?= number_format($ingTot, 2, ',', '.') ?></p>
  </div>
  <div class="card">
    <h3>Ingresos del mes</h3>
    <p>$<?= number_format($ingMes, 2, ',', '.') ?></p>
  </div>
  <div class="card">
    <h3>Ingresos diarios</h3>
    <p>$<?= number_format($ingDia, 2, ',', '.') ?></p>
  </div>
  <div class="card">
    <h3>Canchas a disposición</h3>
    <p><?= number_format($canchasDisp, 0, ',', '.') ?></p>
  </div>
</div>
