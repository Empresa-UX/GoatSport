<?php
/* =========================================================================
 * Listado de torneos (Proveedor)
 * - Columnas: Nombre, Inicio, Fin, Estado (dinámico), Tipo, Capacidad, Puntos, Acciones
 * - Acciones: Participantes / Editar / Eliminar (si "en curso" -> solo "Cronograma de partidos")
 * - Filtros: nombre, estado, tipo, inicio(día/mes), fin(día/mes)
 * - Anchos por variables CSS (editables arriba)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../login.php');
  exit;
}

$proveedor_id = (int)$_SESSION['usuario_id'];

function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function ddmm(?string $ymd): string
{
  $t = $ymd ? strtotime($ymd) : 0;
  return $t ? date('d/m', $t) : '—';
}
function estadoClase(string $e): string
{
  $e = strtolower($e);
  return $e === 'abierto' ? 'st-open' : ($e === 'finalizado' ? 'st-done' : ($e === 'en curso' ? 'st-live' : 'st-closed'));
}
function tipoClase(string $t): string
{
  return strtolower($t) === 'individual' ? 'tp-ind' : 'tp-team';
}

/* DATA: solo del proveedor */
$sql = "
  SELECT
    t.torneo_id, t.nombre, t.fecha_inicio, t.fecha_fin, t.estado,
    t.tipo, t.capacidad, t.puntos_ganador, t.proveedor_id,
    IF(p.torneo_id IS NULL, 0, 1) AS ya_programado
  FROM torneos t
  LEFT JOIN (
    SELECT DISTINCT torneo_id
    FROM partidos
  ) p ON p.torneo_id = t.torneo_id
  WHERE t.proveedor_id = ?
  ORDER BY t.fecha_inicio DESC, t.torneo_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

