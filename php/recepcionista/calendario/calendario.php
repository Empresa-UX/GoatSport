<?php
/* =========================================================================
 * file: php/recepcionista/calendario/calendario.php  (REEMPLAZO COMPLETO)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include './../includes/cards.php';
include __DIR__ . '/../../config.php';

/* Zona horaria local */
if (function_exists('date_default_timezone_set')) {
  @date_default_timezone_set('America/Argentina/Buenos_Aires');
}

$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);
if ($proveedor_id <= 0) {
  echo "<main><div class='section'><h2>Calendario de reservas</h2><p>Sesión inválida.</p></div></main>";
  include __DIR__ . '/../includes/footer.php'; exit;
}

/* 1) Canchas activas del proveedor */
$sqlCanchas = "SELECT cancha_id, nombre, hora_apertura, hora_cierre, duracion_turno FROM canchas WHERE proveedor_id=? AND activa=1 ORDER BY nombre";
$stmt = $conn->prepare($sqlCanchas);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$canchas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($canchas)) {
  echo "<main><div class='section'><h2>Calendario de reservas</h2><p>No hay canchas activas.</p></div></main>";
  include __DIR__ . '/../includes/footer.php'; exit;
}

/* 2) Filtros + clamp de fecha pasada */
$today       = date('Y-m-d');
$fecha_in    = $_GET['fecha'] ?? $today;
$fecha_norm  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_in) ? $fecha_in : $today;
$fecha       = ($fecha_norm < $today) ? $today : $fecha_norm;

$cancha_id   = (int)($_GET['cancha_id'] ?? $canchas[0]['cancha_id']);
$estado_f    = $_GET['estado']     ?? 'todos';
$desde_f     = $_GET['desde']      ?? '';
$hasta_f     = $_GET['hasta']      ?? '';

/* 3) Cancha seleccionada */
$sqlCancha = "SELECT nombre, hora_apertura, hora_cierre, duracion_turno FROM canchas WHERE cancha_id=? AND proveedor_id=? LIMIT 1";
$stmt = $conn->prepare($sqlCancha);
$stmt->bind_param("ii", $cancha_id, $proveedor_id);
$stmt->execute();
$cancha = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cancha) {
  echo "<main><div class='section'><h2>Calendario de reservas</h2><p>La cancha seleccionada no pertenece a tu club.</p></div></main>";
  include __DIR__ . '/../includes/footer.php'; exit;
}

/* 4) Reservas del día */
$sqlReservas = "
  SELECT r.reserva_id, r.hora_inicio, r.hora_fin, r.estado, r.tipo_reserva,
         lp.estado AS estado_pago
  FROM reservas r
  JOIN canchas c ON c.cancha_id=r.cancha_id AND c.proveedor_id=?
  LEFT JOIN (
    SELECT p1.*
    FROM pagos p1
    JOIN (
      SELECT reserva_id, MAX(pago_id) AS max_id
      FROM pagos
      GROUP BY reserva_id
    ) t ON t.reserva_id = p1.reserva_id AND t.max_id = p1.pago_id
  ) lp ON lp.reserva_id = r.reserva_id
  WHERE r.cancha_id=? AND r.fecha=?
";
$stmt = $conn->prepare($sqlReservas);
$stmt->bind_param("iis", $proveedor_id, $cancha_id, $fecha);
$stmt->execute();
$reservasDB = $stmt->get_result();
$reservas = [];
while ($r = $reservasDB->fetch_assoc()) { $reservas[] = $r; }
$stmt->close();

/* 5) Eventos del día */
$sqlEventos = "
  SELECT evento_id, titulo, fecha_inicio, fecha_fin, tipo, color
  FROM eventos_especiales
  WHERE cancha_id = ?
    AND DATE(fecha_inicio) <= ?
    AND DATE(fecha_fin)   >= ?
";
$stmt = $conn->prepare($sqlEventos);
$stmt->bind_param("iss", $cancha_id, $fecha, $fecha);
$stmt->execute();
$eventosDB = $stmt->get_result();
$eventos = [];
while ($e = $eventosDB->fetch_assoc()) { $eventos[] = $e; }
$stmt->close();

/* ==== Helpers ==== */
function str_to_min(string $hhmmss): int { $p = explode(':', $hhmmss); return (int)$p[0]*60 + (int)$p[1]; }
function clip_range(int $v, int $lo, int $hi): int { return max($lo, min($hi, $v)); }

