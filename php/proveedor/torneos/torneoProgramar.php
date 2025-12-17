<?php
/* =========================================================================
 * Programar partidos (Proveedor) — Manual Partido 1..N
 * (UI limpia + anchos configurables por CSS variables; LÓGICA INTACTA)
 * + Reprogramar: precarga datos si ya existen partidos y al guardar reemplaza programación
 * + VALIDACIÓN NUEVA: orden cronológico (Partido k NO puede ser anterior a Partido k-1)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') { header('Location: ../login.php'); exit; }

$proveedor_id = (int)$_SESSION['usuario_id'];
$torneo_id    = (int)($_GET['torneo_id'] ?? 0);
if ($torneo_id<=0) { header('Location: torneos.php'); exit; }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
const MATCH_MINUTES = 60;

/* ====== Cargar torneo propio (no en curso) ====== */
$ts = $conn->prepare("SELECT * FROM torneos WHERE torneo_id=? AND proveedor_id=? LIMIT 1");
$ts->bind_param("ii",$torneo_id,$proveedor_id);
$ts->execute(); $tres=$ts->get_result(); $torneo=$tres->fetch_assoc(); $ts->close();
if (!$torneo) { header('Location: torneos.php'); exit; }

$hoy = date('Y-m-d');
$runtime = ($torneo['fecha_inicio'] <= $hoy && $hoy <= $torneo['fecha_fin']) ? 'en curso' : strtolower($torneo['estado']??'abierto');
if ($runtime === 'en curso') { header('Location: torneos.php'); exit; }

