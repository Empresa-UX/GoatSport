<?php
require_once __DIR__ . '/../../config.php';
if (session_status()===PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') {
  header('Location: ../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

function backWith(string $to='torneos.php', array $flashErr=[], array $flashOld=[]){
  if($flashErr) $_SESSION['flash_errors']=$flashErr;
  if($flashOld) $_SESSION['flash_old']=$flashOld;
  header('Location: '.$to); exit;
}

if (($_SERVER['REQUEST_METHOD']??'GET')!=='POST') { http_response_code(405); echo 'Método no permitido'; exit; }

$action = $_POST['action'] ?? '';

/* === Helpers (ídem versión anterior) === */
function validar(array $in, array &$err): bool {
  $err = $err ?? [];
  $nombre = trim($in['nombre'] ?? '');
  $fi     = $in['fecha_inicio'] ?? '';
  $ff     = $in['fecha_fin'] ?? '';
  $tipo   = $in['tipo'] ?? '';
  $cap    = (int)($in['capacidad'] ?? 0);
  $pts    = (int)($in['puntos_ganador'] ?? 0);

  $today = date('Y-m-d');
  $minStart = date('Y-m-d', strtotime('+3 day', strtotime($today)));

  if ($nombre==='') $err[]='Nombre requerido.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$fi)) $err[]='Fecha inicio inválida.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$ff)) $err[]='Fecha fin inválida.';
  if ($fi && $fi < $minStart) $err[]='La fecha de inicio debe ser al menos dentro de 3 días.';
  if ($fi && $ff) {
    $minEnd = date('Y-m-d', strtotime('+7 day', strtotime($fi)));
    $maxEnd = date('Y-m-d', strtotime('+30 day', strtotime($fi)));
    if ($ff < $minEnd) $err[]='La fecha de fin debe ser al menos 7 días después del inicio.';
    if ($ff > $maxEnd) $err[]='La fecha de fin no puede superar 30 días desde el inicio.';
  }
  if (!in_array($tipo, ['individual','equipo'], true)) $err[]='Tipo inválido.';
  if (!in_array($cap, [4,8,16,32,64], true)) $err[]='Capacidad inválida (4, 8, 16, 32, 64).';
  if ($pts < 0) $err[]='Puntos inválidos.';
  return !$err;
}

function own_torneo(mysqli $conn, int $torneo_id, int $proveedor_id): ?array {
  $st = $conn->prepare("SELECT * FROM torneos WHERE torneo_id=? AND proveedor_id=? LIMIT 1");
  $st->bind_param("ii",$torneo_id,$proveedor_id);
  $st->execute(); $res=$st->get_result(); $row=$res?$res->fetch_assoc():null; $st->close();
  return $row;
}

