<?php
/* =========================================================================
 * FILE: php/cliente/reservas/steps/reservas.php  (REEMPLAZO COMPLETO - CORREGIDO)
 * ========================================================================= */
include './../../includes/header.php';
include './../../../config.php';

if (function_exists('date_default_timezone_set')) {
  @date_default_timezone_set('America/Argentina/Buenos_Aires');
}

/* ======================= AJAX (auto-complete clientes) ======================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clientes') {
  header('Content-Type: application/json; charset=utf-8');
  $out = ['ok' => true, 'data' => []];

  // a) Validación exacta de email existente
  if (isset($_GET['email'])) {
    $email = trim($_GET['email'] ?? '');
    $exists = false;
    if ($email !== '' && preg_match('/^[^@\s]+@gmail\.com$/i', $email)) {
      if ($stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE rol='cliente' AND email = ? LIMIT 1")) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
      }
    }
    echo json_encode(['ok' => true, 'exists' => $exists]);
    exit;
  }

  // b) Búsqueda por texto: prefijo de email (antes del @) y por nombre
  $q = trim($_GET['q'] ?? '');
  if ($q === '') { echo json_encode($out); exit; }

  $qName = $q . '%';

  // ✅ FIX: si el usuario ya escribió "@", NO concatenamos "@gmail.com" de nuevo
  if (strpos($q, '@') !== false) {
    $qEmailPrefix = $q . '%';
  } else {
    $qEmailPrefix = $q . '%@gmail.com';
  }

  $sql = "
    SELECT user_id, nombre, email
    FROM usuarios
    WHERE rol='cliente'
      AND (
         email LIKE CONCAT(?, '')
         OR (email LIKE '%@gmail.com' AND nombre LIKE ?)
      )
    ORDER BY nombre ASC
    LIMIT 10
  ";
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ss", $qEmailPrefix, $qName);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
      if (!preg_match('/@gmail\.com$/i', (string)$r['email'])) continue;
      $out['data'][] = [
        'id'     => (int)$r['user_id'],
        'nombre' => (string)$r['nombre'],
        'email'  => (string)$r['email'],
      ];
    }
    $stmt->close();
  }
  echo json_encode($out);
  exit;
}
/* ======================= FIN AJAX ======================= */

$canchaId = isset($_GET['cancha']) ? (int)$_GET['cancha'] : 0;
if ($canchaId <= 0) { header("Location: reservas_cancha.php"); exit(); }

/* Cancha + pricing */
$stmt = $conn->prepare("
  SELECT cancha_id, nombre, precio, hora_apertura, hora_cierre, duracion_turno, capacidad
  FROM canchas
  WHERE cancha_id = ? AND activa = 1
  LIMIT 1
");
$stmt->bind_param("i", $canchaId);
$stmt->execute();
$cancha = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cancha) { die("Cancha no encontrada o inactiva."); }

$today  = date('Y-m-d');
$fechaIn = $_GET['fecha'] ?? $today;
$fecha  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaIn) ? max($fechaIn, $today) : $today;

