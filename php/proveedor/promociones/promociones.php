<?php
/* =========================================================================
 * file: php/proveedor/promociones/promociones.php
 * Listado de promociones (Proveedor) + Crear / Eliminar
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include './../includes/cards.php';
include __DIR__ . '/../../config.php';

if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') {
  header('Location: ../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

/* Canchas proveedor */
$sqlC = "SELECT cancha_id, nombre FROM canchas WHERE proveedor_id=? AND activa=1 ORDER BY nombre";
$stmt = $conn->prepare($sqlC);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$canchas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ====== Filtros ====== */
$hoy = date('Y-m-d');
$now = date('H:i:s');

$cancha_id = (int) ($_GET['cancha_id'] ?? 0);
$desde = $_GET['desde'] ?? $hoy;
$hasta = $_GET['hasta'] ?? date('Y-m-d', strtotime($hoy . ' +30 days'));
$estado = $_GET['estado'] ?? 'vigentes'; // vigentes|futuros|pasados|todas

$q = trim($_GET['q'] ?? '');
$pct_min = isset($_GET['pct_min']) && $_GET['pct_min'] !== '' ? (float) $_GET['pct_min'] : null;
$pct_max = isset($_GET['pct_max']) && $_GET['pct_max'] !== '' ? (float) $_GET['pct_max'] : null;

$sql = "
  SELECT p.promocion_id, p.proveedor_id, p.cancha_id, p.nombre, p.descripcion,
         p.porcentaje_descuento, p.fecha_inicio, p.fecha_fin,
         p.hora_inicio, p.hora_fin, p.dias_semana, p.minima_reservas, p.activa,
         c.nombre AS cancha_nombre
  FROM promociones p
  LEFT JOIN canchas c ON c.cancha_id = p.cancha_id
  WHERE p.proveedor_id = ?
    AND DATE(p.fecha_fin)   >= ?
    AND DATE(p.fecha_inicio)<= ?
";
$params = [$proveedor_id, $desde, $hasta];
$types = "iss";

if ($cancha_id > 0) {
  $sql .= " AND p.cancha_id=?";
  $params[] = $cancha_id; $types .= "i";
}
if ($q !== '') {
  $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
  $like = '%'.$q.'%';
  $params[] = $like; $params[] = $like; $types .= "ss";
}
if ($pct_min !== null) { $sql .= " AND p.porcentaje_descuento >= ?"; $params[] = $pct_min; $types .= "d"; }
if ($pct_max !== null) { $sql .= " AND p.porcentaje_descuento <= ?"; $params[] = $pct_max; $types .= "d"; }

/* Estado por fecha */
if ($estado === 'vigentes')      { $sql .= " AND p.fecha_inicio <= ? AND p.fecha_fin >= ?"; $params[]=$hoy; $params[]=$hoy; $types.="ss"; }
elseif ($estado === 'futuros')   { $sql .= " AND p.fecha_inicio > ?";  $params[]=$hoy; $types.="s"; }
elseif ($estado === 'pasados')   { $sql .= " AND p.fecha_fin < ?";     $params[]=$hoy; $types.="s"; }

