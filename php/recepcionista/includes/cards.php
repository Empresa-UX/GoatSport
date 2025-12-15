<?php
// File: php/recepcionista/includes/cards.php (REEMPLAZO)
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!isset($conn) || !($conn instanceof mysqli)) { require_once __DIR__ . '/../../config.php'; }

$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);
if ($proveedor_id <= 0) { return; }

/** Helper con prepare -> número */
function fetch_num_bind(mysqli $conn, string $sql, string $types = '', ...$vals): float {
  if (!$stmt = $conn->prepare($sql)) return 0;
  if ($types) $stmt->bind_param($types, ...$vals);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_row() : [0];
  $stmt->close();
  return (float)($row[0] ?? 0);
}

/* Fechas útiles semana (lunes→domingo) */
$hoy = date('Y-m-d');
$dow = (int)date('N', strtotime($hoy));        // 1=lun ... 7=dom
$lunes   = date('Y-m-d', strtotime($hoy . ' -' . ($dow - 1) . ' days'));
$domingo = date('Y-m-d', strtotime($hoy . ' +' . (7 - $dow) . ' days'));

/* 1) Reservas HOY */
$reservasHoy = fetch_num_bind($conn, "
  SELECT COUNT(*)
  FROM reservas r
  JOIN canchas c ON c.cancha_id = r.cancha_id
  WHERE c.proveedor_id = ? AND r.fecha = CURDATE()
", "i", $proveedor_id);

/* 2) Partidos sin cargar resultado (pasados; con reserva asociada del proveedor) */
$partidosSinResultado = fetch_num_bind($conn, "
  SELECT COUNT(*)
  FROM partidos p
  JOIN reservas r ON r.reserva_id = p.reserva_id
  JOIN canchas  c ON c.cancha_id  = r.cancha_id
  WHERE c.proveedor_id = ?
    AND p.resultado IS NULL
    AND p.fecha < NOW()
", "i", $proveedor_id);

/* 3) Promociones activas (vigentes hoy, activa=1) */
$promosActivas = fetch_num_bind($conn, "
  SELECT COUNT(*)
  FROM promociones p
  WHERE p.proveedor_id = ?
    AND p.activa = 1
    AND DATE(p.fecha_inicio) <= CURDATE()
    AND DATE(p.fecha_fin)    >= CURDATE()
", "i", $proveedor_id);

/* 4) Eventos especiales esta semana (excluye 'promocion') */
$eventosSemana = fetch_num_bind($conn, "
  SELECT COUNT(*)
  FROM eventos_especiales e
  WHERE e.proveedor_id = ?
    AND e.tipo <> 'promocion'
    AND DATE(e.fecha_fin)   >= ?
    AND DATE(e.fecha_inicio)<= ?
", "iss", $proveedor_id, $lunes, $domingo);
?>
<div class="cards">
  <div class="card">
    <h3>Reservas de hoy</h3>
    <p><?= number_format($reservasHoy, 0, ',', '.') ?></p>
  </div>
  <div class="card">
    <h3>Partidos sin registrar</h3>
    <p><?= number_format($partidosSinResultado, 0, ',', '.') ?></p>
  </div>
  <div class="card">
    <h3>Promociones activas</h3>
    <p><?= number_format($promosActivas, 0, ',', '.') ?></p>
  </div>
  <div class="card">
    <h3>Eventos especiales esta semana</h3>
    <p><?= number_format($eventosSemana, 0, ',', '.') ?></p>
  </div>
</div>
