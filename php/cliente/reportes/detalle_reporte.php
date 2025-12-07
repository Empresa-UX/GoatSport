<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\reportes\detalle_reporte.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: /php/login.php"); exit;
}

$userId = (int)$_SESSION['usuario_id'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: /php/cliente/reportes/historial_reportes.php"); exit; }

/* Reporte del usuario + info cancha/club/reserva */
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

function badge_estado($estado){
  $base='display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid;';
  if ($estado === 'Pendiente') return '<span style="'.$base.'background:#fff6e5;color:#8a5a00;border-color:#f5d49a">Pendiente</span>';
  return '<span style="'.$base.'background:#e6fff5;color:#0d6b4d;border-color:#a5e4c8">Resuelto</span>';
}
?>
<style>
/* Layout: Detalle más ancho, Información más angosta */
.grid{display:grid;grid-template-columns:1.6fr 0.8fr;gap:40px;align-items:start}
@media (max-width:900px){.grid{grid-template-columns:1fr;gap:20px}}
/* Tablas consistentes con tu UI */
table{width:100%;border-collapse:collapse;font-size:17px}
th,td{padding:12px 14px;border-bottom:1px solid rgba(0,0,0,0.08);text-align:left}
.label-stat{font-weight:600;width:100px;white-space:nowrap}
.value-stat{word-break:break-word}
/* Textos largos: mejor legibilidad */
.prose-box{white-space:pre-wrap;word-break:break-word;line-height:1.55}
.subtle{color:#5a6b6c;font-size:13px}
</style>

<div class="page-wrap">
  <h1 class="page-title">Detalle del reporte #<?= (int)$rep['id'] ?></h1>

  <div class="grid">
    <!-- Primero: Detalle (más ancho) -->
    <div>
      <h2 class="section-title">Detalle</h2>
      <div class="card-white">
        <table>
          <tbody>
            <tr>
              <td class="label-stat">Descripción</td>
              <td class="value-stat"><div class="prose-box"><?= htmlspecialchars($rep['descripcion']) ?></div></td>
            </tr>
            <tr>
              <td class="label-stat">Respuesta del club</td>
              <td class="value-stat">
                <div class="prose-box">
                  <?= $rep['respuesta_proveedor'] ? htmlspecialchars($rep['respuesta_proveedor']) : 'Aún no hay respuesta.' ?>
                </div>
                <?php if ($rep['estado']==='Resuelto'): ?>
                  <div class="subtle" style="margin-top:6px;">Estado: Resuelto</div>
                <?php endif; ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Segundo: Información (más angosto) -->
    <div>
      <h2 class="section-title">Información</h2>
      <div class="card-white">
        <table>
          <tbody>
            <tr><td class="label-stat">Título</td><td class="value-stat"><?= htmlspecialchars($rep['nombre_reporte']) ?></td></tr>
            <tr><td class="label-stat">Estado</td><td class="value-stat"><?= badge_estado($rep['estado']) ?></td></tr>
            <tr><td class="label-stat">Fecha</td><td class="value-stat"><?= htmlspecialchars($rep['fecha_reporte']) ?></td></tr>
            <tr><td class="label-stat">Club</td><td class="value-stat"><?= htmlspecialchars($rep['club_nombre'] ?? '—') ?></td></tr>
            <tr><td class="label-stat">Cancha</td><td class="value-stat"><?= htmlspecialchars($rep['cancha_nombre'] ?? '—') ?></td></tr>
            <tr><td class="label-stat">Reserva</td><td class="value-stat"><?= $rep['reserva_id'] ? '#'.(int)$rep['reserva_id'] : '—' ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div style="text-align:center; margin-top:16px;">
    <a class="btn-add" href="/php/cliente/reportes/historial_reportes.php" style="text-decoration:none;display:inline-block;padding:10px 18px;">Volver al historial</a>
  </div>
</div>

<?php include './../includes/footer.php'; ?>