/* Estado dinámico "en curso" según fechas */
$hoy = date('Y-m-d');
foreach ($rows as &$r) {
  $est = strtolower($r['estado'] ?? 'abierto');
  if ($est !== 'finalizado') {
    if ($r['fecha_inicio'] <= $hoy && $hoy <= $r['fecha_fin']) {
      $r['estado_runtime'] = 'en curso';
    } else {
      $r['estado_runtime'] = $est;
    }
  } else {
    $r['estado_runtime'] = 'finalizado';
  }
}
unset($r);
?>
<div class="section">
  <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <h2 style="margin:0;">Torneos</h2>
    <a class="btn-add" href="torneosForm.php">Crear torneo</a>
  </div>

  <style>
    :root {
      /* ==== Editá los anchos de columnas acá ==== */
      --col-nombre: 200px;
      --col-fecha: 60px;
      --col-estado: 80px;
      --col-tipo: 90px;
      --col-cap: 80px;
      --col-pts: 110px;
      --col-acc: 380px;
      /* ========================================= */

      --brand: #0f766e;
    }

    .fbar {
      display: grid;
      gap: 12px;
      align-items: end;
      background: #fff;
      padding: 14px 16px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
      margin: 12px 0;
    }

    .fbar.cols-7 {
      grid-template-columns: minmax(240px, 1fr) repeat(6, minmax(100px, 140px));
    }

    @media (max-width:1100px) {
      .fbar {
        grid-template-columns: repeat(2, minmax(220px, 1fr));
      }
    }

    @media (max-width:640px) {
      .fbar {
        grid-template-columns: 1fr;
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

    .f input[type=text],
    .f select {
      padding: 9px 10px;
      border: 1px solid #d6dadd;
      border-radius: 10px;
      background: #fff;
      outline: none
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      table-layout: fixed
    }

    thead th {
      position: sticky;
      top: 0;
      background: #f8fafc;
      z-index: 1;
      text-align: left;
      font-weight: 700;
      padding: 10px 12px;
      font-size: 13px;
      color: #334155;
      border-bottom: 1px solid #e5e7eb
    }

    tbody td {
      padding: 10px 12px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: top
    }

    tbody tr:hover {
      background: #f7fbfd
    }

    .truncate {
      display: block;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis
    }

    .col-nom {
      width: var(--col-nombre)
    }

    .col-fe {
      width: var(--col-fecha)
    }

    .col-es {
      width: var(--col-estado);
      text-align: center
    }

    .col-ti {
      width: var(--col-tipo);
      text-align: center
    }

    .col-cap {
      width: var(--col-cap)
    }

    .col-pts {
      width: var(--col-pts)
    }

    .col-acc {
      width: var(--col-acc);
      text-align: center
    }

    .pill {
      display: inline-block;
      padding: 4px 9px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      border: 1px solid transparent;
      white-space: nowrap
    }

    .st-open {
      background: #e6f7f4;
      border-color: #c8efe8;
      color: #0f766e
    }

    .st-closed {
      background: #fff7e6;
      border-color: #ffe2b8;
      color: #92400e
    }

    .st-done {
      background: #eef2ff;
      border-color: #c7d2fe;
      color: #3730a3
    }

    .st-live {
      background: #ffeaf2;
      border-color: #ffc8dc;
      color: #b31258
    }

    .tp-team {
      background: #e0ecff;
      border-color: #bfd7ff;
      color: #1e40af
    }

    .tp-ind {
      background: #fde8f1;
      border-color: #f8c7da;
      color: #a11a5b
    }

    .btn-add {
      text-decoration: none;
      font-weight: 500;
      background: #1bab9d;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 6px 12px;
      cursor: pointer;
      font-size: 14px;
      transition: 0.2s ease;
    }

    .btn-add:hover {
      background: #139488;
    }
    
    .btn-action {
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      text-decoration: none;
      font-weight: 700;
      font-size: 14px;
      transition: filter .15s ease, transform .03s ease;
      white-space: nowrap;
      border: 1px solid #bfd7ff;
      background: #e0ecff;
      color: #1e40af;
      border-radius: 10px
    }

    .btn-action.part {
      background: #e6f7f4;
      border: 1px solid #c8efe8;
      color: #0f766e
    }

    .btn-action.plan {
      background: #eef2ff;
      border: 1px solid #c7d2fe;
      color: #3730a3
    }

    .btn-action.edit {
      background: #e0ecff;
      border: 1px solid #bfd7ff;
      color: #1e40af
    }

    .btn-action.delete {
      background: #fde8e8;
      border: 1px solid #f8c9c9;
      color: #7f1d1d
    }

    .actions {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      justify-content: center
    }
  </style>

  <!-- Filtros -->
  <div class="fbar cols-7" id="filters">
    <div class="f"><label>Por nombre</label><input type="text" id="f-q" placeholder="Ej: Copa Verano"></div>
    <div class="f"><label>Estado</label>
      <select id="f-estado">
        <option value="">Todos</option>
        <option value="abierto">Abierto</option>
        <option value="en curso">En curso</option>
        <option value="cerrado">Cerrado</option>
        <option value="finalizado">Finalizado</option>
      </select>
    </div>
    <div class="f"><label>Tipo</label>
      <select id="f-tipo">
        <option value="">Todos</option>
        <option value="individual">Individual</option>
        <option value="equipo">Equipo</option>
      </select>
    </div>
    <div class="f"><label>Inicio (Día)</label><select id="f-i-dia">
        <option value="">Todos</option><?php for ($d = 1; $d <= 31; $d++) echo "<option>$d</option>"; ?>
      </select></div>
    <div class="f"><label>Inicio (Mes)</label><select id="f-i-mes">
        <option value="">Todos</option><?php for ($m = 1; $m <= 12; $m++) echo "<option>$m</option>"; ?>
      </select></div>
    <div class="f"><label>Fin (Día)</label><select id="f-f-dia">
        <option value="">Todos</option><?php for ($d = 1; $d <= 31; $d++) echo "<option>$d</option>"; ?>
      </select></div>
    <div class="f"><label>Fin (Mes)</label><select id="f-f-mes">
        <option value="">Todos</option><?php for ($m = 1; $m <= 12; $m++) echo "<option>$m</option>"; ?>
      </select></div>
  </div>

  <table id="tablaTorneos">
    <thead>
      <tr>
        <th class="col-nom">Nombre del torneo</th>
        <th class="col-fe">Inicio</th>
        <th class="col-fe">Fin</th>
        <th class="col-es">Estado</th>
        <th class="col-ti">Tipo</th>
        <th class="col-cap">Capacidad</th>
        <th class="col-pts">Puntos ganador</th>
        <th class="col-acc">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="8" style="text-align:center;">No hay torneos</td>
        </tr>
        <?php else: foreach ($rows as $r):
          $ini = ddmm($r['fecha_inicio']);
          $fin = ddmm($r['fecha_fin']);
          $iDay = $r['fecha_inicio'] ? (int)date('j', strtotime($r['fecha_inicio'])) : '';
          $iMon = $r['fecha_inicio'] ? (int)date('n', strtotime($r['fecha_inicio'])) : '';
          $fDay = $r['fecha_fin'] ? (int)date('j', strtotime($r['fecha_fin'])) : '';
          $fMon = $r['fecha_fin'] ? (int)date('n', strtotime($r['fecha_fin'])) : '';
          $estado = strtolower($r['estado_runtime'] ?? $r['estado'] ?? 'abierto');
          $tipo = strtolower($r['tipo'] ?? 'equipo');
          $stCls = estadoClase($estado);
          $tpCls = tipoClase($tipo);
          $txt = mb_strtolower(($r['nombre'] ?? ''), 'UTF-8');
          $enCurso = ($estado === 'en curso');
          $yaProgramado = (int)($r['ya_programado'] ?? 0) === 1;
        ?>
          <tr
            data-text="<?= h($txt) ?>"
            data-estado="<?= h($estado) ?>"
            data-tipo="<?= h($tipo) ?>"
            data-i-dia="<?= $iDay ?>" data-i-mes="<?= $iMon ?>" data-f-dia="<?= $fDay ?>" data-f-mes="<?= $fMon ?>">
            <td class="col-nom">
              <div class="truncate"><strong><?= h($r['nombre']) ?></strong></div>
            </td>
            <td class="col-fe"><?= h($ini) ?></td>
            <td class="col-fe"><?= h($fin) ?></td>
            <td class="col-es"><span class="pill <?= $stCls ?>"><?= ucfirst($estado) ?></span></td>
            <td class="col-ti"><span class="pill <?= $tpCls ?>"><?= ucfirst($tipo) ?></span></td>
            <td class="col-cap"><?= (int)($r['capacidad'] ?? 0) ?></td>
            <td class="col-pts"><?= (int)($r['puntos_ganador'] ?? 0) ?></td>
            <td class="col-acc">
              <div class="actions">
                <?php if ($enCurso): ?>
                  <button class="btn-action part"
                    onclick="location.href='torneoCronograma.php?torneo_id=<?= (int)$r['torneo_id'] ?>'">
                    Cronograma de partidos
                  </button>
                <?php else: ?>
                  <button class="btn-action part"
                    onclick="location.href='torneoParticipantes.php?torneo_id=<?= (int)$r['torneo_id'] ?>'">
                    Participantes
                  </button>

                  <button class="btn-action edit"
                    onclick="location.href='torneosForm.php?torneo_id=<?= (int)$r['torneo_id'] ?>'">
                    Editar
                  </button>

                  <form method="POST" action="torneosAction.php"
                    onsubmit="return confirm('¿Eliminar torneo?');"
                    style="display:inline-block">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="torneo_id" value="<?= (int)$r['torneo_id'] ?>">
                    <button type="submit" class="btn-action delete">Eliminar</button>
                  </form>

                  <!-- Nuevo: abre el wizard -->
                <a class="btn-action plan"
                  href="torneoProgramar.php?torneo_id=<?= (int)$r['torneo_id'] ?>">
                  <?= $yaProgramado ? 'Reprogramar' : 'Programar' ?>
                </a>

                <?php endif; ?>
              </div>
            </td>
          </tr>
      <?php endforeach;
      endif; ?>
    </tbody>
  </table>
</div>

<script>
  (function() {
    const $ = s => document.querySelector(s);
    const $$ = s => Array.from(document.querySelectorAll(s));
    const rows = $$('#tablaTorneos tbody tr');
    const norm = s => (s || '').toString().toLowerCase();

    function apply() {
      const q = norm($('#f-q')?.value),
        est = $('#f-estado')?.value || '',
        tipo = $('#f-tipo')?.value || '';
      const iD = $('#f-i-dia')?.value || '',
        iM = $('#f-i-mes')?.value || '',
        fD = $('#f-f-dia')?.value || '',
        fM = $('#f-f-mes')?.value || '';
      rows.forEach(tr => {
        const show =
          (q === '' || (tr.dataset.text || '').includes(q)) &&
          (est === '' || tr.dataset.estado === est) &&
          (tipo === '' || tr.dataset.tipo === tipo) &&
          (iD === '' || String(tr.dataset.iDia) === String(iD)) &&
          (iM === '' || String(tr.dataset.iMes) === String(iM)) &&
          (fD === '' || String(tr.dataset.fDia) === String(fD)) &&
          (fM === '' || String(tr.dataset.fMes) === String(fM));
        tr.style.display = show ? '' : 'none';
      });
    }
    ['#f-q', '#f-estado', '#f-tipo', '#f-i-dia', '#f-i-mes', '#f-f-dia', '#f-f-mes'].forEach(id => {
      const el = document.querySelector(id);
      if (el) el.addEventListener(id === '#f-q' ? 'input' : 'change', apply);
    });
    apply();
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>