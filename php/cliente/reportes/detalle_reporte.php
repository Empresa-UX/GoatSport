<?php
/* =========================================================================
 * FILE: detalle_reporte.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: /php/login.php"); exit;
}

$userId = (int)$_SESSION['usuario_id'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: /php/cliente/reportes/historial_reportes.php"); exit; }

/* Reporte del usuario + info cancha/club */
$sql = "
  SELECT rep.*, c.nombre AS cancha_nombre, c.ubicacion,
         u.nombre AS club_nombre
  FROM reportes rep
  LEFT JOIN canchas c ON c.cancha_id = rep.cancha_id
  LEFT JOIN usuarios u ON u.user_id = c.proveedor_id
  WHERE rep.id = ? AND rep.usuario_id = ?
  LIMIT 1
";
$st = $conn->prepare($sql);
$st->bind_param("ii", $id, $userId);
$st->execute();
$rep = $st->get_result()->fetch_assoc();
$st->close();

if (!$rep) {
  echo "<div class='page-wrap'><h2>No existe el reporte o no tienes acceso.</h2></div>";
  include './../includes/footer.php';
  exit;
}

function fmt_dia_mes(string $ymd): string {
  $ts = strtotime($ymd);
  return $ts ? date('d/m', $ts) : $ymd;
}
function label_razon(?string $t): string {
  return ($t==='sistema') ? 'Sistema' : 'Cancha';
}
?>
<style>
.page-wrap{ padding:24px 16px 40px; }
.card-white{ max-width:1200px; margin:0 auto 24px auto; }

.grid{ display:grid; grid-template-columns:1.3fr 0.7fr; gap:40px; align-items:start; }
@media (max-width:900px){ .grid{ grid-template-columns:1fr; gap:20px; } }

/* Tabla consistente con Reservas */
table{ width:100%; border-collapse:separate; border-spacing:0; font-size:17px; }
thead th{ text-align:left; padding:12px 14px; color:#2a4e51; border-bottom:2px solid #e1ecec; font-weight:700; }
tbody td{ text-align:left; padding:12px 14px; border-bottom:1px solid #f0f5f5; }
tbody tr:hover{ background:#f7fafb; }
.label-stat{ font-weight:600; white-space:nowrap; width:140px; }
.value-stat{ word-break:break-word; }

/* Caja de descripción */
.prose-box{
  white-space:pre-wrap; word-break:break-word; line-height:1.55; padding:12px 14px; border-radius:10px;
  border:1px solid #e6ecec; background:#fcfdfd;
}

/* CTA */
.cta{ text-align:center; margin-top:16px; }
.btn-outline{
  padding:10px 16px; border:1.5px solid #1bab9d; background:#fff; color:#1bab9d;
  border-radius:10px; cursor:pointer; font-weight:700; text-decoration:none; display:inline-block;
}
.btn-outline:hover{ background:rgba(27,171,157,.08); }
</style>

<div class="page-wrap">
  <h1 class="page-title">Detalle del reporte</h1>

  <div class="grid">
    <!-- Columna izquierda: Descripción -->
    <div>
      <h2 class="section-title">Descripción</h2>
      <div class="card-white">
        <div class="prose-box"><?= htmlspecialchars($rep['descripcion']) ?></div>
      </div>
    </div>

    <!-- Columna derecha: Información -->
    <div>
      <h2 class="section-title">Información</h2>
      <div class="card-white">
        <table>
          <tbody>
            <tr><td class="label-stat">Título</td><td class="value-stat"><?= htmlspecialchars($rep['nombre_reporte']) ?></td></tr>
            <tr><td class="label-stat">Razón</td><td class="value-stat"><?= htmlspecialchars(label_razon($rep['tipo_falla'])) ?></td></tr>
            <tr><td class="label-stat">Estado</td><td class="value-stat"><?= htmlspecialchars($rep['estado']) ?></td></tr>
            <tr><td class="label-stat">Fecha</td><td class="value-stat"><?= fmt_dia_mes($rep['fecha_reporte']) ?></td></tr>
            <tr><td class="label-stat">Club</td><td class="value-stat"><?= htmlspecialchars($rep['club_nombre'] ?? '—') ?></td></tr>
            <tr><td class="label-stat">Cancha</td><td class="value-stat"><?= htmlspecialchars($rep['cancha_nombre'] ?? '—') ?></td></tr>
            <?php if (!empty($rep['reserva_id'])): ?>
              <tr><td class="label-stat">Reserva</td><td class="value-stat">#<?= (int)$rep['reserva_id'] ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="cta">
    <a class="btn-outline" href="/php/cliente/reportes/historial_reportes.php">Volver al historial</a>
  </div>
</div>

<?php include './../includes/footer.php'; ?>
