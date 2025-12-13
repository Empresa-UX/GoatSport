<?php
/* =========================================================================
 * file: admin/partidos.php
 * Vistas:
 *   - Partidos de TORNEO (torneo_id IS NOT NULL)
 *   - Partidos AMISTOSOS (torneo_id IS NULL)
 *
 * Amistosos:
 *   Columnas: ID, Fecha (dd/mm), Hora (HH:MM), Reserva, Jugador1, Jugador2,
 *             Resultado, Ganador
 *   Filtros: Buscar por jugadores, Mes, Día, Ganador (con/sin)
 *
 * Torneos:
 *   Columnas: ID, Nombre torneo, Fecha, Hora, Jugador1, Jugador2,
 *             Resultado, Ganador
 *   Filtros: Buscar por jugadores, Nombre torneo, Mes, Día, Ganador (con/sin)
 *
 * Admin: sin agregar ni editar.
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ddmm(?string $d): string {
  if (!$d) return '—';
  $t = strtotime($d);
  return $t ? date('d/m',$t) : '—';
}

/* === Vista: torneos | amistosos === */
$view  = $_GET['view'] ?? 'torneos';
$valid = ['torneos','amistosos'];
if (!in_array($view,$valid,true)) $view = 'torneos';

/* ======================= PARTIDOS DE TORNEO ======================= */
$partidosTorneo = [];
$torneosLista   = [];

if ($view === 'torneos') {
  $sql = "
    SELECT 
      p.partido_id,
      p.torneo_id,
      t.nombre AS torneo,
      j1.nombre AS jugador1,
      j1.email  AS email1,
      j2.nombre AS jugador2,
      j2.email  AS email2,
      p.fecha,
      p.resultado,
      p.ganador_id,
      g.nombre AS ganador
    FROM partidos p
    JOIN torneos t   ON p.torneo_id   = t.torneo_id
    JOIN usuarios j1 ON p.jugador1_id = j1.user_id
    JOIN usuarios j2 ON p.jugador2_id = j2.user_id
    LEFT JOIN usuarios g ON g.user_id = p.ganador_id
    WHERE p.torneo_id IS NOT NULL
    ORDER BY p.fecha DESC, p.partido_id DESC
  ";
  $res = $conn->query($sql);
  $partidosTorneo = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

  foreach ($partidosTorneo as $row) {
    $torneosLista[$row['torneo_id'].'|'.$row['torneo']] = true;
  }
  ksort($torneosLista, SORT_NATURAL | SORT_FLAG_CASE);
}

/* ======================= PARTIDOS AMISTOSOS ======================= */
$partidosAmistosos = [];

if ($view === 'amistosos') {
  $sql = "
    SELECT
      p.partido_id,
      p.fecha,
      p.reserva_id,
      p.resultado,
      p.ganador_id,
      j1.nombre AS jugador1,
      j1.email  AS email1,
      j2.nombre AS jugador2,
      j2.email  AS email2,
      g.nombre  AS ganador
    FROM partidos p
    JOIN usuarios j1 ON p.jugador1_id = j1.user_id
    JOIN usuarios j2 ON p.jugador2_id = j2.user_id
    LEFT JOIN usuarios g ON g.user_id = p.ganador_id
    WHERE p.torneo_id IS NULL
    ORDER BY p.fecha DESC, p.partido_id DESC
  ";
  $res = $conn->query($sql);
  $partidosAmistosos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}