/* ====== Participantes aceptados (pueden ser < capacidad) ====== */
$cap = max(4, (int)$torneo['capacidad']);
$ps = $conn->prepare("
  SELECT u.user_id, u.nombre
  FROM participaciones p
  INNER JOIN usuarios u ON u.user_id=p.jugador_id
  WHERE p.torneo_id=? AND p.estado='aceptada'
  ORDER BY p.es_creador DESC, u.nombre ASC
  LIMIT ?
");
$ps->bind_param("ii",$torneo_id,$cap);
$ps->execute(); $pr=$ps->get_result(); $participantes=$pr?$pr->fetch_all(MYSQLI_ASSOC):[]; $ps->close();

/* ====== Canchas del proveedor ====== */
$cs = $conn->prepare("SELECT cancha_id, nombre, hora_apertura, hora_cierre FROM canchas WHERE proveedor_id=? AND activa=1 AND estado='aprobado' ORDER BY nombre ASC");
$cs->bind_param("i",$proveedor_id); $cs->execute(); $cr=$cs->get_result();
$canchas=[]; while($c=$cr->fetch_assoc()) $canchas[]=$c; $cs->close();

/* ====== Helpers ====== */
function notificar(mysqli $conn, int $usuario_id, string $tipo, string $titulo, string $mensaje){
  $st = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?, ?, 'sistema', ?, ?)");
  if ($st){ $st->bind_param("isss",$usuario_id,$tipo,$titulo,$mensaje); $st->execute(); $st->close(); }
}
function cancha_slot_libre(mysqli $conn, int $cancha_id, string $fechaYmd, string $hIni, string $hFin): bool {
  $st = $conn->prepare("SELECT 1 FROM reservas WHERE cancha_id=? AND fecha=? AND NOT( hora_fin<=? OR hora_inicio>=? ) LIMIT 1");
  $st->bind_param("isss",$cancha_id,$fechaYmd,$hIni,$hFin);
  $st->execute(); $busy=$st->get_result()->fetch_row(); $st->close();
  if ($busy) return false;

  $st = $conn->prepare("
    SELECT 1 FROM eventos_especiales
    WHERE cancha_id=? AND DATE(fecha_inicio)<=? AND DATE(fecha_fin)>=?
      AND tipo IN ('bloqueo','torneo')
      AND NOT( TIME(fecha_fin)<=? OR TIME(fecha_inicio)>=? )
    LIMIT 1
  ");
  $st->bind_param("issss",$cancha_id,$fechaYmd,$fechaYmd,$hIni,$hFin);
  $st->execute(); $busy2=$st->get_result()->fetch_row(); $st->close();
  return !$busy2;
}

/* ====== Fixture del tamaño exacto de capacidad (sin cambios) ====== */
function generar_fixture_cap(array $participantes, int $cap, int $torneo_id): array {
  $ids = array_map(fn($r)=>(int)$r['user_id'], $participantes);
  mt_srand($torneo_id); shuffle($ids); mt_srand();
  while (count($ids) < $cap) $ids[] = null;

  $rondas = [];
  $r1 = [];
  for ($i=0; $i<$cap; $i+=2) $r1[] = [$ids[$i], $ids[$i+1]];
  $rondas[] = $r1;

  $m = $cap/2;
  while ($m >= 2) { $rondas[] = array_fill(0, $m/2, [null, null]); $m = $m/2; }
  return $rondas;
}

function flatten_partidos(array $rondas): array {
  $out=[]; $k=1;
  foreach($rondas as $rIdx=>$arr){
    foreach($arr as $i=>$par){
      $out[] = ['k'=>$k++, 'ronda'=>$rIdx+1, 'idx'=>$i, 'j1'=>$par[0], 'j2'=>$par[1]];
    }
  }
  return $out;
}

/* ====== Lista Partido 1..N ====== */
$rondas       = generar_fixture_cap($participantes, $cap, $torneo_id);
$partidosList = flatten_partidos($rondas);

/* ====== Detectar reprogramación + precargar datos previos ====== */
$prevByK = [];
$stPrev = $conn->prepare("
  SELECT p.ronda, p.idx_ronda, r.cancha_id, r.fecha, TIME(r.hora_inicio) AS hora_ini
  FROM partidos p
  INNER JOIN reservas r ON r.reserva_id = p.reserva_id
  WHERE p.torneo_id=?
  ORDER BY p.ronda ASC, p.idx_ronda ASC
");
$stPrev->bind_param("i",$torneo_id);
$stPrev->execute(); $rsPrev = $stPrev->get_result();
$partidosPrevios = $rsPrev ? $rsPrev->fetch_all(MYSQLI_ASSOC) : [];
$stPrev->close();

$esReprogramar = !empty($partidosPrevios);
if ($esReprogramar) {
  foreach ($partidosPrevios as $i => $p) {
    $prevByK[$i+1] = $p; // k empieza en 1 y el fetch_all empieza en 0
  }
}

$errors = [];
$warns  = [];

/* ====== POST: guardar programación (LÓGICA INTACTA + ORDEN CRONOLÓGICO) ====== */
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST' && ($_POST['action']??'')==='guardar_programacion') {

  if (!$canchas) $errors[] = "No tenés canchas activas/aprobadas.";
  if (count($participantes) < $cap) {
    $warns[] = "Participantes cargados: ".count($participantes)." de {$cap}. Se crearán partidos con vacantes (NULL).";
  }

  $sel_cancha = $_POST['cancha']   ?? [];
  $sel_fecha  = $_POST['fecha']    ?? [];
  $sel_hini   = $_POST['hora_ini'] ?? [];

  $fi = $torneo['fecha_inicio']; $ff = $torneo['fecha_fin'];

  // Guardamos timestamps de inicio por partido para validar orden al final
  $startsTs = [];

  foreach ($partidosList as $row) {
    $k = $row['k'];
    $cid = (int)($sel_cancha[$k] ?? 0);
    $f   = trim($sel_fecha[$k] ?? '');
    $hi  = trim($sel_hini[$k] ?? '');

    if ($cid<=0) { $errors[] = "Partido {$k}: cancha requerida."; continue; }
    if (!$f)     { $errors[] = "Partido {$k}: fecha requerida."; continue; }
    if ($f < $fi || $f > $ff) { $errors[] = "Partido {$k}: fecha fuera del rango del torneo."; continue; }
    if (!$hi)    { $errors[] = "Partido {$k}: hora desde requerida."; continue; }

    // timestamp inicio (para orden cronológico)
    $tsIni = strtotime($f.' '.$hi);
    if ($tsIni === false) {
      $errors[] = "Partido {$k}: fecha/hora inválida.";
      continue;
    }
    $startsTs[(int)$k] = $tsIni;

    // fin = +60'
    $hf_ts = strtotime($hi)+MATCH_MINUTES*60;
    $hf = date('H:i', $hf_ts);

    // horario operativo cancha
    $cRow = null; foreach($canchas as $c) if ((int)$c['cancha_id']===$cid) { $cRow=$c; break; }
    if ($cRow && $cRow['hora_apertura'] && $cRow['hora_cierre']) {
      $ap = substr($cRow['hora_apertura'],0,5);
      $ci = substr($cRow['hora_cierre'],0,5);
      if (!($hi >= $ap && $hf <= $ci)) {
        $errors[] = "Partido {$k}: fuera del horario de la cancha ({$ap}–{$ci}).";
        continue;
      }
    }

    // colisiones
    $hi_full = $hi.':00';
    $hf_full = $hf.':00';
    if (!cancha_slot_libre($conn, $cid, $f, $hi_full, $hf_full)) {
      $errors[] = "Partido {$k}: la cancha/hora seleccionada está ocupada.";
      continue;
    }
  }

  // ====== VALIDACIÓN NUEVA: Partido k debe ser >= Partido k-1 (fecha/hora) ======
  if (!$errors) {
    $total = count($partidosList);
    for ($k=2; $k<=$total; $k++){
      if (!isset($startsTs[$k-1]) || !isset($startsTs[$k])) continue; // si ya hubo error arriba, no duplicamos
      if ($startsTs[$k] < $startsTs[$k-1]) {
        $errors[] = "Orden inválido: Partido {$k} no puede ser anterior al Partido ".($k-1).".";
      }
    }
  }

  if (!$errors) {

    // ====== Si ya existía programación: borrar (reprogramar) ======
    if ($esReprogramar) {
      // borrar reservas ligadas a partidos del torneo
      $delRes = $conn->prepare("
        DELETE r FROM reservas r
        INNER JOIN partidos p ON p.reserva_id = r.reserva_id
        WHERE p.torneo_id=?
      ");
      $delRes->bind_param("i",$torneo_id);
      $delRes->execute();
      $delRes->close();

      // borrar partidos del torneo
      $delPar = $conn->prepare("DELETE FROM partidos WHERE torneo_id=?");
      $delPar->bind_param("i",$torneo_id);
      $delPar->execute();
      $delPar->close();
    }

    $tipoReserva = ($torneo['tipo']==='individual'?'individual':'equipo');

    $insRes = $conn->prepare("INSERT INTO reservas (cancha_id, creador_id, fecha, hora_inicio, hora_fin, precio_total, tipo_reserva, estado) VALUES (?, ?, ?, ?, ?, 0.00, ?, 'confirmada')");
    $insRes->bind_param("iissss", $cancha_id, $proveedor_id_ref, $fechaYmd, $hIni, $hFin, $tipoR);

    $insPar = $conn->prepare("
      INSERT INTO partidos (torneo_id, ronda, idx_ronda, jugador1_id, jugador2_id, fecha, resultado, ganador_id, reserva_id, next_partido_id, next_pos)
      VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, NULL, NULL)
    ");
    $insPar->bind_param("iiiiisi", $torneo_id_ref, $ronda_ref, $idx_ref, $j1, $j2, $fechaDT, $reserva_id_ref);

    $torneo_id_ref = $torneo_id; $proveedor_id_ref=$proveedor_id; $tipoR=$tipoReserva;
    $partidosIndex = [];

    foreach ($partidosList as $row) {
      $k   = $row['k'];
      $cid = (int)$sel_cancha[$k];
      $f   = $sel_fecha[$k];
      $hi  = $sel_hini[$k];
      $hf  = date('H:i', strtotime($hi)+MATCH_MINUTES*60);

      // reserva
      $cancha_id = $cid;
      $fechaYmd  = $f;
      $hIni      = $hi.':00';
      $hFin      = $hf.':00';
      $insRes->execute(); $reserva_id_ref = $conn->insert_id;

      // partido
      $ronda_ref = (int)$row['ronda'];
      $idx_ref   = (int)$row['idx'];
      $j1 = ($ronda_ref===1) ? ($row['j1'] ?? null) : null;
      $j2 = ($ronda_ref===1) ? ($row['j2'] ?? null) : null;

      $fechaDT = $fechaYmd.' '.$hIni;
      $insPar->execute(); $pid = $conn->insert_id;

      if (!isset($partidosIndex[$ronda_ref])) $partidosIndex[$ronda_ref]=[];
      $partidosIndex[$ronda_ref][$idx_ref] = $pid;

      if ($ronda_ref===1 && $j1 && $j2) {
        $tt = "Partido programado de torneo";
        $msg = "Tu partido del torneo \"{$torneo['nombre']}\" fue programado para el {$fechaYmd} de ".substr($hIni,0,5)." a ".substr($hFin,0,5).".";

        notificar($conn, (int)$j1, 'torneo_partido', $tt, $msg);
        notificar($conn, (int)$j2, 'torneo_partido', $tt, $msg);
      }
    }
    $insRes->close(); $insPar->close();

    // enlace next_*
    $maxR = max(array_keys($partidosIndex));
    $upd = $conn->prepare("UPDATE partidos SET next_partido_id=?, next_pos=? WHERE partido_id=?");
    foreach ($partidosIndex as $r => $arr) {
      if ($r===$maxR) continue;
      foreach ($arr as $i => $pid) {
        $nr = $r+1; $ni = intdiv((int)$i,2);
        if (!isset($partidosIndex[$nr][$ni])) continue;
        $nextPid = (int)$partidosIndex[$nr][$ni];
        $pos = ((int)$i % 2===0) ? 'j1' : 'j2';
        $upd->bind_param("isi", $nextPid, $pos, $pid);
        $upd->execute();
      }
    }
    $upd->close();

    header('Location: torneoCronograma.php?torneo_id='.$torneo_id);
    exit;
  }
}

/* ====== UI — anchos configurables ====== */
?>
<style>
  /* ====== Ajustá estos valores a gusto ====== */
  :root{
    --panel-max: 980px;
    --grid-gap: 10px;

    /* Columnas: partido / cancha / día / hora-desde */
    --col-partido: 80px;
    --col-cancha:  1fr;
    --col-dia:     130px;
    --col-hora:    90px;

    /* Colores sistema (alineado a tu app) */
    --brand:#0f766e;
    --brand-ink:#ffffff;
    --muted-bg:#f1f5f9;
    --muted-ink:#334155;
    --border:#e2e8f0;
  }
  /* ========================================= */

  .panel{background:#fff;border-radius:14px;box-shadow:0 10px 24px rgba(15,23,42,.08);padding:16px;max-width:var(--panel-max);margin:0 auto}
  .panel h2{margin:0 0 10px 0;text-align:center; margin-bottom: 40px;}
  .grid-head{display:grid;grid-template-columns:var(--col-partido) var(--col-cancha) var(--col-dia) var(--col-hora);gap:var(--grid-gap);margin:8px 0;color:#475569;font-size:12px;font-weight:800}
  .grid-row{display:grid;grid-template-columns:var(--col-partido) var(--col-cancha) var(--col-dia) var(--col-hora);gap:var(--grid-gap);align-items:center;margin-bottom:10px}
  @media (max-width:920px){ .grid-head,.grid-row{grid-template-columns:1fr} }
  .lbl{font-weight:700;color:#0f172a}
  select,input[type=date],input[type=time]{padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;width:100%}

  /* ===== Botones (50% / 50% + colores sistema) ===== */
  .actions-2col{display:flex;gap:10px;align-items:center;margin-top:12px;width:100%}
  .actions-2col .btn{
      text-decoration: none;
      width: 50%;
      text-align: center;
      font-weight: 500;
      background: #1bab9d;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 12px;
      cursor: pointer;
      font-size: 16px;
      transition: 0.2s ease;
  }
  .actions-2col .btn:hover {
      background: #139488;
  }

  .btn-primary{background:var(--brand);color:var(--brand-ink)}
  .btn-primary:disabled{opacity:.55;cursor:not-allowed;filter:grayscale(.15)}
  .btn-secondary{background:var(--muted-bg);color:var(--muted-ink);border-color:var(--border)}
  .btn-secondary:hover{filter:brightness(.98)}
</style>

<div class="panel">
  <h2><?= $esReprogramar ? 'Reprogramar partidos' : 'Programar partidos' ?></h2>

  <?php if (!empty($errors)): ?>
    <div style="background:#fff8f0;border:1px solid #fde1bc;border-radius:12px;padding:12px;color:#7c2d12;margin-bottom:12px">
      <strong>Revisá los datos:</strong>
      <ul style="margin:6px 0 0 18px;"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="" id="formProgramar">
    <input type="hidden" name="action" value="guardar_programacion">

    <div class="grid-head">
      <div>Partido</div><div>Cancha</div><div>Día</div><div>Hora desde</div>
    </div>

    <?php
      $minDate = $torneo['fecha_inicio'];
      $maxDate = $torneo['fecha_fin'];
      $defaultStart = '18:00';
      foreach ($partidosList as $row):
        $k = (int)$row['k'];

        $prev = $prevByK[$k] ?? null;
        $valCancha = $prev['cancha_id'] ?? '';
        $valFecha  = $prev['fecha'] ?? '';
        $valHora   = isset($prev['hora_ini']) ? substr((string)$prev['hora_ini'],0,5) : $defaultStart;
    ?>
      <div class="grid-row">
        <div class="lbl">Partido <?= (int)$k ?>:</div>

        <div>
          <select name="cancha[<?= $k ?>]" required data-required="1">
            <option value="">— Elegí cancha —</option>
            <?php foreach ($canchas as $c): ?>
              <option value="<?= (int)$c['cancha_id'] ?>" <?= ((int)$c['cancha_id'] === (int)$valCancha) ? 'selected' : '' ?>>
                <?= h($c['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <input type="date" name="fecha[<?= $k ?>]" min="<?= h($minDate) ?>" max="<?= h($maxDate) ?>" value="<?= h($valFecha) ?>" required data-required="1">
        </div>

        <div>
          <input type="time" name="hora_ini[<?= $k ?>]" value="<?= h($valHora) ?>" required data-required="1">
        </div>
      </div>
    <?php endforeach; ?>

    <div class="actions-2col">
      <button id="btnGuardarProg" type="submit" class="btn btn-primary" disabled>Guardar programación</button>
      <a href="torneos.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<script>
(function(){
  const form = document.getElementById('formProgramar');
  const btnGuardar = document.getElementById('btnGuardarProg');
  if (!form || !btnGuardar) return;

  const TOTAL = <?= (int)count($partidosList) ?>;

  // Habilitar SOLO cuando TODOS los campos requeridos estén completos (en TODAS las filas)
  // + y además el orden cronológico sea válido (k >= k-1)
  const requiredFields = () => Array.from(form.querySelectorAll('[data-required="1"]'));

  function isFilled(el){
    if (el.disabled) return true;
    const v = (el.value ?? '').trim();
    return v !== '' && v !== '0';
  }

  function getStartTs(k){
    const fecha = form.querySelector(`input[name="fecha[${k}]"]`)?.value || '';
    const hini  = form.querySelector(`input[name="hora_ini[${k}]"]`)?.value || '';
    if (!fecha || !hini) return null;
    const ts = Date.parse(`${fecha}T${hini}:00`);
    return Number.isFinite(ts) ? ts : null;
  }

  function isChronologicalOk(){
    for (let k=2; k<=TOTAL; k++){
      const prev = getStartTs(k-1);
      const curr = getStartTs(k);
      if (prev === null || curr === null) return true; // si falta algo, lo maneja "filled"
      if (curr < prev) return false;
    }
    return true;
  }

  function validateAll(){
    const fields = requiredFields();
    const okFilled = fields.length > 0 && fields.every(isFilled);
    const okChrono = okFilled ? isChronologicalOk() : false;
    btnGuardar.disabled = !(okFilled && okChrono);
  }

  // estado inicial
  validateAll();

  form.addEventListener('change', (e) => {
    if (e.target && e.target.matches('[data-required="1"]')) validateAll();
  });
  form.addEventListener('input', (e) => {
    if (e.target && e.target.matches('[data-required="1"]')) validateAll();
  });

  // Tu validación submit (intacta) + agrego orden cronológico
  form.addEventListener('submit', function(e){
    if (btnGuardar.disabled) { e.preventDefault(); return; }

    const errs = [];
    const fi = new Date('<?= h($torneo['fecha_inicio']) ?>T00:00:00');
    const ff = new Date('<?= h($torneo['fecha_fin']) ?>T23:59:59');

    // Validaciones existentes
    <?php foreach ($partidosList as $row): $k=$row['k']; ?>
      (function(){
        const cancha = form.querySelector('select[name="cancha[<?= $k ?>]"]')?.value || '';
        const fecha  = form.querySelector('input[name="fecha[<?= $k ?>]"]')?.value || '';
        const hini   = form.querySelector('input[name="hora_ini[<?= $k ?>]"]')?.value || '';

        if(!cancha) errs.push('Partido <?= $k ?>: cancha requerida.');
        if(!fecha)  errs.push('Partido <?= $k ?>: fecha requerida.');
        if(!hini)   errs.push('Partido <?= $k ?>: hora desde requerida.');
        if (fecha){
          const d = new Date(fecha+'T00:00:00');
          if (d < fi || d > ff) errs.push('Partido <?= $k ?>: fecha fuera de rango.');
        }
      })();
    <?php endforeach; ?>

    // Validación NUEVA: orden cronológico
    for (let k=2; k<=TOTAL; k++){
      const prevTs = getStartTs(k-1);
      const currTs = getStartTs(k);
      if (prevTs !== null && currTs !== null && currTs < prevTs){
        errs.push(`Orden inválido: Partido ${k} no puede ser anterior al Partido ${k-1}.`);
        break; // con 1 alcanza para no spamear
      }
    }

    if (errs.length){
      e.preventDefault();
      alert(errs.join('\n'));
    }
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