function get_participantes(mysqli $conn, int $torneo_id, int $limit): array {
  $st = $conn->prepare("
    SELECT u.user_id, u.nombre
    FROM participaciones p
    INNER JOIN usuarios u ON u.user_id=p.jugador_id
    WHERE p.torneo_id=? AND p.estado='aceptada'
    ORDER BY p.es_creador DESC, u.nombre ASC
    LIMIT ?
  ");
  $st->bind_param("ii",$torneo_id,$limit);
  $st->execute(); $res=$st->get_result(); $arr=$res?$res->fetch_all(MYSQLI_ASSOC):[]; $st->close();
  return $arr;
}

function get_canchas_disponibles(mysqli $conn, int $proveedor_id): array {
  $q = $conn->prepare("SELECT cancha_id, nombre, hora_apertura, hora_cierre, duracion_turno FROM canchas WHERE proveedor_id=? AND activa=1 AND estado='aprobado' ORDER BY nombre ASC");
  $q->bind_param("i",$proveedor_id); $q->execute(); $res=$q->get_result();
  $out=[]; while($r=$res->fetch_assoc()){ $out[]=$r; } $q->close(); return $out;
}

function cancha_slot_libre(mysqli $conn, int $cancha_id, string $fechaYmd, string $hIni, string $hFin): bool {
  $st = $conn->prepare("
    SELECT 1 FROM reservas
    WHERE cancha_id=? AND fecha=? AND NOT( hora_fin<=? OR hora_inicio>=? )
    LIMIT 1
  ");
  $st->bind_param("isss",$cancha_id, $fechaYmd, $hIni, $hFin);
  $st->execute(); $res=$st->get_result(); $busy = (bool)$res->fetch_row(); $st->close();
  if ($busy) return false;

  $st = $conn->prepare("
    SELECT 1
    FROM eventos_especiales
    WHERE cancha_id=? AND DATE(fecha_inicio) <= ? AND DATE(fecha_fin) >= ?
      AND (tipo IN ('bloqueo','torneo'))
      AND NOT( TIME(fecha_fin)<=? OR TIME(fecha_inicio)>=? )
    LIMIT 1
  ");
  $st->bind_param("issss", $cancha_id, $fechaYmd, $fechaYmd, $hIni, $hFin);
  $st->execute(); $res=$st->get_result(); $busy2=(bool)$res->fetch_row(); $st->close();
  if ($busy2) return false;

  return true;
}

/** fixture KO por rondas */
function generar_fixture(array $jugadores): array {
  $N = count($jugadores);
  $rondas = [];
  $r1 = [];
  for ($i=0; $i<$N; $i+=2) $r1[] = [$jugadores[$i], $jugadores[$i+1]];
  $rondas[] = $r1;
  $m = $N/2;
  while ($m >= 2) { $rondas[] = array_fill(0, $m/2, [null, null]); $m = $m/2; }
  return $rondas;
}

/** Agenda slots para todas las rondas */
function programar_rondas(mysqli $conn, int $proveedor_id, string $fi, string $ff, array $rondas, array $canchas, int $durMin): array {
  if (empty($canchas)) throw new Exception('No tenés canchas aprobadas/activas para programar.');
  $period = new DatePeriod(new DateTime($fi), new DateInterval('P1D'), (new DateTime($ff))->modify('+1 day'));
  $slots = [];
  foreach ($period as $day) {
    $ymd = $day->format('Y-m-d');
    $slots[$ymd] = [];
    foreach ($canchas as $c) {
      $ap = $c['hora_apertura'] ?: '08:00:00';
      $ci = $c['hora_cierre']   ?: '23:00:00';
      $t = strtotime("$ymd $ap");
      $endDay = strtotime("$ymd $ci");
      while ($t + $durMin*60 <= $endDay) {
        $hIni = date('H:i:s', $t);
        $hFin = date('H:i:s', $t + $durMin*60);
        if (cancha_slot_libre($conn, (int)$c['cancha_id'], $ymd, $hIni, $hFin)) {
          $slots[$ymd][] = ['cancha_id'=>(int)$c['cancha_id'], 'hora_inicio'=>$hIni, 'hora_fin'=>$hFin];
        }
        $t += $durMin*60;
      }
    }
  }
  $takeSlot = function() use (&$slots){
    foreach ($slots as $ymd => &$arr) if (!empty($arr)) return [$ymd, array_shift($arr)];
    return [null, null];
  };

  $agenda = [];
  foreach ($rondas as $rIdx => $partidos) {
    for ($pIdx=0; $pIdx<count($partidos); $pIdx++) {
      [$ymd,$slot] = $takeSlot();
      if (!$ymd || !$slot) throw new Exception('No hay slots suficientes en el rango de fechas.');
      $agenda[] = [
        'ronda'=>$rIdx+1,'index'=>$pIdx,
        'fecha'=>$ymd,'cancha_id'=>$slot['cancha_id'],
        'hora_inicio'=>$slot['hora_inicio'],'hora_fin'=>$slot['hora_fin'],
        'j1'=>$partidos[$pIdx][0],'j2'=>$partidos[$pIdx][1]
      ];
    }
  }
  return $agenda;
}

function notificar(mysqli $conn, int $usuario_id, string $tipo, string $titulo, string $mensaje){
  $st = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?, ?, 'sistema', ?, ?)");
  if ($st){ $st->bind_param("isss",$usuario_id,$tipo,$titulo,$mensaje); $st->execute(); $st->close(); }
}

/* ================== ADD ================== */
if ($action === 'add') {
  $in = [
    'nombre'=>$_POST['nombre']??'',
    'fecha_inicio'=>$_POST['fecha_inicio']??'',
    'fecha_fin'=>$_POST['fecha_fin']??'',
    'tipo'=>$_POST['tipo']??'equipo',
    'capacidad'=>$_POST['capacidad']??0,
    'puntos_ganador'=>$_POST['puntos_ganador']??0,
  ];
  $err=[]; if(!validar($in,$err)) backWith('torneosForm.php', $err, $in);

  $creador_id = $proveedor_id;
  $sql="INSERT INTO torneos (nombre,creador_id,proveedor_id,fecha_inicio,fecha_fin,estado,tipo,capacidad,puntos_ganador)
        VALUES (?,?,?,?,?,'abierto',?,?,?)";
  $st=$conn->prepare($sql);
  $st->bind_param("siisssii", $in['nombre'],$creador_id,$proveedor_id,$in['fecha_inicio'],$in['fecha_fin'],$in['tipo'],$in['capacidad'],$in['puntos_ganador']);
  $st->execute(); $st->close();

  backWith('torneos.php');
}

/* ================== EDIT ================== */
if ($action === 'edit') {
  $torneo_id = (int)($_POST['torneo_id']??0);
  $row = own_torneo($conn, $torneo_id, $proveedor_id);
  if (!$row) { http_response_code(404); echo 'No encontrado'; exit; }

  $hoy = date('Y-m-d');
  $estado_runtime = ($row['fecha_inicio'] <= $hoy && $hoy <= $row['fecha_fin']) ? 'en curso' : strtolower($row['estado']??'abierto');
  if ($estado_runtime === 'en curso') backWith('torneos.php', ['No se puede editar un torneo en curso.']);

  $in = [
    'nombre'=>$_POST['nombre']??'',
    'fecha_inicio'=>$_POST['fecha_inicio']??'',
    'fecha_fin'=>$_POST['fecha_fin']??'',
    'tipo'=>$_POST['tipo']??'equipo',
    'capacidad'=>$_POST['capacidad']??0,
    'puntos_ganador'=>$_POST['puntos_ganador']??0,
  ];
  $err=[]; if(!validar($in,$err)) backWith('torneosForm.php?torneo_id='.$torneo_id, $err, $in);

  $sql="UPDATE torneos SET nombre=?, fecha_inicio=?, fecha_fin=?, tipo=?, capacidad=?, puntos_ganador=? WHERE torneo_id=? AND proveedor_id=?";
  $st=$conn->prepare($sql);
  $st->bind_param("ssssiiii", $in['nombre'],$in['fecha_inicio'],$in['fecha_fin'],$in['tipo'],$in['capacidad'],$in['puntos_ganador'],$torneo_id,$proveedor_id);
  $st->execute(); $st->close();

  backWith('torneos.php');
}

/* ================== DELETE ================== */
if ($action === 'delete') {
  $torneo_id = (int)($_POST['torneo_id']??0);
  $row = own_torneo($conn, $torneo_id, $proveedor_id);
  if (!$row) { http_response_code(404); echo 'No encontrado'; exit; }

  $hoy = date('Y-m-d');
  $estado_runtime = ($row['fecha_inicio'] <= $hoy && $hoy <= $row['fecha_fin']) ? 'en curso' : strtolower($row['estado']??'abierto');
  if ($estado_runtime === 'en curso') backWith('torneos.php', ['No se puede eliminar un torneo en curso.']);

  $nombre = (string)$row['nombre'];
  $fi_lbl = date('d/m', strtotime($row['fecha_inicio']));
  $ff_lbl = date('d/m', strtotime($row['fecha_fin']));

  $st=$conn->prepare("DELETE FROM torneos WHERE torneo_id=? AND proveedor_id=?");
  $st->bind_param("ii",$torneo_id,$proveedor_id); $st->execute(); $st->close();

  $ps = $conn->prepare("
    SELECT DISTINCT u.user_id
    FROM participaciones p INNER JOIN usuarios u ON u.user_id=p.jugador_id
    WHERE p.torneo_id=? AND p.estado='aceptada'
  ");
  $ps->bind_param("i",$torneo_id); $ps->execute(); $pr=$ps->get_result();
  while($u=$pr->fetch_assoc()){
    notificar($conn,(int)$u['user_id'],'torneo_cancelado',"Torneo cancelado: {$nombre}","El torneo \"{$nombre}\" (del {$fi_lbl} al {$ff_lbl}) fue cancelado por el club.");
  }
  $ps->close();

  backWith('torneos.php');
}

/* ================== PROGRAMAR ================== */
if ($action === 'programar') {
  $torneo_id = (int)($_POST['torneo_id']??0);
  $torneo = own_torneo($conn, $torneo_id, $proveedor_id);
  if (!$torneo) { http_response_code(404); echo 'No encontrado'; exit; }

  $hoy = date('Y-m-d');
  $runtime = ($torneo['fecha_inicio'] <= $hoy && $hoy <= $torneo['fecha_fin']) ? 'en curso' : strtolower($torneo['estado']??'abierto');
  if ($runtime === 'en curso') backWith('torneos.php', ['No se puede programar un torneo que ya está en curso.']);

  $cap = (int)$torneo['capacidad'];
  $tipoReserva = ($torneo['tipo']==='individual'?'individual':'equipo');

  // Participantes
  $participantes = get_participantes($conn, $torneo_id, $cap);
  if (count($participantes) < $cap) backWith('torneos.php', ["Necesitás {$cap} participantes aceptados. Hay ".count($participantes)."."]);

  // Canchas
  $canchas = get_canchas_disponibles($conn, $proveedor_id);
  if (empty($canchas)) backWith('torneos.php', ['No tenés canchas activas y aprobadas para programar.']);

  // Duración partido (min): 2 turnos mínimo o 90' (lo mayor)
  $durMin = 90; foreach ($canchas as $c) $durMin = max($durMin, (int)$c['duracion_turno']*2);

  // Seed reproducible
  mt_srand($torneo_id); shuffle($participantes); mt_srand();
  $players = array_map(fn($r)=> (int)$r['user_id'], $participantes);

  // Fixture por rondas (array de rondas; R1 con jugadores, resto placeholders)
  $rondas = generar_fixture($players); // [[ [j1,j2], ... ], [ [null,null]... ], ... ]

  // Agenda (slots de canchas y horas)
  try {
    $agenda = programar_rondas($conn, $proveedor_id, $torneo['fecha_inicio'], $torneo['fecha_fin'], $rondas, $canchas, $durMin);
  } catch (Exception $ex) { backWith('torneos.php', [$ex->getMessage()]); }

  // Insertar reservas + partidos con ronda/idx_ronda (sin next_* por ahora)
  $insRes = $conn->prepare("INSERT INTO reservas (cancha_id, creador_id, fecha, hora_inicio, hora_fin, precio_total, tipo_reserva, estado) VALUES (?, ?, ?, ?, ?, 0.00, ?, 'confirmada')");
  $insRes->bind_param("iissss", $cancha_id, $proveedor_id_ref, $fechaYmd, $hIni, $hFin, $tipoR);

  $insPar = $conn->prepare("
    INSERT INTO partidos (torneo_id, ronda, idx_ronda, jugador1_id, jugador2_id, fecha, resultado, ganador_id, reserva_id)
    VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?)
  ");
  $insPar->bind_param("iiiiisi", $torneo_id_ref, $ronda_ref, $idx_ref, $j1, $j2, $fechaDT, $reserva_id_ref);

  $torneo_id_ref = $torneo_id;
  $proveedor_id_ref = $proveedor_id;
  $tipoR = $tipoReserva;

  // indice: $partidosIndex[ r ][ i ] = partido_id
  $partidosIndex = [];
  foreach ($agenda as $a) {
    $cancha_id = (int)$a['cancha_id'];
    $fechaYmd  = $a['fecha'];
    $hIni      = $a['hora_inicio'];
    $hFin      = $a['hora_fin'];
    $j1        = (int)$a['j1'];
    $j2        = (int)$a['j2'];
    $ronda_ref = (int)$a['ronda'];   // 1..N
    $idx_ref   = (int)$a['index'];   // 0..m-1

    $insRes->execute();
    $reserva_id_ref = $conn->insert_id;

    $fechaDT = $fechaYmd.' '.$hIni;
    $insPar->execute();
    $pid = $conn->insert_id;

    if (!isset($partidosIndex[$ronda_ref])) $partidosIndex[$ronda_ref]=[];
    $partidosIndex[$ronda_ref][$idx_ref] = $pid;

    // Notificar solo R1 (cuando hay j1/j2)
    if ($ronda_ref === 1 && $j1 && $j2) {
      $tt = "Partido programado de torneo";
      $msg = "Tu partido del torneo \"{$torneo['nombre']}\" fue programado para el {$fechaYmd} de ".substr($hIni,0,5)." a ".substr($hFin,0,5).".";
      notificar($conn, $j1, 'torneo_partido', $tt, $msg);
      notificar($conn, $j2, 'torneo_partido', $tt, $msg);
    }
  }
  $insRes->close(); $insPar->close();

  // Completar next_partido_id / next_pos
  // Para cada partido (r,i) => next es (r+1, floor(i/2)); pos = 'j1' si i par, 'j2' si impar
  $maxR = max(array_keys($partidosIndex));
  $upd = $conn->prepare("UPDATE partidos SET next_partido_id=?, next_pos=? WHERE partido_id=?");
  foreach ($partidosIndex as $r => $arr) {
    if ($r === $maxR) continue; // la final no tiene siguiente
    foreach ($arr as $i => $pid) {
      $nextR = $r + 1;
      $nextI = intdiv((int)$i, 2);
      if (!isset($partidosIndex[$nextR][$nextI])) continue;
      $nextPid = (int)$partidosIndex[$nextR][$nextI];
      $pos = ((int)$i % 2 === 0) ? 'j1' : 'j2';
      $upd->bind_param("isi", $nextPid, $pos, $pid);
      $upd->execute();
    }
  }
  $upd->close();

  backWith('torneos.php');
}

if ($action === 'programar_commit') {
  $torneo_id = (int)($_POST['torneo_id']??0);
  if ($torneo_id<=0) backWith('torneos.php', ['Torneo inválido.']);

  $torneo = own_torneo($conn, $torneo_id, $proveedor_id);
  if (!$torneo) { http_response_code(404); echo 'No encontrado'; exit; }

  $hoy = date('Y-m-d');
  $runtime = ($torneo['fecha_inicio'] <= $hoy && $hoy <= $torneo['fecha_fin']) ? 'en curso' : strtolower($torneo['estado']??'abierto');
  if ($runtime === 'en curso') backWith('torneos.php', ['No se puede programar un torneo que ya está en curso.']);

  // agenda creada por el preview (60’ ya aplicado allí)
  $wiz = $_SESSION['wizard_agenda'] ?? null;
  if (!$wiz || (int)($wiz['torneo_id']??0)!==$torneo_id) backWith('torneoProgramar.php?torneo_id='.$torneo_id, ['Repetí el preview antes de confirmar.']);
  $agenda = $wiz['agenda'] ?? [];
  if (!$agenda) backWith('torneoProgramar.php?torneo_id='.$torneo_id, ['Agenda vacía.']);

  // Revalidar participantes exactos
  $cap = (int)$torneo['capacidad'];
  $ps = $conn->prepare("
    SELECT u.user_id
    FROM participaciones p INNER JOIN usuarios u ON u.user_id=p.jugador_id
    WHERE p.torneo_id=? AND p.estado='aceptada'
    ORDER BY p.es_creador DESC, u.nombre ASC
    LIMIT ?
  ");
  $ps->bind_param("ii",$torneo_id,$cap); $ps->execute(); $pr=$ps->get_result();
  $participantes=$pr?$pr->fetch_all(MYSQLI_ASSOC):[]; $ps->close();
  if (count($participantes) < $cap) backWith('torneoProgramar.php?torneo_id='.$torneo_id, ["Necesitás {$cap} participantes aceptados. Hay ".count($participantes)."."]);

  // fixture para conocer j1/j2 de R1
  mt_srand($torneo_id); $tmp=$participantes; shuffle($tmp); mt_srand();
  $players = array_map(fn($r)=> (int)$r['user_id'], $tmp);
  $genFixture = function(array $jugadores){
    $N = count($jugadores); $rondas=[]; $r1=[];
    for($i=0;$i<$N;$i+=2){ $r1[] = [$jugadores[$i], $jugadores[$i+1]]; }
    $rondas[]=$r1; $m=$N/2;
    while($m>=2){ $rondas[] = array_fill(0,$m/2,[null,null]); $m=$m/2; }
    return $rondas;
  };
  $rondas = $genFixture($players);

  $tipoReserva = ($torneo['tipo']==='individual'?'individual':'equipo');

  $insRes = $conn->prepare("INSERT INTO reservas (cancha_id, creador_id, fecha, hora_inicio, hora_fin, precio_total, tipo_reserva, estado) VALUES (?, ?, ?, ?, ?, 0.00, ?, 'confirmada')");
  $insRes->bind_param("iissss", $cancha_id, $proveedor_id_ref, $fechaYmd, $hIni, $hFin, $tipoR);

  $insPar = $conn->prepare("
    INSERT INTO partidos (torneo_id, ronda, idx_ronda, jugador1_id, jugador2_id, fecha, resultado, ganador_id, reserva_id, next_partido_id, next_pos)
    VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, NULL, NULL)
  ");
  $insPar->bind_param("iiiiisi", $torneo_id_ref, $ronda_ref, $idx_ref, $j1, $j2, $fechaDT, $reserva_id_ref);

  $torneo_id_ref = $torneo_id; $proveedor_id_ref=$proveedor_id;
  $tipoR = $tipoReserva;

  $partidosIndex = [];
  foreach ($agenda as $a) {
    $r = (int)$a['ronda']; $i = (int)$a['idx'];
    $j1 = ($r===1) ? (int)$a['j1'] : null; // rondas futuras quedan NULL
    $j2 = ($r===1) ? (int)$a['j2'] : null;

    $cancha_id = (int)$a['cancha_id'];
    $fechaYmd  = $a['fecha'];
    $hIni      = $a['hora_inicio'];
    $hFin      = $a['hora_fin'];

    $insRes->execute(); $reserva_id_ref = $conn->insert_id;
    $fechaDT = $fechaYmd.' '.$hIni;

    $ronda_ref=$r; $idx_ref=$i;
    $insPar->execute(); $pid = $conn->insert_id;

    if (!isset($partidosIndex[$ronda_ref])) $partidosIndex[$ronda_ref]=[];
    $partidosIndex[$ronda_ref][$idx_ref] = $pid;

    if ($ronda_ref===1 && $j1 && $j2) {
      $tt = "Partido programado de torneo";
      $msg = "Tu partido del torneo \"{$torneo['nombre']}\" fue programado para el {$fechaYmd} de ".substr($hIni,0,5)." a ".substr($hFin,0,5).".";
      notificar($conn, $j1, 'torneo_partido', $tt, $msg);
      notificar($conn, $j2, 'torneo_partido', $tt, $msg);
    }
  }
  $insRes->close(); $insPar->close();

  $maxR = max(array_keys($partidosIndex));
  $upd = $conn->prepare("UPDATE partidos SET next_partido_id=?, next_pos=? WHERE partido_id=?");
  foreach ($partidosIndex as $r => $arr) {
    if ($r===$maxR) continue; // final
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

  unset($_SESSION['wizard_agenda']);
  backWith('torneoCronograma.php?torneo_id='.$torneo_id);
}

backWith('torneos.php');
