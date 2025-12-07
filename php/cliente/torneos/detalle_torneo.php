<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\torneos\detalle_torneo.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if ($_SESSION['rol'] !== 'cliente') { header("Location: /php/login.php"); exit; }

$userId   = (int)$_SESSION['usuario_id'];
$torneoId = isset($_GET['torneo_id']) ? (int)$_GET['torneo_id'] : 0;
if ($torneoId <= 0) { header("Location: /php/cliente/torneos/torneos.php"); exit; }

/* helpers */
function fmt_md(string $d): string {
    if (!$d) return '—';
    [$y,$m,$day] = explode('-', $d);
    $mes = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][(int)$m] ?? '';
    return (int)$day.' '.$mes;
}
function comienza_label($n){
  if ($n === null) return '—';
  $n = (int)$n;
  if ($n > 1) return "En $n días";
  if ($n === 1) return "Mañana";
  if ($n === 0) return "Hoy";
  if ($n === -1) return "Ayer";
  return "Hace ".abs($n)." días";
}
function badge_estado($e){
  $base='display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid;';
  if($e==='abierto') return '<span style="'.$base.'background:#e6fff5;color:#0d6b4d;border-color:#a5e4c8">abierto</span>';
  if($e==='cerrado') return '<span style="'.$base.'background:#fff6e5;color:#8a5a00;border-color:#f5d49a">cerrado</span>';
  return '<span style="'.$base.'background:#f2f4f7;color:#5b5b5b;border-color:#d8dde3">finalizado</span>';
}

/* Torneo */
$sql = "
  SELECT
    t.torneo_id, t.nombre, t.fecha_inicio, t.fecha_fin, t.estado,
    t.proveedor_id, COALESCE(prov.nombre,'—') AS club,
    DATEDIFF(t.fecha_inicio, CURDATE()) AS comienza_en
  FROM torneos t
  LEFT JOIN usuarios prov ON prov.user_id = t.proveedor_id
  WHERE t.torneo_id=?
  LIMIT 1
";
$st = $conn->prepare($sql);
$st->bind_param("i", $torneoId);
$st->execute();
$torneo = $st->get_result()->fetch_assoc();
$st->close();
if (!$torneo) { header("Location: /php/cliente/torneos/torneos.php"); exit; }

/* Participantes */
$stP = $conn->prepare("
  SELECT u.user_id, u.nombre
  FROM participaciones p
  JOIN usuarios u ON u.user_id = p.jugador_id
  WHERE p.torneo_id=? AND p.estado='aceptada'
  ORDER BY u.nombre ASC
");
$stP->bind_param("i", $torneoId);
$stP->execute();
$participants = $stP->get_result()->fetch_all(MYSQLI_ASSOC);
$stP->close();

$isJoined = false;
foreach ($participants as $pp) { if ((int)$pp['user_id'] === $userId) { $isJoined=true; break; } }

$okMsg  = isset($_GET['ok'])  ? trim($_GET['ok'])  : '';
$errMsg = isset($_GET['err']) ? trim($_GET['err']) : '';
?>
<style>
table tbody tr:hover{background:#f7fafb}
.actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.btn{padding:10px 14px;border:none;background:#07566b;color:#fff;border-radius:10px;cursor:pointer;font-weight:700}
.btn-outline{padding:10px 14px;border:1.5px solid #1bab9d;background:#fff;color:#1bab9d;border-radius:10px;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block}
.btn-outline:hover{background:rgba(27,171,157,.08)}
.cta-wrap{margin-top:16px;text-align:center}
.grid{display:grid;grid-template-columns:1.3fr 0.7fr;gap:40px}
@media (max-width:900px){.grid{grid-template-columns:1fr}}
</style>

<div class="page-wrap">
  <h1 class="page-title">Detalle del torneo</h1>

  <div class="card-white" style="margin-bottom:12px; display:none" id="alertBox"></div>

  <div class="grid">
    <div>
      <h2 class="section-title">Información</h2>
      <div class="card-white">
        <table>
          <tbody>
            <tr><td class="label-stat">Nombre</td><td class="value-stat"><?= htmlspecialchars($torneo['nombre']) ?></td></tr>
            <tr><td class="label-stat">Club</td><td class="value-stat"><?= htmlspecialchars($torneo['club']) ?></td></tr>
            <tr><td class="label-stat">Estado</td><td class="value-stat"><?= badge_estado($torneo['estado']) ?></td></tr>
            <tr><td class="label-stat">Inicio</td><td class="value-stat"><?= fmt_md($torneo['fecha_inicio']) ?></td></tr>
            <tr><td class="label-stat">Fin</td><td class="value-stat"><?= fmt_md($torneo['fecha_fin']) ?></td></tr>
            <tr><td class="label-stat">Comienza</td><td class="value-stat"><?= comienza_label($torneo['comienza_en']) ?></td></tr>
          </tbody>
        </table>
        <div class="actions" style="margin-top:10px">
          <?php if ($torneo['estado']==='abierto' && !$isJoined): ?>
            <form method="post" action="/php/cliente/torneos/unirseTorneo.php">
              <input type="hidden" name="torneo_id" value="<?= (int)$torneo['torneo_id'] ?>">
              <input type="hidden" name="return" value="/php/cliente/torneos/detalle_torneo.php?torneo_id=<?= (int)$torneo['torneo_id'] ?>">
              <button type="submit" class="btn">Unirme</button>
            </form>
          <?php endif; ?>
          <?php if ($isJoined): ?>
            <form method="post" action="/php/cliente/torneos/salirTorneo.php" onsubmit="return confirm('¿Salir del torneo?');">
              <input type="hidden" name="torneo_id" value="<?= (int)$torneo['torneo_id'] ?>">
              <button type="submit" class="btn-outline">Salir</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div>
      <h2 class="section-title">Participantes (<?= count($participants) ?>)</h2>
      <div class="card-white">
        <table>
          <thead><tr><th>Nombre</th></tr></thead>
          <tbody>
            <?php if ($participants): foreach ($participants as $p): ?>
              <tr><td><?= htmlspecialchars($p['nombre']) ?><?= ((int)$p['user_id']===$userId)?' (tú)':'' ?></td></tr>
            <?php endforeach; else: ?>
              <tr><td style="text-align:center;">Aún no hay inscriptos</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="cta-wrap">
    <a class="btn-outline" href="/php/cliente/torneos/torneos.php">Volver a torneos</a>
  </div>
</div>

<script>
// alert() si vuelve con ?ok/err desde unirse/salir
<?php if ($okMsg): ?> alert(<?= json_encode($okMsg) ?>); history.replaceState({}, '', window.location.pathname + window.location.search.replace(/(\?|&)ok=[^&]*/,'').replace(/\?&/,'?').replace(/\?$/,'')); <?php endif; ?>
<?php if ($errMsg): ?> alert(<?= json_encode($errMsg) ?>); history.replaceState({}, '', window.location.pathname + window.location.search.replace(/(\?|&)err=[^&]*/,'').replace(/\?&/,'?').replace(/\?$/,'')); <?php endif; ?>
</script>

<?php include './../includes/footer.php'; ?>
