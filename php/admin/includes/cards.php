<?php
// File: admin/partials/cards.php (REEMPLAZO)
include './../../config.php';

/** Helper seguro: COUNT/SUM; retorna 0 si falla */
function fetch_int(mysqli $conn, string $sql): int {
  $res = $conn->query($sql);
  if (!$res) return 0;
  $row = $res->fetch_row();
  return isset($row[0]) ? (int)$row[0] : 0;
}

/* 1) Proveedores activos (≥1 cancha activa y aprobada) */
$proveedoresActivos = fetch_int($conn, "
  SELECT COUNT(DISTINCT proveedor_id)
  FROM canchas
  WHERE activa = 1 AND estado = 'aprobado'
");

/* 2) Canchas activas (aprobadas) */
$canchasActivas = fetch_int($conn, "
  SELECT COUNT(*)
  FROM canchas
  WHERE activa = 1 AND estado = 'aprobado'
");

/* 3) Reportes pendientes (ajusta estados si difieren en tu esquema) */
$reportesPendientes = fetch_int($conn, "
  SELECT COUNT(*)
  FROM reportes
  WHERE estado IN ('pendiente','abierto')
");

/* 4) Torneos vigentes (fecha_fin ≥ hoy) */
$torneosVigentes = fetch_int($conn, "
  SELECT COUNT(*)
  FROM torneos
  WHERE fecha_fin >= CURDATE()
");
?>
<div class="cards">
  <div class="card">
    <h3>Proveedores activos</h3>
    <p><?= number_format($proveedoresActivos, 0, ',', '.') ?></p>
  </div>
  <div class="card">
    <h3>Canchas activas</h3>
    <p><?= number_format($canchasActivas, 0, ',', '.') ?></p>
  </div>
  <div class="card">
    <h3>Reportes pendientes</h3>
    <p><?= number_format($reportesPendientes, 0, ',', '.') ?></p>
  </div>
  <div class="card">
    <h3>Torneos vigentes</h3>
    <p><?= number_format($torneosVigentes, 0, ',', '.') ?></p>
  </div>
</div>