?>
<div class="section">
  <!-- Header + Tabs -->
  <div class="section-header" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
      <a class="tab <?= $view==='torneos'?'active':'' ?>" href="?view=torneos">De torneo</a>
      <a class="tab <?= $view==='amistosos'?'active':'' ?>" href="?view=amistosos">Amistosos</a>
    </div>
    <!-- Botón Agregar removido -->
    <span></span>
  </div>

  <style>
    :root{ --brand:#0f766e; }

    .tab{
      display:inline-block; padding:6px 10px; border-radius:999px; text-decoration:none;
      background:#fff; border:1px solid #d6dadd; color:#334155; font-weight:600; font-size:13px;
    }
    .tab.active{ background:#1bab9d; color:#fff; border-color:#1bab9d; }

    /* Filtros */
    .fbar{
      display:grid;
      gap:12px;
      align-items:end;
      background:#fff;
      padding:14px 16px;
      border-radius:12px;
      box-shadow:0 4px 12px rgba(0,0,0,.08);
      margin-bottom:12px;
    }
    /* Torneos: Buscar, Torneo, Mes, Día, Ganador */
    .fbar.cols-5{
      grid-template-columns:
        minmax(260px,1fr)
        minmax(200px,220px)
        minmax(120px,140px)
        minmax(120px,140px)
        minmax(160px,180px);
    }
    /* Amistosos: Buscar, Mes, Día, Ganador */
    .fbar.cols-4{
      grid-template-columns:
        minmax(260px,1fr)
        minmax(120px,140px)
        minmax(120px,140px)
        minmax(160px,180px);
    }
    @media (max-width:1100px){ .fbar{ grid-template-columns:repeat(2,minmax(220px,1fr)); } }
    @media (max-width:640px){ .fbar{ grid-template-columns:1fr; } }

    .f{ display:flex; flex-direction:column; gap:6px; }
    .f label{ font-size:12px; color:#586168; font-weight:700; }
    .f input[type="text"], .f select{
      padding:9px 10px;
      border:1px solid #d6dadd;
      border-radius:10px;
      background:#fff;
      outline:none;
    }

    /* Tabla */
    table{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      background:#fff;
      border-radius:12px;
      overflow:hidden;
      table-layout:fixed;
    }
    thead th{
      position:sticky;
      top:0;
      background:#f8fafc;
      z-index:1;
      text-align:left;
      font-weight:700;
      padding:10px 12px;
      font-size:13px;
      color:#334155;
      border-bottom:1px solid #e5e7eb;
    }
    tbody td{
      padding:10px 12px;
      border-bottom:1px solid #f1f5f9;
      vertical-align:top;
    }
    tbody tr:hover{ background:#f7fbfd; }
    .truncate{
      display:block;
      max-width:100%;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .sub{ font-size:12px; color:#64748b; }

    /* === Anchos columnas (sin Acciones) === */
    /* TORNEOS */
    .col-id      { width:50px; }
    .col-torneo  { width:190px; }
    .col-fecha   { width:80px; }
    .col-hora    { width:80px; }
    .col-jug     { width:170px; }
    .col-res     { width:140px; }
    .col-ganador { width:150px; }

    /* AMISTOSOS */
    .col-aid      { width:60px; }
    .col-afecha   { width:80px; }
    .col-ahora    { width:80px; }
    .col-areserva { width:90px; }
    .col-ajug     { width:170px; }
    .col-ares     { width:140px; }
    .col-agan     { width:150px; }

    .pill{
      display:inline-flex; align-items:center; padding:3px 8px; border-radius:999px;
      font-size:12px; font-weight:600; border:1px solid transparent; white-space:nowrap;
    }
    .pill-torneo{ background:#e0f2fe; border-color:#bfdbfe; color:#1d4ed8; }
    .pill-win{ background:#ecfdf3; border-color:#bbf7d0; color:#166534; }
    .pill-reserva{ background:#fef3c7; border-color:#fed7aa; color:#92400e; }

    .row-win{ background:#f5fdf7; }
  </style>

  <!-- Filtros -->
  <?php if ($view === 'torneos'): ?>
    <div class="fbar cols-5" id="filters">
      <div class="f">
        <label>Buscar por jugadores</label>
        <input type="text" id="f-q" placeholder="Jugador 1 / Jugador 2 / Torneo">
      </div>
      <div class="f">
        <label>Nombre del torneo</label>
        <select id="f-torneo">
          <option value="">Todos</option>
          <?php foreach(array_keys($torneosLista) as $key):
            [$tid,$tname] = explode('|',$key,2); ?>
            <option value="<?= (int)$tid ?>"><?= h($tname) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Mes</label>
        <select id="f-mes">
          <option value="">Todos</option>
          <?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Día</label>
        <select id="f-dia">
          <option value="">Todos</option>
          <?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Ganador</label>
        <select id="f-ganador">
          <option value="">Todos</option>
          <option value="con">Con ganador</option>
          <option value="sin">Sin ganador</option>
        </select>
      </div>
    </div>
  <?php else: ?>
    <div class="fbar cols-4" id="filters">
      <div class="f">
        <label>Buscar por jugadores</label>
        <input type="text" id="f-q" placeholder="Jugador 1 / Jugador 2">
      </div>
      <div class="f">
        <label>Mes</label>
        <select id="f-mes">
          <option value="">Todos</option>
          <?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Día</label>
        <select id="f-dia">
          <option value="">Todos</option>
          <?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Ganador</label>
        <select id="f-ganador">
          <option value="">Todos</option>
          <option value="con">Con ganador</option>
          <option value="sin">Sin ganador</option>
        </select>
      </div>
    </div>
  <?php endif; ?>

  <!-- TABLA -->
  <table id="tablaPartidos">
    <thead>
      <?php if ($view === 'torneos'): ?>
        <tr>
          <th class="col-id">ID</th>
          <th class="col-torneo">Nombre del torneo</th>
          <th class="col-fecha">Fecha</th>
          <th class="col-hora">Hora</th>
          <th class="col-jug">Jugador 1</th>
          <th class="col-jug">Jugador 2</th>
          <th class="col-res">Resultado</th>
          <th class="col-ganador">Ganador</th>
        </tr>
      <?php else: ?>
        <tr>
          <th class="col-aid">ID</th>
          <th class="col-afecha">Fecha</th>
          <th class="col-ahora">Hora</th>
          <th class="col-areserva">Reserva</th>
          <th class="col-ajug">Jugador 1</th>
          <th class="col-ajug">Jugador 2</th>
          <th class="col-ares">Resultado</th>
          <th class="col-agan">Ganador</th>
        </tr>
      <?php endif; ?>
    </thead>
    <tbody>
      <?php if ($view === 'torneos'): ?>
        <?php if (empty($partidosTorneo)): ?>
          <tr><td colspan="8" style="text-align:center;">No hay partidos de torneo</td></tr>
        <?php else: foreach ($partidosTorneo as $row):
          $t   = strtotime($row['fecha']);
          $dia = $t ? (int)date('j',$t) : '';
          $mes = $t ? (int)date('n',$t) : '';
          $fecha = ddmm($row['fecha']);
          $hora  = $t ? date('H:i',$t) : '—';
          $ganadorFlag = $row['ganador_id'] ? 'con' : 'sin';

          $texto = mb_strtolower(
            ($row['jugador1'] ?? '') . ' ' .
            ($row['jugador2'] ?? '') . ' ' .
            ($row['torneo']   ?? ''),
            'UTF-8'
          );
          $rowClass = $row['ganador_id'] ? 'row-win' : '';
        ?>
          <tr
            class="<?= $rowClass ?>"
            data-text="<?= h($texto) ?>"
            data-torneo-id="<?= (int)$row['torneo_id'] ?>"
            data-dia="<?= $dia ?>"
            data-mes="<?= $mes ?>"
            data-ganador="<?= $ganadorFlag ?>"
          >
            <td class="col-id"><?= (int)$row['partido_id'] ?></td>
            <td class="col-torneo">
              <span class="pill pill-torneo truncate"><?= h($row['torneo']) ?></span>
            </td>
            <td class="col-fecha"><?= h($fecha) ?></td>
            <td class="col-hora"><?= h($hora) ?></td>
            <td class="col-jug">
              <div class="truncate"><strong><?= h($row['jugador1']) ?></strong></div>
              <?php if (!empty($row['email1'])): ?>
                <div class="sub truncate"><?= h($row['email1']) ?></div>
              <?php endif; ?>
            </td>
            <td class="col-jug">
              <div class="truncate"><strong><?= h($row['jugador2']) ?></strong></div>
              <?php if (!empty($row['email2'])): ?>
                <div class="sub truncate"><?= h($row['email2']) ?></div>
              <?php endif; ?>
            </td>
            <td class="col-res"><?= $row['resultado'] ? h($row['resultado']) : '—' ?></td>
            <td class="col-ganador">
              <?php if ($row['ganador']): ?>
                <span class="pill pill-win truncate"><?= h($row['ganador']) ?></span>
              <?php else: ?>
                <span class="sub">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      <?php else: ?>
        <?php if (empty($partidosAmistosos)): ?>
          <tr><td colspan="8" style="text-align:center;">No hay partidos amistosos</td></tr>
        <?php else: foreach ($partidosAmistosos as $row):
          $t      = strtotime($row['fecha']);
          $fecha  = ddmm($row['fecha']);
          $hora   = $t ? date('H:i',$t) : '—';
          $dia    = $t ? (int)date('j',$t) : '';
          $mes    = $t ? (int)date('n',$t) : '';
          $ganFlag= $row['ganador_id'] ? 'con' : 'sin';

          $texto = mb_strtolower(
            ($row['jugador1'] ?? '') . ' ' .
            ($row['jugador2'] ?? ''),
            'UTF-8'
          );
          $rowClass = $row['ganador_id'] ? 'row-win' : '';
        ?>
          <tr
            class="<?= $rowClass ?>"
            data-text="<?= h($texto) ?>"
            data-dia="<?= $dia ?>"
            data-mes="<?= $mes ?>"
            data-ganador="<?= $ganFlag ?>"
          >
            <td class="col-aid"><?= (int)$row['partido_id'] ?></td>
            <td class="col-afecha"><?= h($fecha) ?></td>
            <td class="col-ahora"><?= h($hora) ?></td>
            <td class="col-areserva">
              <?php if ($row['reserva_id']): ?>
                <span class="pill pill-reserva"># <?= (int)$row['reserva_id'] ?></span>
              <?php else: ?>
                <span class="sub">—</span>
              <?php endif; ?>
            </td>
            <td class="col-ajug">
              <div class="truncate"><strong><?= h($row['jugador1']) ?></strong></div>
              <?php if (!empty($row['email1'])): ?>
                <div class="sub truncate"><?= h($row['email1']) ?></div>
              <?php endif; ?>
            </td>
            <td class="col-ajug">
              <div class="truncate"><strong><?= h($row['jugador2']) ?></strong></div>
              <?php if (!empty($row['email2'])): ?>
                <div class="sub truncate"><?= h($row['email2']) ?></div>
              <?php endif; ?>
            </td>
            <td class="col-ares"><?= $row['resultado'] ? h($row['resultado']) : '—' ?></td>
            <td class="col-agan">
              <?php if ($row['ganador']): ?>
                <span class="pill pill-win truncate"><?= h($row['ganador']) ?></span>
              <?php else: ?>
                <span class="sub">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
/* ===== Filtros en vivo ===== */
(function(){
  const $  = (s,root=document)=>root.querySelector(s);
  const $$ = (s,root=document)=>Array.from(root.querySelectorAll(s));
  const rows = $$('#tablaPartidos tbody tr');
  const params = new URLSearchParams(location.search);
  const view = params.get('view') || 'torneos';
  const norm = s => (s||'').toString().toLowerCase();

  function apply(){
    const q       = norm($('#f-q')?.value);
    const mes     = $('#f-mes')?.value || '';
    const dia     = $('#f-dia')?.value || '';
    const ganador = $('#f-ganador')?.value || '';
    const torneoId = (view === 'torneos') ? ($('#f-torneo')?.value || '') : '';

    rows.forEach(tr=>{
      const vText     = tr.dataset.text || '';
      const vDia      = tr.dataset.dia || tr.getAttribute('data-dia') || '';
      const vMes      = tr.dataset.mes || tr.getAttribute('data-mes') || '';
      const vGanador  = tr.dataset.ganador || tr.getAttribute('data-ganador') || '';
      const vTorneoId = tr.dataset.torneoId || tr.getAttribute('data-torneo-id') || '';

      let show = true;

      show = show && (q   === '' || vText.includes(q));
      show = show && (mes === '' || String(vMes) === String(mes));
      show = show && (dia === '' || String(vDia) === String(dia));
      show = show && (ganador === '' || vGanador === ganador);

      if (view === 'torneos') {
        show = show && (torneoId === '' || vTorneoId === torneoId);
      }

      tr.style.display = show ? '' : 'none';
    });
  }

  const listen = (id,ev='change') => {
    const el = document.querySelector(id);
    if (el) el.addEventListener(ev, apply);
  };

  listen('#f-q','input');
  listen('#f-mes');
  listen('#f-dia');
  listen('#f-ganador');
  if (view === 'torneos') listen('#f-torneo');

  apply();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
