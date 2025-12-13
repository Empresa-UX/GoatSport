<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\torneos\detalle_torneo.php
 * (Patch de UI: etiquetas en negrita como en Detalle de Reservas)
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if ($_SESSION['rol'] !== 'cliente') { header("Location: /php/login.php"); exit; }

$userId   = (int)$_SESSION['usuario_id'];
$torneoId = isset($_GET['torneo_id']) ? (int)$_GET['torneo_id'] : 0;
if ($torneoId <= 0) { header("Location: /php/cliente/torneos/torneos.php"); exit; }

/* Helpers */
function fmt_md(string $d): string {
    if (!$d) return '—';
    [$y,$m,$day] = explode('-', $d);
    $mes = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][(int)$m] ?? '';
    return (int)$day.' '.$mes;
}
function tipo_label(?string $t): string {
    $t = strtolower((string)$t);
    return $t==='equipo' ? 'Equipo' : ($t==='individual' ? 'Individual' : ucfirst($t));
}

/* Torneo */
$sql = "
  SELECT
    t.torneo_id, t.nombre, t.fecha_inicio, t.fecha_fin, t.estado, t.tipo, t.capacidad,
    COALESCE(prov.nombre,'—') AS club
  FROM torneos t
  LEFT JOIN usuarios prov ON prov.user_id = t.proveedor_id
  WHERE t.torneo_id=? LIMIT 1
";
$st = $conn->prepare($sql);
$st->bind_param("i", $torneoId);
$st->execute();
$torneo = $st->get_result()->fetch_assoc();
$st->close();
if (!$torneo) { header("Location: /php/cliente/torneos/torneos.php"); exit; }

/* Participantes con email */
$participants = [];
$stP = $conn->prepare("
  SELECT u.user_id, u.nombre, u.email
  FROM participaciones p
  JOIN usuarios u ON u.user_id = p.jugador_id
  WHERE p.torneo_id=? AND p.estado='aceptada'
  ORDER BY u.nombre ASC
");
$stP->bind_param("i", $torneoId);
$stP->execute();
$participants = $stP->get_result()->fetch_all(MYSQLI_ASSOC);
$stP->close();

/* Ganador si finalizado (heurística simple por cantidad de victorias) */
$ganador = null;
if ($torneo['estado'] === 'finalizado') {
    $stG = $conn->prepare("
      SELECT u.user_id, u.nombre, u.email, COUNT(*) AS wins
      FROM partidos pa
      JOIN usuarios u ON u.user_id = pa.ganador_id
      WHERE pa.torneo_id=? AND pa.ganador_id IS NOT NULL
      GROUP BY u.user_id, u.nombre, u.email
      ORDER BY wins DESC
      LIMIT 1
    ");
    $stG->bind_param("i", $torneoId);
    $stG->execute();
    $ganador = $stG->get_result()->fetch_assoc();
    $stG->close();
}
?>
<style>
/* ====== Estilo consistente con Detalle de Reservas ====== */
.page-wrap{ padding:24px 16px 40px; }
.card-white{ max-width:1280px; margin:0 auto 24px auto; }
.card-white .section-title{ font-size:26px; font-weight:700; color:var(--text-dark); margin:0 0 8px; }

.detail-2col{ display:grid; grid-template-columns:1.3fr 0.7fr; gap:40px; }
@media (max-width:900px){ .detail-2col{ grid-template-columns:1fr; } }

table{ width:100%; border-collapse:separate; border-spacing:0; }
table thead th{ text-align:left; padding:12px 14px; color:#2a4e51; border-bottom:2px solid #e1ecec; font-weight:700; }
table tbody td{ text-align:left; padding:12px 14px; border-bottom:1px solid #f0f5f5; }
table tbody tr:hover{ background:#f7fafb; }

/* Igual que Reservas: la etiqueta a la izquierda va más fuerte */
.label-stat{ font-weight:600; }

/* Botones base igual que Reservas */
.cta-wrap{ margin-top:18px; text-align:center; display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
.btn-outline{
  padding:10px 16px; border:1.5px solid #1bab9d; background:#fff; color:#1bab9d;
  border-radius:10px; cursor:pointer; font-weight:700; text-decoration:none; display:inline-block;
}
.btn-outline:hover{ background:rgba(27,171,157,.08); }
</style>

<div class="page-wrap">
  <h1 class="page-title" style="text-align:center;">Detalle del torneo</h1>

  <?php if ($torneo['estado'] === 'finalizado'): ?>
    <div class="card-white">
      <h2 class="section-title">Información</h2>
      <table>
        <tbody>
          <tr><td class="label-stat">Nombre</td><td><?= htmlspecialchars($torneo['nombre']) ?></td></tr>
          <tr><td class="label-stat">Club</td><td><?= htmlspecialchars($torneo['club']) ?></td></tr>
          <tr><td class="label-stat">Fecha inicio</td><td><?= fmt_md($torneo['fecha_inicio']) ?></td></tr>
          <tr><td class="label-stat">Fecha fin</td><td><?= fmt_md($torneo['fecha_fin']) ?></td></tr>
          <tr><td class="label-stat">Tipo</td><td><?= tipo_label($torneo['tipo']) ?></td></tr>
          <tr><td class="label-stat">Ganador</td><td><?= $ganador ? htmlspecialchars($ganador['nombre']) : '—' ?></td></tr>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="detail-2col">
      <div>
        <h2 class="section-title">Información</h2>
        <div class="card-white">
          <table>
            <tbody>
              <tr><td class="label-stat">Nombre</td><td><?= htmlspecialchars($torneo['nombre']) ?></td></tr>
              <tr><td class="label-stat">Club</td><td><?= htmlspecialchars($torneo['club']) ?></td></tr>
              <tr><td class="label-stat">Fecha inicio</td><td><?= fmt_md($torneo['fecha_inicio']) ?></td></tr>
              <tr><td class="label-stat">Fecha fin</td><td><?= fmt_md($torneo['fecha_fin']) ?></td></tr>
              <tr><td class="label-stat">Tipo</td><td><?= tipo_label($torneo['tipo']) ?></td></tr>
              <tr><td class="label-stat">Capacidad</td><td><?= (int)$torneo['capacidad'] ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <h2 class="section-title">Participantes (<?= count($participants) ?>)</h2>
        <div class="card-white">
          <table>
            <thead><tr><th>Nombre</th><th>Email</th></tr></thead>
            <tbody>
              <?php if ($participants): foreach ($participants as $p): ?>
                <tr>
                  <td><?= htmlspecialchars($p['nombre']) ?><?= ((int)$p['user_id']===$userId)?' (tú)':'' ?></td>
                  <td><?= htmlspecialchars($p['email']) ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="2" style="text-align:center;">Aún no hay inscriptos</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="cta-wrap">
    <a class="btn-outline" href="/php/cliente/torneos/torneos.php">Volver a torneos</a>
  </div>
</div>

<?php include './../includes/footer.php'; ?>