function default_event_color(string $tipo): string {
  return match ($tipo) {
    'torneo'   => '#8b5cf6',
    'bloqueo'  => '#ef4444',
    'promocion'=> '#10b981',
    default    => '#0ea5e9',
  };
}
function hex2rgba(string $hex, float $alpha=0.12): string {
  $hex = ltrim($hex, '#');
  if (strlen($hex)===3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
  $r = hexdec(substr($hex,0,2));
  $g = hexdec(substr($hex,2,2));
  $b = hexdec(substr($hex,4,2));
  return "rgba($r,$g,$b,$alpha)";
}

/* 6) Rango horario base */
$apertura = $cancha['hora_apertura'] ?: '08:00:00';
$finalDia = $cancha['hora_cierre']   ?: '23:00:00';
$turnoDB  = max(15, (int)($cancha['duracion_turno'] ?: 60));
$SNAP     = 10;

$desde_ok = preg_match('/^\d{2}:\d{2}$/', $desde_f) ? ($desde_f . ':00') : $apertura;
$hasta_ok = preg_match('/^\d{2}:\d{2}$/', $hasta_f) ? ($hasta_f . ':00') : $finalDia;

$openMin   = str_to_min($apertura);
$closeMin  = str_to_min($finalDia);
$viewStart = max(str_to_min($desde_ok), $openMin);
$viewEnd   = min(str_to_min($hasta_ok), $closeMin);
$daySpan   = max(1, $viewEnd - $viewStart);

/* === Bloques === */
$blocks_res = [];
foreach ($reservas as $r) {
  $ini = clip_range(str_to_min($r['hora_inicio']), $viewStart, $viewEnd);
  $fin = clip_range(str_to_min($r['hora_fin']),   $viewStart, $viewEnd);
  if ($fin <= $ini) continue;

  $estado = $r['estado'];
  $pay    = $r['estado_pago'] ?? null;
  $cls = 'res-pend';
  if ($estado === 'confirmada') $cls = ($pay === 'pendiente') ? 'res-conf-pend' : 'res-conf';
  elseif ($estado === 'cancelada' || $estado === 'no_show') $cls = 'res-cancel';

  $blocks_res[] = [
    'type'   => 'reserva',
    'id'     => (int)$r['reserva_id'],
    'estado' => $estado,
    'tipo_reserva' => $r['tipo_reserva'],
    'top'    => ($ini - $viewStart) / $daySpan * 100.0,
    'height' => ($fin - $ini) / $daySpan * 100.0,
    'h_ini'  => substr($r['hora_inicio'],0,5),
    'h_fin'  => substr($r['hora_fin'],0,5),
    'label'  => ucfirst($estado) . ' · ' . substr($r['hora_inicio'],0,5) . '–' . substr($r['hora_fin'],0,5),
    'cls'    => $cls,
    'min_ini'=> $ini,
    'min_fin'=> $fin,
  ];
}

$blocks_evt = [];
foreach ($eventos as $e) {
  $startDayMin = (int)round((strtotime($e['fecha_inicio']) - strtotime($fecha.' 00:00:00'))/60);
  $endDayMin   = (int)round((strtotime($e['fecha_fin'])   - strtotime($fecha.' 00:00:00'))/60);
  $ini = clip_range($startDayMin, $viewStart, $viewEnd);
  $fin = clip_range($endDayMin,   $viewStart, $viewEnd);
  if ($fin <= $ini) continue;

  $color = $e['color'] ?: default_event_color($e['tipo']);

  $blocks_evt[] = [
    'type'   => 'evento',
    'id'     => (int)$e['evento_id'],
    'raw'    => $e['tipo'],
    'color'  => $color,
    'bg'     => hex2rgba($color, 0.12),
    'top'    => ($ini - $viewStart) / $daySpan * 100.0,
    'height' => ($fin - $ini) / $daySpan * 100.0,
    'label'  => ucfirst($e['tipo']).' · '.$e['titulo'],
    'min_ini'=> $ini,
    'min_fin'=> $fin,
  ];
}

/* Rangos ocupados (reserva pend/confirm + bloqueo/torneo) */
$occupied = [];
foreach ($blocks_res as $b) {
  if ($b['estado']==='pendiente' || $b['estado']==='confirmada') $occupied[] = [$b['min_ini'], $b['min_fin']];
}
foreach ($blocks_evt as $b) {
  if ($b['raw']==='bloqueo' || $b['raw']==='torneo') $occupied[] = [$b['min_ini'], $b['min_fin']];
}

/* === Pasado como “evento” oscuro: Horas muertas === */
if ($fecha === $today) {
  $nowMin = (int)date('G')*60 + (int)date('i');
  $nowMinClamped = clip_range($nowMin, $viewStart, $viewEnd);
  if ($nowMinClamped > $viewStart) {
    $occupied[] = [$viewStart, $nowMinClamped]; /* bloqueo real de selección */
    $dead_ini = $viewStart;
    $dead_fin = $nowMinClamped;
    $dead_color = '#111827'; /* slate-900 */
    $blocks_evt[] = [
      'type'   => 'evento',
      'id'     => 0,
      'raw'    => 'horas_muertas', /* no afecta las reglas actuales (solo “torneo” se atenúa en filtro Eventos) */
      'color'  => $dead_color,
      'bg'     => hex2rgba($dead_color, 0.14),
      'top'    => ($dead_ini - $viewStart) / $daySpan * 100.0,
      'height' => ($dead_fin - $dead_ini) / $daySpan * 100.0,
      'label'  => 'Tiempo transcurrido',
      'min_ini'=> $dead_ini,
      'min_fin'=> $dead_fin,
    ];
  }
}

/* === UI === */
?>
<main>
  <div class="section">
    <div class="section-header"><h2>Calendario de reservas</h2></div>

    <style>
      .filtros-box{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;background:#fff;padding:12px 14px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:12px}
      .f{display:flex;flex-direction:column;gap:6px;min-width:160px}
      .f label{font-size:12px;color:#586168;font-weight:700}
      .f select,.f input[type="date"],.f input[type="time"]{padding:8px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
      .f select:focus,.f input[type="date"]:focus,.f input[type="time"]:focus{border-color:#1bab9d;box-shadow:0 0 0 3px rgba(27,171,157,.12)}

      .day-wrap{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:grid;grid-template-columns:80px 1fr}
      .day-wrap > *{ padding-top:50px; padding-bottom:30px; margin-top: 50px; margin-bottom: 50px;}
      .day-times{border-right:1px dashed #e5e7eb;position:relative;margin-bottom:30px}
      .day-grid{position:relative;padding-top:18px;padding-bottom:24px}
      .hr{position:absolute;left:0;right:0;height:1px;background:#eef2f7}
      .hr-label{position:absolute;right:8px;top:-8px;font-size:11px;color:#6b7280}
      .blk{position:absolute;left:8px;right:8px;border-radius:8px;padding:8px 10px;font-size:13px;line-height:1.25;box-shadow:0 2px 6px rgba(0,0,0,.08)}
      .blk.res-pend{background:#fff7e6;border:1px solid #ffe1b5;color:#7a5600}
      .blk.res-conf{background:#fde8e8;border:1px solid #f8c9c9;color:#7f1d1d}
      .blk.res-conf-pend{background:#fff4ce;border:1px solid #ffe08b;color:#7a5b00}
      .blk.res-cancel{background:#f3f4f6;border:1px solid #e5e7eb;color:#6b7280}
      .blk.evt{color:#0f172a;border:1px solid rgba(0,0,0,.08)}
      .blk.draft{background:rgba(27,171,157,.10);border:2px dashed #1bab9d;color:#0f766e;pointer-events:none}
      .blk small{display:block;color:#6b7280;font-size:11px;margin-top:4px}
      .day-hint{padding:8px 10px;color:#6b7280;font-size:12px}
      .day-grid .click-layer{position:absolute;left:0;right:0;top:0;bottom:0;cursor:crosshair}
      .blk a{color:inherit;text-decoration:none;display:block}
      .blk:hover{filter:brightness(.98)}

      .is-dim{opacity:.35; filter:grayscale(12%);}

      .tip{position:absolute;transform:translate(-50%, -120%);background:#009684ff;color:#fff;padding:10px 12px;border-radius:10px;font-size:13px;font-weight:700;white-space:nowrap;pointer-events:none;box-shadow:0 4px 12px #009684ff}
    </style>

    <!-- Filtros -->
    <form id="fForm" class="filtros-box" method="GET">
      <div class="f">
        <label>Cancha</label>
        <select name="cancha_id" onchange="this.form.submit()">
          <?php foreach($canchas as $c): ?>
            <option value="<?= (int)$c['cancha_id'] ?>" <?= $c['cancha_id']==$cancha_id?'selected':'' ?>>
              <?= htmlspecialchars($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Fecha</label>
        <input
          type="date"
          name="fecha"
          value="<?= htmlspecialchars($fecha) ?>"
          min="<?= htmlspecialchars($today) ?>"
          onchange="this.form.submit()">
      </div>
      <div class="f">
        <label>Estado</label>
        <select name="estado" onchange="this.form.submit()">
          <?php
            $opts = ['todos'=>'Todos','reservas'=>'Reservas','eventos'=>'Eventos','torneos'=>'Torneos'];
            foreach ($opts as $k=>$v): ?>
              <option value="<?= $k ?>" <?= $estado_f===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Desde</label>
        <input type="time" name="desde" value="<?= htmlspecialchars($desde_f) ?>" onchange="this.form.submit()">
      </div>
      <div class="f">
        <label>Hasta</label>
        <input type="time" name="hasta" value="<?= htmlspecialchars($hasta_f) ?>" onchange="this.form.submit()">
      </div>
    </form>

    <?php
      $hours = max(1, $daySpan / 60);
      $totalHeight = (int)round($hours * 64);

      $labels = [];
      $t = $viewStart - ($viewStart % 60);
      for (; $t <= $viewEnd; $t += 60) $labels[] = $t;

      function dim_class_for($estado_f, $kind, $rawTipo=null) {
        if ($estado_f === 'todos') return '';
        if ($estado_f === 'reservas') return $kind==='reserva' ? '' : 'is-dim';
        if ($estado_f === 'eventos') {
          if ($kind==='reserva') return 'is-dim';
          return ($rawTipo==='torneo') ? 'is-dim' : '';
        }
        if ($estado_f === 'torneos') {
          if ($kind==='reserva') return 'is-dim';
          return ($rawTipo==='torneo') ? '' : 'is-dim';
        }
        return '';
      }
    ?>
    <div class="day-wrap">
      <div class="day-times" style="height:<?= $totalHeight ?>px;">
        <?php foreach ($labels as $hMin):
          $top = ($hMin - $viewStart) / $daySpan * 100.0;
          $hTxt = sprintf('%02d:00', intdiv($hMin,60));
        ?>
          <div class="hr" style="top:<?= $top ?>%;"></div>
          <div class="hr-label" style="top:calc(<?= $top ?>% - 8px);"><?= $hTxt ?></div>
        <?php endforeach; ?>
      </div>
      <div class="day-grid" id="dayGrid" style="height:<?= $totalHeight ?>px;">

        <!-- Eventos (incluye “Horas muertas”) -->
        <?php foreach ($blocks_evt as $b):
          $dimCls = dim_class_for($estado_f, 'evento', $b['raw']);
        ?>
          <div class="blk evt <?= $dimCls ?>" title="<?= htmlspecialchars($b['label']) ?>"
               style="top:<?= $b['top'] ?>%; height:<?= $b['height'] ?>%; border-left:6px solid <?= htmlspecialchars($b['color']) ?>; background: <?= htmlspecialchars($b['bg']) ?>;">
            <?= htmlspecialchars($b['label']) ?>
          </div>
        <?php endforeach; ?>

        <!-- Reservas -->
        <?php foreach ($blocks_res as $b):
          $href = '../reservas/reservas.php?fecha='.urlencode($fecha)
                . '&cancha_id='.(int)$cancha_id
                . '&hora_desde='.urlencode($b['h_ini'])
                . '&hora_hasta='.urlencode($b['h_fin'])
                . '&focus='.$b['id'];
          $dimCls = dim_class_for($estado_f, 'reserva');
        ?>
          <div class="blk <?= htmlspecialchars($b['cls']) ?> <?= $dimCls ?>" title="<?= htmlspecialchars($b['label']) ?>"
               style="top:<?= $b['top'] ?>%; height:<?= $b['height'] ?>%;">
            <a href="<?= htmlspecialchars($href) ?>">
              <?= htmlspecialchars($b['label']) ?>
              <small><?= $b['tipo_reserva']==='equipo'?'Equipo':'Individual' ?> · #<?= (int)$b['id'] ?></small>
            </a>
          </div>
        <?php endforeach; ?>

        <!-- Capa de selección -->
        <div class="click-layer" data-start="<?= $viewStart ?>" data-span="<?= $daySpan ?>"></div>
      </div>
    </div>

    <script>
      (function(){
        const layer = document.querySelector('.click-layer');
        const grid  = document.getElementById('dayGrid');
        if(!layer || !grid) return;

        const fecha = '<?= addslashes($fecha) ?>';
        const canchaId = <?= (int)$cancha_id ?>;
        const snap = <?= (int)$SNAP ?>;
        const startMin = parseInt(layer.dataset.start,10);
        const spanMin  = parseInt(layer.dataset.span,10);

        const occupied = <?= json_encode($occupied, JSON_NUMERIC_CHECK) ?>;

        function minToHHMM(m){ const h=Math.floor(m/60), mm=m%60; return String(h).padStart(2,'0')+':'+String(mm).padStart(2,'0'); }
        function yToMin(clientY){
          const rect = layer.getBoundingClientRect();
          let rel = (clientY - rect.top);
          if (rel < 0) rel = 0;
          if (rel > rect.height) rel = rect.height;
          const relY = rel / rect.height;
          let m = Math.round(relY * spanMin) + startMin;
          return Math.floor(m / snap) * snap;
        }
        function overlaps(a0,a1,b0,b1){ return !(a1<=b0 || a0>=b1); }
        function intersectsAny(a0,a1){
          for (const [b0,b1] of occupied){ if (overlaps(a0,a1,b0,b1)) return true; }
          return false;
        }
        function insideAny(x){
          for(const [b0,b1] of occupied){ if (x>=b0 && x<b1) return true; }
          return false;
        }
        function clampToWalls(a,b){
          let t=b, dir = (b>=a)? 1 : -1;
          for(const [s,e] of occupied){
            if (t>=s && t<e) t = (dir===1 ? s : e);
          }
          for(const [s,e] of occupied){
            if (dir===1){
              if (a<=s && t>s) t = Math.min(t, s);
            }else{
              if (a>=e && t<e) t = Math.max(t, e);
            }
          }
          return t;
        }

        let dragging=false, m0=0, draft=null, tip=null;
        function ensureDraft(){
          if(!draft){ draft=document.createElement('div'); draft.className='blk draft'; grid.appendChild(draft); }
          if(!tip){ tip=document.createElement('div'); tip.className='tip'; grid.appendChild(tip); }
        }
        function setDraft(mA,mB, clientX, clientY){
          const top = (Math.min(mA,mB) - startMin)/spanMin*100;
          const h   = (Math.max(mA,mB) - Math.min(mA,mB))/spanMin*100;
          draft.style.top = top+'%';
          draft.style.height = Math.max(h, (snap/spanMin*100))+'%';
          const rect = grid.getBoundingClientRect();
          tip.style.left = (clientX - rect.left) + 'px';
          tip.style.top  = (clientY - rect.top) + 'px';
          const ini = Math.min(mA,mB), fin = Math.max(mA,mB);
          tip.textContent = minToHHMM(ini)+' — '+minToHHMM(fin);
        }
        function clearDraft(){ if(draft){ draft.remove(); draft=null; } if(tip){ tip.remove(); tip=null; } }

        layer.addEventListener('contextmenu', (e)=>{ e.preventDefault(); if (dragging){ dragging=false; clearDraft(); } });

        layer.addEventListener('mousedown', (e)=>{
          if (e.button===2) return;
          const mStart = yToMin(e.clientY);
          if (insideAny(mStart)){ e.preventDefault(); return; } // ocupado o “Horas muertas”
          dragging = true;
          ensureDraft();
          m0 = mStart;
          setDraft(m0, m0 + snap, e.clientX, e.clientY);
          e.preventDefault();
        });
        window.addEventListener('mousemove', (e)=>{
          if(!dragging) return;
          let m1 = yToMin(e.clientY);
          m1 = clampToWalls(m0, m1);
          ensureDraft();
          setDraft(m0, m1, e.clientX, e.clientY);
        });
        window.addEventListener('mouseup', (e)=>{
          if(!dragging) return;
          if (e.button===2){ dragging=false; clearDraft(); return; }
          dragging = false;
          let m1 = yToMin(e.clientY);
          m1 = clampToWalls(m0, m1);
          let ini = Math.min(m0, m1), fin = Math.max(m0, m1);
          if (fin <= ini) fin = ini + snap;
          if (intersectsAny(ini, fin)){
            clearDraft();
            alert('El rango seleccionado se superpone con una reserva/evento o pertenece al pasado.');
            return;
          }
          const url = new URL('<?= dirname($_SERVER['REQUEST_URI']) ?>/../reservas/reservasForm.php', window.location.href);
          url.searchParams.set('fecha', fecha);
          url.searchParams.set('cancha_id', String(canchaId));
          url.searchParams.set('hora_inicio', minToHHMM(ini));
          url.searchParams.set('duracion', String(fin - ini));
          window.location.href = url.toString();
          clearDraft();
        });

        window.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' || e.key === 'Esc') {
            if (dragging || draft) {
              dragging = false;
              clearDraft();
              e.preventDefault();
              e.stopPropagation();
            }
          }
        });
      })();
    </script>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