/* Helpers */
function str_to_min(string $hhmmss): int { $p = explode(':', $hhmmss); return (int)$p[0]*60 + (int)$p[1]; }
function clip_range(int $v, int $lo, int $hi): int { return max($lo, min($hi, $v)); }
function hex2rgba(string $hex, float $alpha=0.12): string {
  $hex = ltrim($hex, '#');
  if (strlen($hex)===3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
  $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
  return "rgba($r,$g,$b,$alpha)";
}

/* Franja */
$apertura = $cancha['hora_apertura'] ?: '08:00:00';
$cierre   = $cancha['hora_cierre']   ?: '23:00:00';
$turnoMin = max(15, (int)($cancha['duracion_turno'] ?: 60));
$CAP      = (int)$cancha['capacidad'];
$SNAP     = 10;

$openMin  = str_to_min($apertura);
$closeMin = str_to_min($cierre);
$viewStart= $openMin;
$viewEnd  = $closeMin;
$daySpan  = max(1, $viewEnd - $viewStart);

/* Reservas del día */
$sqlR = "SELECT reserva_id, hora_inicio, hora_fin, estado, tipo_reserva FROM reservas WHERE cancha_id=? AND fecha=?";
$stmt = $conn->prepare($sqlR);
$stmt->bind_param("is", $canchaId, $fecha);
$stmt->execute();
$resDB = $stmt->get_result();
$reservas = [];
while ($r = $resDB->fetch_assoc()) $reservas[] = $r;
$stmt->close();

/* Eventos del día */
$sqlE = "
  SELECT evento_id, titulo, fecha_inicio, fecha_fin, tipo, color
  FROM eventos_especiales
  WHERE cancha_id = ? AND DATE(fecha_inicio) <= ? AND DATE(fecha_fin) >= ?
";
$stmt = $conn->prepare($sqlE);
$stmt->bind_param("iss", $canchaId, $fecha, $fecha);
$stmt->execute();
$evtDB = $stmt->get_result();
$eventos = [];
while ($e = $evtDB->fetch_assoc()) $eventos[] = $e;
$stmt->close();

function default_event_color(string $tipo): string {
  return match ($tipo) {
    'torneo'   => '#8b5cf6',
    'bloqueo'  => '#ef4444',
    'promocion'=> '#10b981',
    default    => '#0ea5e9',
  };
}

/* Bloques */
$blocks_res = [];
foreach ($reservas as $r) {
  $ini = clip_range(str_to_min($r['hora_inicio']), $viewStart, $viewEnd);
  $fin = clip_range(str_to_min($r['hora_fin']),   $viewStart, $viewEnd);
  if ($fin <= $ini) continue;
  $estado = $r['estado'];
  $cls = ($estado==='confirmada') ? 'res-conf' : (($estado==='pendiente') ? 'res-pend' : 'res-cancel');
  $blocks_res[] = [
    'type'=>'reserva','id'=>(int)$r['reserva_id'],'estado'=>$estado,'raw'=>'reserva',
    'top'=>($ini-$viewStart)/$daySpan*100.0,'height'=>($fin-$ini)/$daySpan*100.0,
    'h_ini'=>substr($r['hora_inicio'],0,5),'h_fin'=>substr($r['hora_fin'],0,5),
    'label'=>ucfirst($estado).' · '.substr($r['hora_inicio'],0,5).'–'.substr($r['hora_fin'],0,5),
    'min_ini'=>$ini,'min_fin'=>$fin,'tipo_reserva'=>$r['tipo_reserva'],'cls'=>$cls
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
    'type'=>'evento','id'=>(int)$e['evento_id'],'raw'=>$e['tipo'],
    'color'=>$color,'bg'=>hex2rgba($color,0.12),
    'top'=>($ini-$viewStart)/$daySpan*100.0,'height'=>($fin-$ini)/$daySpan*100.0,
    'label'=>ucfirst($e['tipo']).' · '.$e['titulo'],'min_ini'=>$ini,'min_fin'=>$fin
  ];
}

/* Ocupados + pasado */
$occupied = [];
foreach ($blocks_res as $b) if ($b['estado']!=='cancelada' && $b['estado']!=='no_show') $occupied[] = [$b['min_ini'],$b['min_fin']];
foreach ($blocks_evt as $b) if (in_array($b['raw'], ['bloqueo','torneo'], true)) $occupied[] = [$b['min_ini'],$b['min_fin']];
if ($fecha === $today) {
  $nowMin = (int)date('G')*60 + (int)date('i');
  $nowMinClamped = clip_range($nowMin, $viewStart, $viewEnd);
  if ($nowMinClamped > $viewStart) {
    $occupied[] = [$viewStart, $nowMinClamped];
    $dead_ini=$viewStart; $dead_fin=$nowMinClamped; $dead_color='#111827';
    $blocks_evt[] = [
      'type'=>'evento','id'=>0,'raw'=>'horas_muertas','color'=>$dead_color,'bg'=>hex2rgba($dead_color,0.14),
      'top'=>($dead_ini-$viewStart)/$daySpan*100.0,'height'=>($dead_fin-$dead_ini)/$daySpan*100.0,
      'label'=>'Tiempo transcurrido','min_ini'=>$dead_ini,'min_fin'=>$dead_fin
    ];
  }
}

/* Altura total y labels */
$hours = max(1, $daySpan / 60);
$totalHeight = (int)round($hours * 68);
$labels = [];
$t = $viewStart - ($viewStart % 60);
for (; $t <= $viewEnd; $t += 60) $labels[] = $t;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reservar — <?= htmlspecialchars($cancha['nombre']) ?></title>
<style>
  :root{
    --teal-700:#054a56; --teal-600:#07566b; --teal-500:#1bab9d; --ink:#043b3d; --white:#fff;
    --c-res:#fde8e8; --c-res-bd:#f8c9c9; --c-res-tx:#7f1d1d;
    --c-pend:#fff7e6; --c-pend-bd:#ffe1b5; --c-pend-tx:#7a5600;
    --bd:#e5e7eb;
  }

  *, *::before, *::after{ box-sizing:border-box; }
  .reservation-container > *{ min-width:0; }
  .day-wrap, .card{ min-width:0; }
  .fld input, .fld select{ width:100%; max-width:100%; display:block; min-width:0; }

  .page-wrap{ max-width:1200px; margin:0 auto; padding:0 10px; display:flex; flex-direction:column; gap:18px; }
  .flow-header h1{ color:#fff; margin:10px 0 0; font-weight:900; }

  .reservation-container{
    display:grid;
    grid-template-columns:minmax(360px,460px) 1fr;
    gap:22px;
    align-items:stretch;
  }
  @media (max-width:1000px){ .reservation-container{ grid-template-columns:1fr; } }

  .card{
    position:sticky; top:12px;
    max-height:calc(100vh - 24px); overflow:auto;
    background:#fff; border-radius:16px;
    border:1px solid rgba(0,0,0,.06);
    box-shadow:0 16px 40px rgba(0,0,0,.20);
    padding:14px; display:flex; flex-direction:column; gap:12px; z-index:2;
  }
  .card::-webkit-scrollbar{ width:10px; }
  .card::-webkit-scrollbar-thumb{ background:#cfe3e6; border-radius:10px; }
  .card::-webkit-scrollbar-track{ background:#f3f7f8; border-radius:10px; }

  .fld{ display:flex; flex-direction:column; gap:6px; }
  .fld label{ font-size:12px; font-weight:800; color:#557173; text-transform:uppercase; letter-spacing:.2px; }
  .fld input,.fld select{ padding:10px 12px; border:1px solid #e1ecec; border-radius:10px; font-size:14px; outline:none; margin-bottom: 10px}
  .fld input:disabled,.fld select:disabled{ background:#f4f7f7; color:#8aa; }

  .row-3{ display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1fr); gap:10px; }
  .row-3.paywide{ grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1fr); }

  /* ✅ FIX: row-2 para Nombre/Apellido en la misma fila */
  .row-2{
    display:grid;
    grid-template-columns:minmax(0,1fr) minmax(0,1fr);
    gap:10px;
  }
  @media (max-width:420px){
    .row-2{ grid-template-columns:1fr; }
  }

  .chkline{ display:flex; align-items:center; gap:10px; padding:10px 13px; border:1px solid #e1ecec; border-radius:10px; background:#fff; }
  .chkline input{ transform:scale(1.05); width:20px; margin-bottom: 0px}

  .muted{ font-size:12px; color:#6b7280; }
  .promo-box{ border:1px solid var(--bd); border-radius:12px; padding:10px; background:linear-gradient(180deg,#ffffff,#fbfeff); }
  .promo-title{ font-size:13px; font-weight:900; color:#334155; margin-bottom:6px; text-transform:uppercase; letter-spacing:.2px; }
  .promo-item{ font-size:13px; color:#0b6158; background:#e6f7f4; border:1px solid #b7e6de; border-radius:8px; padding:6px 8px; margin-bottom:6px; }
  .promo-empty{ font-size:13px; color:#6b7280; background:#f8fafc; border:1px dashed #e5e7eb; border-radius:8px; padding:8px; }

  .split-wrap{ border:1px dashed #e5e7eb; border-radius:12px; padding:12px; transition:opacity .15s ease; background:linear-gradient(180deg,#fff,#fbfcfc); }
  .split-grid{ display:grid; grid-template-columns:1fr; gap:8px; }

  .part-card{ border:1px solid #e5e7eb; border-radius:12px; padding:10px; background:#fafcfc; position:relative; }
  .part-head{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; }
  .part-title{ font-size:12px; font-weight:900; color:#334155; text-transform:uppercase; letter-spacing:.2px; }
  .part-toggles{ display:flex; gap:12px; flex-wrap:wrap; }
  .part-toggles label{ display:flex; align-items:center; gap:6px; font-size:12px; font-weight:800; color:#557173; user-select:none; }

  .ac-wrap{ position:relative; }
  .ac-list{
    position:absolute; left:0; right:0; top:100%; z-index:10;
    background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-top:6px; max-height:180px; overflow:auto;
    box-shadow:0 14px 30px rgba(0,0,0,.14);
  }
  .ac-item{ padding:9px 10px; cursor:pointer; }
  .ac-item:hover{ background:#f1f6f7; }

  .invite-cta{
    margin-top:8px;
    display:none;
    border:1px solid #e5e7eb;
    background:linear-gradient(180deg,#ffffff,#fbfbfb);
    border-radius:10px;
    padding:10px;
  }
  .invite-cta .line{ display:flex; align-items:center; justify-content:space-between; gap:10px; }
  .invite-cta .msg{ font-size:12px; color:#6b7280; line-height:1.3; }
  .btn-invite{
    appearance:none; border:1px solid #1bab9d; background:#fff; color:#054a56;
    border-radius:10px; font-weight:800; padding:8px 10px; cursor:pointer;
    white-space:nowrap;
  }
  .btn-invite:disabled{ opacity:.6; cursor:not-allowed; }

  .day-wrap{
    background:#fff;
    border:1px solid rgba(0,0,0,.06);
    border-radius:16px;
    box-shadow:0 16px 40px rgba(0,0,0,.10);
    overflow:hidden;
    display:flex;
    flex-direction:column;
    min-height: 0;
  }
  .cal-head{
    padding:12px 14px;
    background:linear-gradient(180deg,#0b6c78,#054a56);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    border-bottom:1px solid rgba(255,255,255,.12);
  }
  .cal-title{
    margin:0;
    font-size:14px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.2px;
    display:flex;
    align-items:center;
    gap:10px;
  }
  .cal-sub{
    font-size:12px;
    opacity:.92;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .day-scroll{
    height:calc(100vh - 24px - 50px);
    overflow:auto;
    scroll-behavior:smooth;
    padding-top: 44px;
    padding-bottom: 44px;
    background:linear-gradient(180deg,#ffffff,#fbfeff);
  }
  @media (max-width:1000px){
    .day-scroll{ height:clamp(520px, 72vh, 760px); }
  }

  .day-inner{ display:grid; grid-template-columns:88px 1fr; position:relative; min-height:100%; }
  .day-times{ position:relative; border-right:1px dashed #e5e7eb; }
  .day-grid { position:relative; }
  .hr{ position:absolute; left:0; right:0; height:1px; background:#eef2f7; }
  .hr-label{ position:absolute; right:8px; top:-8px; font-size:11px; color:#6b7280; }
  .blk{
    position:absolute; left:10px; right:10px;
    border-radius:12px; padding:8px 10px; font-size:13px; line-height:1.25;
    box-shadow:0 6px 14px rgba(0,0,0,.10);
  }
  .blk.res-conf{ background:var(--c-res); border:1px solid var(--c-res-bd); color:var(--c-res-tx); }
  .blk.res-pend{ background:var(--c-pend); border:1px solid var(--c-pend-bd); color:var(--c-pend-tx); }
  .blk.res-cancel{ background:#f3f4f6; border:1px solid #e5e7eb; color:#6b7280; }
  .blk.evt{ color:#0f172a; border:1px solid rgba(0,0,0,.10) }
  .blk.draft{ background:rgba(27,171,157,.10); border:2px dashed #1bab9d; color:#0f766e; pointer-events:none }
  .click-layer{ position:absolute; left:0; right:0; top:0; bottom:0; cursor:crosshair; }
  .tip{ position:absolute; transform:translate(-50%, -120%); background:#009684ff; color:#fff; padding:10px 12px; border-radius:10px; font-size:13px; font-weight:700; white-space:nowrap; pointer-events:none; box-shadow:0 4px 12px #009684ff }

  .footer{ display:grid; grid-template-columns:auto 1fr auto; align-items:center; gap:12px; margin-top:10px; }
  .corner-left{ justify-self:start; } .corner-right{ justify-self:end; }
  .footer .muted{ text-align:center; padding:0 8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

  @media (max-width:420px){
    .footer{ grid-template-columns:1fr 1fr; grid-template-areas:"back next" "price price"; }
    .corner-left{ grid-area:back; } .corner-right{ grid-area:next; }
    .footer .muted{ grid-area:price; text-align:center; }
  }

  .btn{ appearance:none; border:none; border-radius:10px; font-weight:700; padding:10px 14px; cursor:pointer; }
  .btn-outline{ background:#fff; color:#054a56; border:1px solid #1bab9d; text-decoration:none; }
  .btn-next{ color:#fff; background:linear-gradient(180deg,#07566b,#054a56); box-shadow:0 10px 22px rgba(0,0,0,.18); }
  .btn-next:disabled{ opacity:.6; box-shadow:none; cursor:not-allowed; }

  .day-scroll::-webkit-scrollbar{ width:10px; }
  .day-scroll::-webkit-scrollbar-thumb{ background:#cfe3e6; border-radius:10px; }
  .day-scroll::-webkit-scrollbar-track{ background:#f3f7f8; border-radius:10px; }
</style>
</head>
<body>
<div class="page-wrap">
  <div class="flow-header">
    <h1>Reservar — <?= htmlspecialchars($cancha['nombre']) ?></h1>
  </div>

  <div class="reservation-container">
    <!-- LEFT -->
    <div class="card">
      <form method="POST" action="reservas_pago.php" id="formReserva" onsubmit="return validar();">
        <input type="hidden" name="cancha_id" value="<?= (int)$cancha['cancha_id'] ?>">
        <input type="hidden" name="duracion" id="duracion_hidden" value="">

        <div class="row-3">
          <div class="fld">
            <label>Fecha elegida</label>
            <input type="date" name="fecha" id="fecha" value="<?= htmlspecialchars($fecha) ?>" min="<?= htmlspecialchars($today) ?>">
          </div>

          <div class="fld">
            <label>Hora de inicio</label>
            <input type="time" name="hora_inicio" id="hora_inicio" disabled>
          </div>

          <div class="fld">
            <label>Hora a terminar</label>
            <input type="time" id="hora_fin" disabled>
          </div>
        </div>

        <div class="row-3 paywide">
          <div class="fld">
            <label>Duración (min)</label>
            <input type="number" id="duracion_view" readonly>
          </div>

          <div class="fld">
            <label>Precio total</label>
            <input type="number" step="0.01" min="0" name="precio_total" id="precio_total" readonly>
          </div>

          <div class="fld">
            <label>Dividir costos</label>
            <div class="chkline" style="margin-bottom:10px;">
              <input type="checkbox" id="dividir_costos" name="dividir_costos" value="1">
              <label for="dividir_costos" style="margin:0; font-size:12px; font-weight:900; color:#557173; text-transform:uppercase; letter-spacing:.2px;">
              </label>
              <span class="muted" style="margin-left:auto;">(opcional)</span>
            </div>
          </div>
        </div>

        <div class="split-wrap" id="splitWrap" data-disabled="1" style="opacity:.6; pointer-events:none;">
          <div class="muted">
            Capacidad: <?= (int)$cancha['capacidad'] ?> jugadores.
            Individual → 1 persona · Equipo → 3 personas.
          </div>
          <div id="splitGrid" class="split-grid"></div>
        </div>

        <div class="promo-box">
          <div class="promo-title">Promociones aplicadas</div>
          <div id="promosList" class="promo-empty">Sin promociones.</div>
          <div class="row-2" id="promosTotals" style="display:none">
            <span class="promo-item" id="pillBase"></span>
            <span class="promo-item" id="pillFinal"></span>
          </div>
        </div>

        <div class="footer">
          <a href="reservas_cancha.php" class="btn btn-outline corner-left">Volver</a>
          <div class="muted">Precio/hora: $ <?= number_format((float)$cancha['precio'],2,',','.') ?></div>
          <button type="submit" class="btn btn-next corner-right" id="btnSubmit" disabled>Continuar</button>
        </div>
      </form>
    </div>

    <!-- RIGHT -->
    <div class="day-wrap">
      <div class="cal-head">
        <h2 class="cal-title">Calendario de disponibilidad</h2>
        <div class="cal-sub"><?= htmlspecialchars($fecha) ?> · <?= htmlspecialchars($apertura) ?>–<?= htmlspecialchars($cierre) ?></div>
      </div>

      <div class="day-scroll" id="dayScroll">
        <div class="day-inner" style="height:<?= $totalHeight ?>px;">
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
            <?php foreach ($blocks_evt as $b): ?>
              <div class="blk evt"
                   title="<?= htmlspecialchars($b['label']) ?>"
                   style="top:<?= $b['top'] ?>%; height:<?= $b['height'] ?>%; border-left:6px solid <?= htmlspecialchars($b['color']) ?>; background: <?= htmlspecialchars($b['bg']) ?>;">
                <?= htmlspecialchars($b['label']) ?>
              </div>
            <?php endforeach; ?>

            <?php foreach ($blocks_res as $b): ?>
              <div class="blk <?= htmlspecialchars($b['cls']) ?>"
                   title="<?= htmlspecialchars($b['label']) ?>"
                   style="top:<?= $b['top'] ?>%; height:<?= $b['height'] ?>%;">
                <?= htmlspecialchars($b['label']) ?>
              </div>
            <?php endforeach; ?>

            <div class="click-layer" data-start="<?= $viewStart ?>" data-span="<?= $daySpan ?>"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const precioHora = <?= json_encode((float)$cancha['precio']) ?>;
  const CAP = <?= (int)$CAP ?>;
  const SNAP = <?= (int)$SNAP ?>;
  const openMin = <?= (int)$openMin ?>, closeMin = <?= (int)$closeMin ?>;

  const fechaInp = document.getElementById('fecha');
  const iniInp   = document.getElementById('hora_inicio');
  const finInp   = document.getElementById('hora_fin');
  const durView  = document.getElementById('duracion_view');
  const durHidden= document.getElementById('duracion_hidden');
  const submitBtn= document.getElementById('btnSubmit');

  const promosList = document.getElementById('promosList');
  const promosTotals = document.getElementById('promosTotals');
  const pillBase = document.getElementById('pillBase');
  const pillFinal= document.getElementById('pillFinal');
  const outTotal = document.getElementById('precio_total');

  /* ===== Split ===== */
  const splitGrid = document.getElementById('splitGrid');
  const splitWrap = document.getElementById('splitWrap');
  const chkSplit  = document.getElementById('dividir_costos');

  function countParticipants(){ return (CAP >= 4) ? 3 : 1; }

  function esc(s){return (s??'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}

  function createACList(anchor){
    let list = anchor.parentElement.querySelector('.ac-list');
    if (!list) {
      list = document.createElement('div');
      list.className = 'ac-list';
      anchor.parentElement.appendChild(list);
    }
    return list;
  }
  function closeAC(anchor){ const l = anchor.parentElement.querySelector('.ac-list'); if (l) l.remove(); }

  function buildParticipant(i){
    const div = document.createElement('div');
    div.className = 'part-card';
    div.innerHTML = `
      <div class="part-head">
        <div class="part-title">Persona ${i}</div>
        <div class="part-toggles">
          <label><input type="radio" name="p${i}_mode" value="inv"> Invitado</label>
          <label><input type="radio" name="p${i}_mode" value="reg" checked> Registrado</label>
        </div>
      </div>

      <div class="row-2 invited-box" style="display:none;">
        <div class="fld">
          <label>Nombre</label>
          <input type="text" name="part[${i}][first]" class="first" placeholder="Nombre" disabled>
        </div>
        <div class="fld">
          <label>Apellido</label>
          <input type="text" name="part[${i}][last]" class="last" placeholder="Apellido" disabled>
        </div>
      </div>

      <div class="fld registered-box">
        <label>Email registrado (@gmail.com)</label>
        <div class="ac-wrap">
          <input type="email" name="part[${i}][email]" class="email" placeholder="ej: mauricio@gmail.com" inputmode="email" autocomplete="off" disabled>
        </div>

        <div class="invite-cta">
          <div class="line">
            <div class="msg">
              Ese email es válido, pero <strong>no está registrado</strong>.
              Podés invitarlo por email (no queda válido para dividir costos hasta que se registre).
            </div>
            <button type="button" class="btn-invite">Invitar por email</button>
          </div>
        </div>
      </div>
    `;

    const invitedBox = div.querySelector('.invited-box');
    const registeredBox = div.querySelector('.registered-box');
    const first = div.querySelector('.first');
    const last  = div.querySelector('.last');
    const email = div.querySelector('.email');
    const radios = div.querySelectorAll(`input[name="p${i}_mode"]`);
    const inviteBox = div.querySelector('.invite-cta');
    const inviteBtn = div.querySelector('.btn-invite');

    function showInviteCTA(on){
      inviteBox.style.display = on ? '' : 'none';
      inviteBtn.disabled = !on;
    }
    showInviteCTA(false);

    function setEnabled(on){
      [first,last,email].forEach(el => { el.disabled = !on; });
      div.style.opacity = on ? '1' : '0.7';
      if (!on) { showInviteCTA(false); closeAC(email); }
    }
    div.setEnabled = setEnabled;

    function setMode(mode){
      if (mode === 'reg'){
        invitedBox.style.display='none';
        registeredBox.style.display='';
        first.value=''; last.value='';
      } else {
        invitedBox.style.display='';
        registeredBox.style.display='none';
        email.value=''; email.removeAttribute('data-valid');
        showInviteCTA(false);
        closeAC(email);
      }
    }

    radios.forEach(r => r.addEventListener('change', e => setMode(e.target.value)));

    function mailtoInvite(addr){
      const subject = encodeURIComponent('Te invito a registrarte');
      const body = encodeURIComponent(
        'Hola! Te invito a registrarte para poder participar de una reserva.\n\n' +
        'Registrate con este email y después volvemos a dividir costos.\n'
      );
      window.location.href = `mailto:${encodeURIComponent(addr)}?subject=${subject}&body=${body}`;
    }

    inviteBtn.addEventListener('click', ()=>{
      const v = (email.value || '').trim();
      if (!/^.+@gmail\.com$/i.test(v)) return;
      mailtoInvite(v);
    });

    // ✅ FIX: verificar existencia también mientras escribe (y no solo con blur)
    let acT = null, lastQ = '';
    let verifyT = null;

    function verifyEmailExists(addr){
      const v = (addr || '').trim();
      showInviteCTA(false);

      if (!/^.+@gmail\.com$/i.test(v)) {
        email.removeAttribute('data-valid');
        return;
      }

      fetch(`<?= basename(__FILE__) ?>?ajax=clientes&email=${encodeURIComponent(v)}`, {cache:'no-store'})
        .then(r=>r.json()).then(j=>{
          if (j && j.ok && j.exists) {
            email.setAttribute('data-valid','1');
            showInviteCTA(false);
          } else {
            email.removeAttribute('data-valid');
            showInviteCTA(true);
          }
        }).catch(()=>{
          email.removeAttribute('data-valid');
          showInviteCTA(true);
        });
    }

    email.addEventListener('input', (e)=>{
      const q = e.target.value.trim();
      email.removeAttribute('data-valid');
      showInviteCTA(false);
      if (q === '') { closeAC(email); return; }
      if (!/^[\w.\-+@]*$/i.test(q)) return;

      // Autocomplete
      clearTimeout(acT);
      acT = setTimeout(()=>{
        lastQ = q;
        fetch(`<?= basename(__FILE__) ?>?ajax=clientes&q=${encodeURIComponent(q)}`, {cache:'no-store'})
          .then(r=>r.json()).then(j=>{
            if (!j || !j.ok || q !== lastQ) return;
            const list = createACList(email);
            list.innerHTML = '';
            if (!j.data || j.data.length===0){ list.innerHTML = '<div class="ac-item muted">Sin coincidencias</div>'; return; }
            j.data.forEach(it=>{
              const item = document.createElement('div');
              item.className = 'ac-item';
              item.textContent = `${it.nombre} — ${it.email}`;
              item.addEventListener('click', ()=>{
                email.value = it.email;
                email.setAttribute('data-valid','1');
                showInviteCTA(false);
                closeAC(email);
              });
              list.appendChild(item);
            });
          }).catch(()=>{});
      }, 220);

      // Verificación directa (si ya parece email completo)
      clearTimeout(verifyT);
      verifyT = setTimeout(()=> verifyEmailExists(q), 350);
    });

    email.addEventListener('blur', ()=>{
      verifyEmailExists(email.value);
      setTimeout(()=>closeAC(email), 150);
    });

    setMode('reg');
    return div;
  }

  function renderSplit(){
    splitGrid.innerHTML = '';
    const count = countParticipants();
    for (let i=1; i<=count; i++){
      const card = buildParticipant(i);
      card.setEnabled(false);
      splitGrid.appendChild(card);
    }
  }

  function toggleSplit(){
    const on = !!chkSplit.checked;
    splitWrap.dataset.disabled = on ? '0' : '1';
    splitWrap.style.opacity = on ? '1' : '.6';
    splitWrap.style.pointerEvents = on ? 'auto' : 'none';
    splitGrid.querySelectorAll('.part-card').forEach(c=>{
      if (typeof c.setEnabled === 'function') c.setEnabled(on);
    });
  }

  renderSplit();
  chkSplit.addEventListener('change', toggleSplit);
  toggleSplit();

  /* ===== Timeline drag-select ===== */
  const layer = document.querySelector('.click-layer');
  const grid  = document.getElementById('dayGrid');
  if (!layer || !grid) return;

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
    return Math.floor(m / SNAP) * SNAP;
  }
  function overlaps(a0,a1,b0,b1){ return !(a1<=b0 || a0>=b1); }
  function intersectsAny(a0,a1){ for (const [b0,b1] of occupied){ if (overlaps(a0,a1,b0,b1)) return true; } return false; }
  function insideAny(x){ for(const [b0,b1] of occupied){ if (x>=b0 && x<b1) return true; } return false; }
  function clampToWalls(a,b){
    let t=b, dir = (b>=a)? 1 : -1;
    for(const [s,e] of occupied){ if (t>=s && t<e) t = (dir===1 ? s : e); }
    for(const [s,e] of occupied){
      if (dir===1){ if (a<=s && t>s) t = Math.min(t, s); }
      else{ if (a>=e && t<e) t = Math.max(t, e); }
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
    draft.style.height = Math.max(h, (SNAP/spanMin*100))+'%';
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
    if (insideAny(mStart)){ e.preventDefault(); return; }
    dragging = true; ensureDraft(); m0 = mStart; setDraft(m0, m0 + SNAP, e.clientX, e.clientY); e.preventDefault();
  });
  window.addEventListener('mousemove', (e)=>{
    if(!dragging) return;
    let m1 = yToMin(e.clientY);
    m1 = clampToWalls(m0, m1);
    ensureDraft(); setDraft(m0, m1, e.clientX, e.clientY);
  });
  window.addEventListener('mouseup', (e)=>{
    if(!dragging) return;
    if (e.button===2){ dragging=false; clearDraft(); return; }
    dragging = false;
    let m1 = yToMin(e.clientY);
    m1 = clampToWalls(m0, m1);
    let ini = Math.min(m0, m1), fin = Math.max(m0, m1);
    if (fin <= ini) fin = ini + SNAP;
    if (intersectsAny(ini, fin)){
      clearDraft(); alert('El rango seleccionado se superpone con una reserva/evento o pertenece al pasado.'); return;
    }
    iniInp.value = minToHHMM(ini);
    finInp.value = minToHHMM(fin);
    durView.value = String(fin - ini);
    durHidden.value = String(fin - ini);
    iniInp.disabled = false; finInp.disabled=false;
    validateTimes();
    autoPrice();
    clearDraft();
  });

  fechaInp.addEventListener('change', ()=>{
    const url = new URL(window.location.href);
    url.searchParams.set('fecha', fechaInp.value);
    url.searchParams.set('cancha', String(<?= (int)$cancha['cancha_id'] ?>));
    window.location.href = url.toString();
  });

  function autoPrice(){
    const mins = parseInt(durView.value || '0', 10);
    if (!mins || !isFinite(precioHora)) { outTotal.value=''; renderPromos(null); return; }
    const base = precioHora * (mins/60);
    const f = fechaInp.value, h = iniInp.value;
    if (f && h && mins>0) {
      const form = new FormData();
      form.append('action','promos_preview');
      form.append('cancha_id', String(<?= (int)$cancha['cancha_id'] ?>));
      form.append('fecha', f);
      form.append('hora_inicio', h);
      form.append('duracion', String(mins));
      fetch('reservas_pago.php',{method:'POST',body:form})
        .then(r=>r.json()).then(j=>{
          if(!j||!j.ok){ outTotal.value=base.toFixed(2); renderPromos({promos:[],base,final:base}); return; }
          outTotal.value = Number(j.data.precio_final).toFixed(2);
          renderPromos({promos:j.data.promos, base:j.data.precio_base, final:j.data.precio_final});
        }).catch(()=>{ outTotal.value=base.toFixed(2); renderPromos({promos:[],base,final:base}); });
    } else {
      outTotal.value = base.toFixed(2);
      renderPromos({promos:[],base,final:base});
    }
  }

  function renderPromos(d){
    if(!d||!d.promos||d.promos.length===0){
      promosList.className='promo-empty'; promosList.textContent='Sin promociones.'; promosTotals.style.display='none'; return;
    }
    promosList.className='';
    promosList.innerHTML = d.promos.map(p=>`<div class="promo-item"><strong>${esc(p.nombre)}</strong> — ${Number(p.porcentaje_descuento||0).toFixed(2)}% · ahorro $ ${Number(p.ahorro||0).toFixed(2)}</div>`).join('');
    promosTotals.style.display='';
    pillBase.textContent = `Base: $ ${Number(d.base).toFixed(2)}`;
    pillFinal.textContent= `Final: $ ${Number(d.final).toFixed(2)}`;
  }

  function toMin(v){ if(!v||!/^\d{2}:\d{2}$/.test(v)) return NaN; const [h,m]=v.split(':').map(Number); return h*60+m; }
  function validateTimes(){
    const a=toMin(iniInp.value), b=toMin(finInp.value);
    iniInp.setCustomValidity(''); finInp.setCustomValidity('');
    if (Number.isNaN(a) || Number.isNaN(b)){ durView.value=''; durHidden.value=''; submitBtn.disabled=true; return false; }
    if (a >= b){
      durView.value=''; durHidden.value='';
      iniInp.setCustomValidity('La hora inicio debe ser menor que la hora fin.');
      finInp.setCustomValidity('La hora fin debe ser mayor que la hora inicio.');
      submitBtn.disabled = true;
      return false;
    }
    if (a<openMin || b>closeMin){ submitBtn.disabled=true; return false; }
    durView.value = String(b-a); durHidden.value = String(b-a);
    submitBtn.disabled = false;
    iniInp.reportValidity(); finInp.reportValidity();
    return true;
  }

  ;[iniInp, finInp].forEach(el=>{
    el.addEventListener('input', ()=>{ validateTimes(); autoPrice(); });
    el.addEventListener('change', ()=>{ validateTimes(); autoPrice(); });
  });

  // ✅ helper: verificación SINCRÓNICA de existencia (solo para el caso borde de submit inmediato)
  function syncEmailExists(addr){
    try{
      const url = `<?= basename(__FILE__) ?>?ajax=clientes&email=${encodeURIComponent(addr)}`;
      const xhr = new XMLHttpRequest();
      xhr.open('GET', url, false); // sync
      xhr.send(null);
      if (xhr.status >= 200 && xhr.status < 300) {
        const j = JSON.parse(xhr.responseText || '{}');
        return !!(j && j.ok && j.exists);
      }
    }catch(e){}
    return false;
  }

  window.validar = function(){
    if (!validateTimes()){ alert('Revisá los horarios.'); return false; }
    if (!fechaInp.value){ alert('Elegí una fecha.'); return false; }

    if (chkSplit.checked){
      const cards = splitGrid.querySelectorAll('.part-card');
      let idx = 1;
      for (const c of cards){
        const mode = (c.querySelector('input[type=radio][value=reg]')?.checked) ? 'reg' : 'inv';
        if (mode === 'inv'){
          const f = (c.querySelector('.first')?.value || '').trim();
          const l = (c.querySelector('.last')?.value || '').trim();
          if (!f || !l){ alert(`Completar nombre y apellido en Persona ${idx}.`); return false; }
        } else {
          const emailEl = c.querySelector('.email');
          const email = (emailEl?.value || '').trim();
          const okDomain = /@gmail\.com$/i.test(email);
          }
        idx++;
      }
    }
    return true;
  };
})();
</script>

<?php include './../../includes/footer.php'; ?>
</body>
</html>