$sql .= " ORDER BY p.fecha_inicio ASC, p.fecha_fin ASC, p.promocion_id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ====== Helpers ====== */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function estadoPromoFecha(array $p, string $hoy): string {
  $ini = $p['fecha_inicio']; $fin = $p['fecha_fin'];
  if ($ini <= $hoy && $hoy <= $fin) return 'Activa';
  if ($ini >  $hoy) return 'Próxima';
  return 'Finalizada';
}
function ddmm(?string $d): string { if(!$d) return '—'; $t=strtotime($d); return $t?date('d/m/y',$t):'—'; }
function hhmm(?string $t): string { return $t ? substr($t,0,5) : 'Sin límite'; }
function diaNumeroISO(string $dateYmd): int { return (int) date('N', strtotime($dateYmd)); } // 1..7
function diasSetIncluye(?string $set, int $dia): bool {
  if (!$set || $set === '') return true; $arr = array_map('trim', explode(',', $set)); return in_array((string)$dia, $arr, true);
}
function diasLindos(?string $set): string {
  if (!$set || $set === '') return 'Todos los días';
  $map = ['1'=>'L','2'=>'Ma','3'=>'Mi','4'=>'J','5'=>'V','6'=>'S','7'=>'D'];
  $arr = array_map('trim', explode(',', $set));
  return implode(', ', array_map(fn($d)=>$map[$d]??$d, $arr));
}
?>
<main>
  <div class="section">
    <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <h2>Promociones</h2>
      <a class="btn-add" href="promocionesForm.php">Crear promoción</a>
    </div>

    <style>
      /* ===== anchos manipulables ===== */
      :root{
        --col-nombre:    260px;
        --col-canchas:   160px;
        --col-fini:       90px;
        --col-ffin:       90px;
        --col-hini:       80px;
        --col-hfin:       80px;
        --col-dias:      140px;
        --col-desc:       80px;
        --col-estado:    80px;
        --col-acc:       120px;
      }

      .fbar {
        display: grid;
        grid-template-columns:
          minmax(220px, 320px)
          minmax(180px, 180px)
          minmax(160px, 200px)
          minmax(160px, 200px)
          minmax(70px, 140px)
          minmax(70px, 140px)
          minmax(140px, 180px);
        gap: 12px; align-items: end; background: #fff; padding: 14px 16px;
        border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.08); margin-bottom: 12px;
      }
      @media (max-width: 1150px) { .fbar { grid-template-columns: repeat(3, minmax(200px, 1fr)); } }
      @media (max-width: 720px)  { .fbar { grid-template-columns: repeat(2, minmax(180px, 1fr)); } }

      .f { display:flex; flex-direction:column; gap:6px }
      .f label{ font-size:12px; color:#586168; font-weight:700 }
      .f select, .f input[type="date"], .f input[type="text"], .f input[type="number"]{
        padding: 8px 10px; border:1px solid #d6dadd; border-radius:10px; background:#fff; outline:none;
      }
      .f.tiny input, .f.tiny select { padding:7px 8px; }
      .f.search{ max-width:320px }

      .summary{ margin:8px 2px 12px; color:#475569; font-size:13px }

      table{ width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:12px; overflow:hidden; table-layout:fixed; }
      thead th{ position:sticky; top:0; background:#f8fafc; z-index:1; text-align:left; font-weight:700; padding:10px 12px; font-size:13px; color:#334155; border-bottom:1px solid #e5e7eb; }
      tbody td{ padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
      tbody tr:hover{ background:#f7fbfd; }

      th.col-nombre,  td.col-nombre  { width: var(--col-nombre); }
      th.col-canchas, td.col-canchas { width: var(--col-canchas); }
      th.col-fini,    td.col-fini    { width: var(--col-fini); text-wrap: nowrap; }
      th.col-ffin,    td.col-ffin    { width: var(--col-ffin); text-wrap: nowrap; }
      th.col-hini,    td.col-hini    { width: var(--col-hini); text-wrap: nowrap; }
      th.col-hfin,    td.col-hfin    { width: var(--col-hfin); text-wrap: nowrap; }
      th.col-dias,    td.col-dias    { width: var(--col-dias); }
      th.col-desc,    td.col-desc    { width: var(--col-desc); text-align:right; }
      th.col-estado,  td.col-estado  { width: var(--col-estado); text-align:center; }
      th.col-acc,     td.col-acc     { width: var(--col-acc); text-align:center; }

      .pill{ display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; white-space:nowrap; border:1px solid transparent }
      .pk{ background:#e6f7f4; border-color:#c8efe8; color:#0f766e }   /* Activa */
      .pp{ background:#fff7e6; border-color:#ffe1b5; color:#92400e }   /* Próxima */
      .pb{ background:#fde8e8; border-color:#f8c9c9; color:#7f1d1d }   /* Finalizada */

      .title-text, .desc-text{ white-space:normal; word-wrap:break-word; font-size:14px; color:#0f172a; }
      .sub{ font-size:12px; color:#64748b; margin-top:4px; }

      .btn-mini{appearance:none;border:1px solid #f8c9c9;background:#fde8e8;color:#7f1d1d;border-radius:8px;padding:6px 10px;font-weight:700;cursor:pointer}
      .truncate{display:block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .btn-add{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;text-decoration:none;font-weight:500;font-size:14px;transition:filter .15s ease,transform .03s ease;white-space:nowrap;}
        .btn-add:hover{background:#139488;}    </style>

    <form class="fbar" method="GET" id="promoFilters">
      <div class="f search">
        <label>Buscar</label>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Nombre o descripción">
      </div>
      <div class="f">
        <label>Cancha</label>
        <select name="cancha_id">
          <option value="0" <?= $cancha_id === 0 ? 'selected' : '' ?>>Todas</option>
          <?php foreach ($canchas as $c): ?>
            <option value="<?= (int) $c['cancha_id'] ?>" <?= (int)$c['cancha_id'] === $cancha_id ? 'selected' : '' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Desde</label>
        <input type="date" name="desde" value="<?= h($desde) ?>">
      </div>
      <div class="f">
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?= h($hasta) ?>">
      </div>
      <div class="f tiny">
        <label>% Desc. mín</label>
        <input type="number" step="0.01" name="pct_min" value="<?= h($_GET['pct_min'] ?? '') ?>" placeholder="0">
      </div>
      <div class="f tiny">
        <label>% Desc. máx</label>
        <input type="number" step="0.01" name="pct_max" value="<?= h($_GET['pct_max'] ?? '') ?>" placeholder="100">
      </div>
      <div class="f tiny">
        <label>Estado</label>
        <select name="estado">
          <?php foreach (['vigentes' => 'Activas', 'futuros' => 'Próximas', 'pasados' => 'Finalizadas', 'todas' => 'Todas'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $estado === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <div class="summary"><?= count($rows) ?> promoción(es) encontradas.</div>

    <table>
      <thead>
        <tr>
          <th class="col-nombre">Nombre</th>
          <th class="col-canchas">Canchas afectadas</th>
          <th class="col-fini">Fecha inicio</th>
          <th class="col-ffin">Fecha fin</th>
          <th class="col-hini">Hora inicio</th>
          <th class="col-hfin">Hora fin</th>
          <th class="col-dias">Días</th>
          <th class="col-desc">% Des.</th>
          <th class="col-estado">Estado</th>
          <th class="col-acc">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="10" style="text-align:center;">Sin promociones con esos filtros.</td></tr>
        <?php else: foreach ($rows as $p):
          $estadoTxt = estadoPromoFecha($p, $hoy);
          $dias = diasLindos($p['dias_semana'] ?? null);
          $pill = ['Activa'=>'pk','Próxima'=>'pp','Finalizada'=>'pb'][$estadoTxt] ?? 'pp';
          $canchaTxt = $p['cancha_id'] ? ($p['cancha_nombre'] ?: ('#'.$p['cancha_id'])) : 'Todas las canchas';
          $pct = number_format((float)$p['porcentaje_descuento'], 2, ',', '.').'%';
        ?>
          <tr>
            <td class="col-nombre">
              <div class="title-text"><strong><?= h($p['nombre']) ?></strong></div>
              <?php if (!empty($p['descripcion'])): ?>
                <div class="sub desc-text"><?= nl2br(h($p['descripcion'])) ?></div>
              <?php endif; ?>
            </td>
            <td class="col-canchas"><span class="truncate"><?= h($canchaTxt) ?></span></td>
            <td class="col-fini"><?= h(ddmm($p['fecha_inicio'])) ?></td>
            <td class="col-ffin"><?= h(ddmm($p['fecha_fin'])) ?></td>
            <td class="col-hini"><?= h(hhmm($p['hora_inicio'])) ?></td>
            <td class="col-hfin"><?= h(hhmm($p['hora_fin'])) ?></td>
            <td class="col-dias"><span class="truncate"><?= h($dias) ?></span></td>
            <td class="col-desc"><?= h($pct) ?></td>
            <td class="col-estado">
              <span class="pill <?= $pill ?>"><?= $estadoTxt ?><?= ((int)$p['activa']===1 ? '' : ' (inactiva)') ?></span>
            </td>
            <td class="col-acc">
              <form method="POST" action="promocionesAction.php" onsubmit="return confirm('¿Eliminar promoción?');" style="display:inline-block">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="promocion_id" value="<?= (int)$p['promocion_id'] ?>">
                <button type="submit" class="btn-mini">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
  (function () {
    const f = document.getElementById('promoFilters');
    if (!f) return;
    const submit = () => { if (f.requestSubmit) f.requestSubmit(); else f.submit(); };
    f.querySelectorAll('select,input[type="date"]').forEach(el => el.addEventListener('change', submit));
    f.querySelectorAll('input[type="text"],input[type="number"]').forEach(el => {
      el.addEventListener('keydown', e => { if (e.key === 'Enter') submit(); });
      el.addEventListener('blur', submit);
    });
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
