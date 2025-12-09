<?php
/* =========================================================================
 * file: php/recepcionista/promociones/promociones.php
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';

$proveedor_id = (int) ($_SESSION['proveedor_id'] ?? 0);
if ($proveedor_id <= 0) {
  echo "<main><div class='section'><p>Sesión inválida.</p></div></main>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

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
  $params[] = $cancha_id;
  $types .= "i";
}

if ($q !== '') {
  $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
  $like = '%' . $q . '%';
  $params[] = $like;
  $params[] = $like;
  $types .= "ss";
}
if ($pct_min !== null) {
  $sql .= " AND p.porcentaje_descuento >= ?";
  $params[] = $pct_min;
  $types .= "d";
}
if ($pct_max !== null) {
  $sql .= " AND p.porcentaje_descuento <= ?";
  $params[] = $pct_max;
  $types .= "d";
}

/* Filtro Estado por fecha */
$hoyYmd = $hoy;
if ($estado === 'vigentes') {
  $sql .= " AND p.fecha_inicio <= ? AND p.fecha_fin >= ?";
  $params[] = $hoyYmd;
  $params[] = $hoyYmd;
  $types .= "ss";
} elseif ($estado === 'futuros') {
  $sql .= " AND p.fecha_inicio > ?";
  $params[] = $hoyYmd;
  $types .= "s";
} elseif ($estado === 'pasados') {
  $sql .= " AND p.fecha_fin < ?";
  $params[] = $hoyYmd;
  $types .= "s";
}

$sql .= " ORDER BY p.fecha_inicio ASC, p.fecha_fin ASC, p.promocion_id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ====== Helpers ====== */
function estadoPromoFecha(array $p, string $hoy): string
{
  $ini = $p['fecha_inicio'];
  $fin = $p['fecha_fin'];
  if ($ini <= $hoy && $hoy <= $fin)
    return 'Activa';
  if ($ini > $hoy)
    return 'Próxima';
  return 'Finalizada';
}
function diaNumeroISO(string $dateYmd): int
{
  return (int) date('N', strtotime($dateYmd));
} // 1..7
function diasSetIncluye(?string $set, int $dia): bool
{
  if (!$set || $set === '')
    return true; // null o vacío = todos
  $arr = array_map('trim', explode(',', $set));
  return in_array((string) $dia, $arr, true);
}
function diasLindos(?string $set): string
{
  if (!$set || $set === '')
    return 'Todos';
  $map = ['1' => 'Lun', '2' => 'Mar', '3' => 'Mié', '4' => 'Jue', '5' => 'Vie', '6' => 'Sáb', '7' => 'Dom'];
  $arr = array_map('trim', explode(',', $set));
  $labs = array_map(fn($d) => $map[$d] ?? $d, $arr);
  return implode(', ', $labs);
}
function horaDentro(?string $hIni, ?string $hFin, string $hora): bool
{
  if (!$hIni && !$hFin)
    return true;
  if ($hIni && !$hFin)
    return ($hora >= $hIni);
  if (!$hIni && $hFin)
    return ($hora < $hFin);
  return ($hora >= $hIni && $hora < $hFin);
}
function aplicaHoyRow(array $p, string $hoy, string $now): bool
{
  if (!($p['fecha_inicio'] <= $hoy && $hoy <= $p['fecha_fin']))
    return false;
  if (!diasSetIncluye($p['dias_semana'] ?? null, diaNumeroISO($hoy)))
    return false;
  return horaDentro($p['hora_inicio'] ?? null, $p['hora_fin'] ?? null, $now);
}
?>
<main>
  <div class="section">
    <div class="section-header">
      <h2>Promociones</h2>
    </div>

    <style>
      /* ---- Filtros en UNA LÍNEA (desktop) ---- */
      .fbar {
        display: grid;
        grid-template-columns:
          minmax(220px, 320px)
          /* Buscar (más corto que antes) */
          minmax(180px, 180px)
          /* Cancha */
          minmax(160px, 200px)
          /* Desde */
          minmax(160px, 200px)
          /* Hasta */
          minmax(70px, 140px)
          /* % mín (tiny) */
          minmax(70px, 140px)
          /* % máx (tiny) */
          minmax(140px, 180px);
        /* Estado (tiny) */
        gap: 12px;
        align-items: end;
        background: #fff;
        padding: 14px 16px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
        margin-bottom: 12px;
      }

      @media (max-width: 1150px) {
        .fbar {
          grid-template-columns: repeat(3, minmax(200px, 1fr));
        }
      }

      @media (max-width: 720px) {
        .fbar {
          grid-template-columns: repeat(2, minmax(180px, 1fr));
        }
      }

      .f {
        display: flex;
        flex-direction: column;
        gap: 6px
      }

      .f label {
        font-size: 12px;
        color: #586168;
        font-weight: 700
      }

      .f select,
      .f input[type="date"],
      .f input[type="text"],
      .f input[type="number"] {
        padding: 8px 10px;
        border: 1px solid #d6dadd;
        border-radius: 10px;
        background: #fff;
        outline: none
      }

      /* inputs pequeños */
      .f.tiny input,
      .f.tiny select {
        padding: 7px 8px;
      }

      .f.search {
        max-width: 320px
      }

      /* Buscar un poco más corto */
      .summary {
        margin: 8px 2px 12px;
        color: #475569;
        font-size: 13px
      }

      table {
        width: 100%
      }

      thead th {
        position: sticky;
        top: 0;
        background: #f8fafc;
        z-index: 1
      }

      tbody tr:hover {
        background: #f7fbfd
      }

      .pill {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 12px;
        white-space: nowrap;
        border: 1px solid transparent
      }

      .pk {
        background: #e6f7f4;
        border-color: #c8efe8;
        color: #0f766e
      }

      .pp {
        background: #fff7e6;
        border-color: #ffe1b5;
        color: #995c00
      }

      .pb {
        background: #fde8e8;
        border-color: #f8c9c9;
        color: #7f1d1d
      }

      .pt {
        background: #e0ecff;
        border-color: #bfd7ff;
        color: #1e40af
      }

      /* anchos mínimos de columnas */
      table tr td:nth-child(2) {
        min-width: 170px
      }

      table tr td:nth-child(3) {
        min-width: 160px
      }

      table tr td:nth-child(4) {
        min-width: 150px
      }

      table tr td:nth-child(5) {
        min-width: 140px
      }

      table tr td:nth-child(7) {
        min-width: 110px
      }
    </style>

    <form class="fbar" method="GET" id="promoFilters">
      <!-- Buscar -->
      <div class="f search">
        <label>Buscar</label>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Nombre o descripción">
      </div>

      <!-- Cancha -->
      <div class="f">
        <label>Cancha</label>
        <select name="cancha_id">
          <option value="0" <?= $cancha_id === 0 ? 'selected' : '' ?>>Todas</option>
          <?php foreach ($canchas as $c): ?>
            <option value="<?= (int) $c['cancha_id'] ?>" <?= $c['cancha_id'] === $cancha_id ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Desde / Hasta -->
      <div class="f">
        <label>Desde</label>
        <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
      </div>
      <div class="f">
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
      </div>

      <!-- % Desc. min / max (compactos) -->
      <div class="f tiny">
        <label>% Desc. mín</label>
        <input type="number" step="0.01" name="pct_min" value="<?= htmlspecialchars($_GET['pct_min'] ?? '') ?>"
          placeholder="0">
      </div>
      <div class="f tiny">
        <label>% Desc. máx</label>
        <input type="number" step="0.01" name="pct_max" value="<?= htmlspecialchars($_GET['pct_max'] ?? '') ?>"
          placeholder="100">
      </div>

      <!-- Estado (compacto) -->
      <div class="f tiny">
        <label>Estado</label>
        <select name="estado">
          <?php foreach (['vigentes' => 'Activas', 'futuros' => 'Próximas', 'pasados' => 'Finalizadas', 'todas' => 'Todas'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $estado === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <div class="summary">
      <?= count($rows) ?> promoción(es) encontradas.
    </div>

    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Cancha</th>
          <th>Vigencia</th>
          <th>Ventana horaria</th>
          <th>Días</th>
          <th>% Desc.</th>
          <th>Estado</th>
          <th>Hoy</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (empty($rows)): ?>
          <tr>
            <td colspan="8" style="text-align:center;">Sin promociones con esos filtros.</td>
          </tr>
          <?php
        else:
          foreach ($rows as $p):
            $estadoTxt = estadoPromoFecha($p, $hoy);
            $ini = date('d/m', strtotime($p['fecha_inicio']));
            $fin = date('d/m', strtotime($p['fecha_fin']));
            if ($p['hora_inicio'] && $p['hora_fin']) {
              $hor = substr($p['hora_inicio'], 0, 5) . ' - ' . substr($p['hora_fin'], 0, 5);
            } elseif ($p['hora_inicio'] || $p['hora_fin']) {
              $hor = substr(($p['hora_inicio'] ?? ''), 0, 5) . ' ' . substr(($p['hora_fin'] ?? ''), 0, 5);
            } else {
              $hor = 'Todos el día';
            }
            $dias = diasLindos($p['dias_semana'] ?? null);
            $pill = ['Activa' => 'pk', 'Próxima' => 'pp', 'Finalizada' => 'pb'][$estadoTxt] ?? 'pp';
            $aplica = aplicaHoyRow($p, $hoy, $now);
            ?>
            <tr>
              <td><?= htmlspecialchars($p['nombre']) ?></td>
              <td><?= htmlspecialchars($p['cancha_nombre'] ?? 'Todas') ?></td>
              <td><?= $ini ?> — <?= $fin ?></td>
              <td><?= htmlspecialchars($hor) ?></td>
              <td><?= htmlspecialchars($dias) ?></td>
              <td><?= number_format((float) $p['porcentaje_descuento'], 2) ?>%</td>
              <td><span class="pill <?= $pill ?>"><?= $estadoTxt ?><?= $p['activa'] ? '' : ' (inactiva)' ?></span></td>
              <td>
                <?= $aplica ? '<span class="pill pt">Aplica hoy</span>' : '<span class="pill pb" style="opacity:.6">No</span>' ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
  // autosubmit elegante
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