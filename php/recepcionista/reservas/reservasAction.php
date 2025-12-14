<?php
/* =========================================================================
 * file: php/recepcionista/reservas/reservasAction.php   (REEMPLAZAR COMPLETO)
 *   DB: goatsport_db
 * ========================================================================= */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config.php';

if (function_exists('date_default_timezone_set')) {
  @date_default_timezone_set('America/Argentina/Buenos_Aires');
}

$recepcionista_id = (int) $_SESSION['usuario_id'];
$proveedor_id     = (int) ($_SESSION['proveedor_id'] ?? 0);
$action           = $_POST['action'] ?? '';

function redirect_ok(string $msg): void { header("Location: reservas.php?ok=" . urlencode($msg)); exit; }
function redirect_err(string $msg): void { header("Location: reservas.php?err=" . urlencode($msg)); exit; }

/* --------- Helpers promos ---------- */
function day_1_7(string $date): int { return (int) date('N', strtotime($date)); }
function time_overlap(?string $aStart, ?string $aEnd, string $bStart, string $bEnd): bool {
  if ($aStart === null || $aEnd === null) return true;
  return !($aEnd <= $bStart || $aStart >= $bEnd);
}
function compute_promos(mysqli $conn, int $proveedor_id, int $cancha_id, string $fecha, string $hora_inicio, int $duracion_min): array {
  $st = $conn->prepare("SELECT precio FROM canchas WHERE cancha_id=? AND proveedor_id=? LIMIT 1");
  $st->bind_param("ii", $cancha_id, $proveedor_id);
  $st->execute();
  $st->bind_result($precio_hora);
  if (!$st->fetch()) { $st->close(); return [[], 0.0, 0.0, 0.0]; }
  $st->close();
  $precio_base = round(((float)$precio_hora) * ($duracion_min/60), 2);

  $sql = "SELECT promocion_id, cancha_id, nombre, descripcion, porcentaje_descuento, fecha_inicio, fecha_fin, hora_inicio, hora_fin, dias_semana, minima_reservas
          FROM promociones
          WHERE proveedor_id=? AND activa=1 AND (cancha_id IS NULL OR cancha_id=?) AND ? BETWEEN fecha_inicio AND fecha_fin";
  $p = $conn->prepare($sql);
  $p->bind_param("iis", $proveedor_id, $cancha_id, $fecha);
  $p->execute(); $rs = $p->get_result();

  $res_ini = $hora_inicio . ':00';
  $res_fin = date('H:i:s', strtotime($hora_inicio.':00') + $duracion_min*60);
  $dow = (string)day_1_7($fecha);

  $promos=[]; $sum_pct=0.0;
  while($row=$rs->fetch_assoc()){
    if ($row['dias_semana']!==null && $row['dias_semana']!=='') {
      $dias = explode(',', $row['dias_semana']);
      if (!in_array($dow, $dias, true)) continue;
    }
    if (!time_overlap($row['hora_inicio'], $row['hora_fin'], $res_ini, $res_fin)) continue;
    if ((int)$row['minima_reservas'] > 0) continue;
    $pct = (float)$row['porcentaje_descuento'];
    $ahorro = round($precio_base * ($pct/100.0), 2);
    $promos[] = ['promocion_id'=>(int)$row['promocion_id'],'nombre'=>$row['nombre'],'porcentaje_descuento'=>$pct,'ahorro'=>$ahorro];
    $sum_pct += $pct;
  }
  $p->close();

  $precio_final = round($precio_base * (1.0 - $sum_pct/100.0), 2);
  if ($precio_final < 0) $precio_final = 0.0;

  return [$promos, $sum_pct, $precio_base, $precio_final];
}

/* ================== Helpers Invitados / Torneo Sombra ================== */
function email_exists_user_id(mysqli $conn, string $email): ?int {
  $q = $conn->prepare("SELECT user_id FROM usuarios WHERE email=? LIMIT 1");
  $q->bind_param("s", $email);
  $q->execute();
  $q->bind_result($uid);
  if ($q->fetch()) { $q->close(); return (int)$uid; }
  $q->close();
  return null;
}
function gen_unique_guest_email(mysqli $conn): string {
  do {
    $candidate = 'guest_'.time().'_'.random_int(1000, 9999).'@guest.local';
    $exists = email_exists_user_id($conn, $candidate);
  } while ($exists !== null);
  return $candidate;
}
/* crea usuario invitado para usar como titular/participante */
function create_marked_invited_user(mysqli $conn, string $nombre): int {
  $nombre = trim($nombre);
  if ($nombre === '') return 0;

  $email = gen_unique_guest_email($conn);
  $rol   = 'cliente';
  $hash  = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);

  $st = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol) VALUES (?,?,?,?)");
  $st->bind_param("ssss", $nombre, $email, $hash, $rol);
  if (!$st->execute()) { $st->close(); return 0; }
  $uid = (int)$st->insert_id;
  $st->close();

  $mi = $conn->prepare("INSERT INTO invitados (user_id) VALUES (?)");
  $mi->bind_param("i", $uid);
  if(!$mi->execute()){ $mi->close(); return 0; }
  $mi->close();

  return $uid;
}
function resolve_player(mysqli $conn, string $mode, ?int $sel_id, ?string $nombre): int {
  $mode = ($mode === 'inv') ? 'inv' : 'reg';
  if ($mode === 'reg') {
    $id = (int)($sel_id ?? 0);
    return $id > 0 ? $id : 0;
  }
  $nombre = trim((string)$nombre);
  if ($nombre === '') return 0;
  return create_marked_invited_user($conn, $nombre);
}
function get_or_create_shadow_torneo(mysqli $conn, int $proveedor_id, int $creador_id): int {
  $nombre = 'Amistosos (auto)';
  $q = $conn->prepare("SELECT torneo_id FROM torneos WHERE proveedor_id=? AND nombre=? LIMIT 1");
  $q->bind_param("is", $proveedor_id, $nombre);
  $q->execute();
  $q->bind_result($tid);
  if ($q->fetch()) { $q->close(); return (int)$tid; }
  $q->close();

  $hoy = date('Y-m-d'); $estado = 'abierto'; $puntos = 0;
  $ins = $conn->prepare("INSERT INTO torneos (nombre, creador_id, proveedor_id, fecha_inicio, fecha_fin, estado, puntos_ganador) VALUES (?,?,?,?,?,?,?)");
  $ins->bind_param("siisssi", $nombre, $creador_id, $proveedor_id, $hoy, $hoy, $estado, $puntos);
  if (!$ins->execute()) { $ins->close(); return 0; }
  $torneo_id = (int)$ins->insert_id;
  $ins->close();
  return $torneo_id;
}

/* ====== Validadores comunes ====== */
function assert_not_past(string $fecha, string $hora_inicio): void {
  $today = date('Y-m-d'); $now = date('H:i');
  if ($fecha < $today) redirect_err('La fecha no puede ser pasada.');
  if ($fecha === $today && $hora_inicio < $now) redirect_err('La hora de inicio no puede ser en el pasado.');
}
function cancha_capacidad_match(mysqli $conn, int $proveedor_id, int $cancha_id, string $tipo_reserva): void {
  $q=$conn->prepare("SELECT capacidad FROM canchas WHERE cancha_id=? AND proveedor_id=? AND activa=1 LIMIT 1");
  $q->bind_param("ii",$cancha_id,$proveedor_id);
  $q->execute(); $q->bind_result($cap);
  if(!$q->fetch()){ $q->close(); redirect_err('Cancha inválida o inactiva.'); }
  $q->close();
  $need = ($tipo_reserva==='equipo') ? 4 : 2;
  if ((int)$cap !== $need) redirect_err('La cancha seleccionada no coincide con el tipo de reserva.');
}

/* ----- API: preview promos ----- */
if ($action === 'promos_preview') {
  header('Content-Type: application/json; charset=utf-8');
  $cancha_id   = (int)($_POST['cancha_id'] ?? 0);
  $fecha       = trim($_POST['fecha'] ?? '');
  $hora_inicio = trim($_POST['hora_inicio'] ?? '');
  $duracion    = max(0, (int)($_POST['duracion'] ?? 0));
  if ($proveedor_id<=0 || $cancha_id<=0 || !$fecha || !$hora_inicio || $duracion<=0) {
    echo json_encode(['ok'=>false,'error'=>'Parámetros incompletos']); exit;
  }
  [$promos, $pct, $base, $final] = compute_promos($conn, $proveedor_id, $cancha_id, $fecha, $hora_inicio, $duracion);
  echo json_encode(['ok'=>true,'data'=>[
    'promos'=>$promos,'total_descuento_pct'=>$pct,'precio_base'=>$base,'precio_final'=>$final
  ]]); exit;
}

/* ----- Crear reserva (+ opcionalmente partido) ----- */
if ($action === 'add') {
  $cancha_id      = (int)($_POST['cancha_id'] ?? 0);
  $fecha          = trim($_POST['fecha'] ?? '');
  $hora_inicio    = trim($_POST['hora_inicio'] ?? '');
  $duracion       = max(0, (int)($_POST['duracion'] ?? 0));
  $tipo_reserva   = $_POST['tipo_reserva'] ?? 'individual';
  $metodo         = $_POST['metodo'] ?? '';
  $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');

  $split_costs    = isset($_POST['split_costs']) && $_POST['split_costs']=='1';
  $split_names    = array_map('trim', $_POST['split_names'] ?? []);
  $crear_partido  = isset($_POST['crear_partido']) && $_POST['crear_partido']=='1';

  if ($cancha_id<=0 || $fecha==='' || $hora_inicio==='') redirect_err('Datos incompletos.');
  if ($duracion<=0) redirect_err('La duración debe ser mayor a 0.');
  if ($cliente_nombre==='') redirect_err('Ingrese nombre y apellido del cliente.');
  if (!in_array($metodo, ['club','tarjeta','mercado_pago'], true)) redirect_err('Seleccione un método de pago válido.');

  // Validaciones nuevas
  assert_not_past($fecha, $hora_inicio);
  cancha_capacidad_match($conn, $proveedor_id, $cancha_id, $tipo_reserva);

  if ($split_costs) {
    $esperados = ($tipo_reserva==='equipo') ? 3 : 1;
    $validos = array_values(array_filter($split_names, fn($s)=>$s!==''));
    if (count($validos) !== $esperados) redirect_err('Complete los nombres de todos los integrantes para dividir costos.');
  }

  // Cancha válida (activa) + precio
  $stmt=$conn->prepare("SELECT proveedor_id, precio FROM canchas WHERE cancha_id=? AND proveedor_id=? AND activa=1 LIMIT 1");
  $stmt->bind_param("ii",$cancha_id,$proveedor_id);
  $stmt->execute();
  $stmt->bind_result($prov_cancha,$precio_hora);
  if(!$stmt->fetch()){ $stmt->close(); redirect_err('Cancha inválida o inactiva.'); }
  $stmt->close();

  $t_inicio = strtotime("$fecha $hora_inicio");
  if($t_inicio===false) redirect_err('Hora inválida.');
  $hora_fin = date('H:i:s', $t_inicio + ($duracion*60));

  // Conflicto reservas
  $st1=$conn->prepare("SELECT 1 FROM reservas WHERE cancha_id=? AND fecha=? AND estado <> 'cancelada' AND NOT (hora_fin <= ? OR hora_inicio >= ?) LIMIT 1");
  $st1->bind_param("isss",$cancha_id,$fecha,$hora_inicio,$hora_fin);
  $st1->execute(); $st1->store_result(); if($st1->num_rows>0){ $st1->close(); redirect_err('Conflicto con otra reserva.'); } $st1->close();

  // Conflicto eventos
  $ini_dt=$fecha.' '.$hora_inicio; $fin_dt=$fecha.' '.$hora_fin;
  $st2=$conn->prepare("SELECT 1 FROM eventos_especiales WHERE cancha_id=? AND ( (fecha_inicio < ? AND fecha_fin > ?) OR (fecha_inicio >= ? AND fecha_inicio < ?) ) LIMIT 1");
  $st2->bind_param("issss",$cancha_id,$fin_dt,$ini_dt,$ini_dt,$fin_dt);
  $st2->execute(); $st2->store_result(); if($st2->num_rows>0){ $st2->close(); redirect_err('Conflicto con un evento especial.'); } $st2->close();

  // titular del pago = cliente (crear invitado) y usarlo también como CREADOR de la reserva
  $titular_id = create_marked_invited_user($conn, $cliente_nombre);
  if ($titular_id <= 0) redirect_err('No se pudo registrar al cliente como titular del pago.');
  $creador_id = $titular_id;

  [$promos_aplic, $pct_total, $precio_base, $precio_final] =
    compute_promos($conn, $proveedor_id, $cancha_id, $fecha, $hora_inicio, $duracion);

  $conn->begin_transaction();

  try {
    // Reserva (confirmada)
    $st=$conn->prepare("INSERT INTO reservas (cancha_id, creador_id, fecha, hora_inicio, hora_fin, precio_total, tipo_reserva, estado) VALUES (?,?,?,?,?,?,?, 'confirmada')");
    $st->bind_param("iisssds",$cancha_id,$creador_id,$fecha,$hora_inicio,$hora_fin,$precio_final,$tipo_reserva);
    if(!$st->execute()) throw new Exception('No se pudo crear la reserva.');
    $reserva_id=(int)$st->insert_id; $st->close();

    // Estado de pago
    $estado_pago = ($metodo==='club') ? 'pagado' : ( ($tipo_reserva==='equipo' || $split_costs) ? 'pendiente' : 'pagado' );
    $fecha_pago  = ($estado_pago==='pagado') ? date('Y-m-d H:i:s') : null;

    $detalle = $split_costs ? ('Dividir costos; integrantes: '.$cliente_nombre.' | '.implode(' | ', array_values(array_filter($split_names)))) : null;

    // Pago a nombre del CLIENTE (titular)
    $stp=$conn->prepare("INSERT INTO pagos (reserva_id, jugador_id, monto, metodo, estado, fecha_pago, detalle) VALUES (?,?,?,?,?,?,?)");
    $stp->bind_param("iidssss",$reserva_id,$titular_id,$precio_final,$metodo,$estado_pago,$fecha_pago,$detalle);
    if(!$stp->execute()) throw new Exception('No se pudo registrar el pago.');
    $stp->close();

    /* ===== Crear partido si se pidió ===== */
    $partido_id = null;
    if ($crear_partido) {
      $p1_mode = $_POST['p1_mode'] ?? 'reg';
      $p2_mode = $_POST['p2_mode'] ?? 'reg';

      $p1_sel_id = isset($_POST['jugador1_id']) ? (int)$_POST['jugador1_id'] : 0;
      $p1_nombre = trim($_POST['jugador1_nombre'] ?? '');
      $j1_id = resolve_player($conn, $p1_mode, $p1_sel_id, $p1_nombre);
      if ($j1_id <= 0) throw new Exception('Jugador 1 inválido.');

      $p2_sel_id = isset($_POST['jugador2_id']) ? (int)$_POST['jugador2_id'] : 0;
      $p2_nombre = trim($_POST['jugador2_nombre'] ?? '');
      $j2_id = resolve_player($conn, $p2_mode, $p2_sel_id, $p2_nombre);
      if ($j2_id <= 0) throw new Exception('Jugador 2 inválido.');

      if ($j1_id === $j2_id) throw new Exception('Los jugadores no pueden ser el mismo usuario.');

      $torneo_id = get_or_create_shadow_torneo($conn, $proveedor_id, $recepcionista_id);
      if ($torneo_id <= 0) throw new Exception('No se pudo preparar el torneo para el partido.');

      $fecha_partido = $ini_dt . ':00';

      $ip = $conn->prepare("INSERT INTO partidos (torneo_id, jugador1_id, jugador2_id, fecha, resultado, ganador_id, reserva_id) VALUES (?,?,?,?,NULL,NULL,?)");
      $ip->bind_param("iiisi", $torneo_id, $j1_id, $j2_id, $fecha_partido, $reserva_id);
      if(!$ip->execute()) { $ip->close(); throw new Exception('No se pudo crear el partido.'); }
      $partido_id = (int)$ip->insert_id;
      $ip->close();
    }

    // Notificaciones - reserva
    $tipo='reserva_nueva'; $origen='recepcion';
    $titulo="Nueva reserva walk-in #{$reserva_id}";
    $msgA="Reserva creada desde recepción para {$cliente_nombre}.";
    $stA=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol='admin'");
    $stA->bind_param("ssss",$tipo,$origen,$titulo,$msgA); if(!$stA->execute()) throw new Exception('No se pudo notificar admins.'); $stA->close();

    $msgP="Reserva en {$fecha} {$hora_inicio}-{$hora_fin}. Cliente: {$cliente_nombre}.";
    $stP=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?,?,?,?,?)");
    $stP->bind_param("issss",$proveedor_id,$tipo,$origen,$titulo,$msgP); if(!$stP->execute()) throw new Exception('No se pudo notificar proveedor.'); $stP->close();

    // Notificaciones - partido (si hubo)
    if ($partido_id) {
      $tipo2='partido_nuevo'; $tit2="Partido creado #{$partido_id} (reserva #{$reserva_id})";
      $msg2="Se creó un partido a partir de una reserva sin cita previa para {$fecha} {$hora_inicio}-{$hora_fin}.";
      $stAp=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol='admin'");
      $stAp->bind_param("ssss",$tipo2,$origen,$tit2,$msg2); $stAp->execute(); $stAp->close();
      $stPp=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?,?,?,?,?)");
      $stPp->bind_param("issss",$proveedor_id,$tipo2,$origen,$tit2,$msg2); $stPp->execute(); $stPp->close();
    }

    $conn->commit();

    $ok = "Reserva #$reserva_id creada.";
    if (!empty($partido_id)) $ok .= " Partido #$partido_id creado.";
    redirect_ok($ok);
  } catch (Throwable $e) {
    $conn->rollback();
    redirect_err($e->getMessage());
  }
}

/* ----- marcar pagado ----- */
if ($action === 'mark_paid') {
  $pago_id = (int)($_POST['pago_id'] ?? 0);
  if ($pago_id <= 0) redirect_err('Pago inválido.');

  $st=$conn->prepare("
    SELECT p.pago_id, p.metodo, r.reserva_id, r.fecha, r.hora_inicio, r.hora_fin
    FROM pagos p
    JOIN reservas r ON r.reserva_id=p.reserva_id
    JOIN canchas c  ON c.cancha_id=r.cancha_id
    WHERE p.pago_id=? AND c.proveedor_id=? AND p.estado <> 'pagado'
    LIMIT 1
  ");
  $st->bind_param("ii",$pago_id,$proveedor_id);
  $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
  if(!$row) redirect_err('No autorizado o ya pagado.');

  $upd=$conn->prepare("UPDATE pagos SET estado='pagado', fecha_pago=NOW() WHERE pago_id=?");
  $upd->bind_param("i",$pago_id); $upd->execute(); $upd->close();

  $reserva_id=(int)$row['reserva_id'];
  $tipo='pago_confirmado'; $origen='recepcion';
  $titulo="Pago confirmado (#{$pago_id})";
  $mensaje="Reserva #{$reserva_id} confirmada ({$row['metodo']}) en {$row['fecha']} {$row['hora_inicio']}-{$row['hora_fin']}.";

  $stA=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol='admin'");
  $stA->bind_param("ssss",$tipo,$origen,$titulo,$mensaje); $stA->execute(); $stA->close();

  $stP=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?,?,?,?,?)");
  $stP->bind_param("issss",$proveedor_id,$tipo,$origen,$titulo,$mensaje); $stP->execute(); $stP->close();

  redirect_ok('Pago confirmado.');
}

/* ----- eliminar reserva (+ borra partido ligado) ----- */
if ($action === 'delete') {
  $reserva_id = (int)($_POST['reserva_id'] ?? 0);
  if ($reserva_id<=0) redirect_err('Reserva inválida.');

  $q=$conn->prepare("SELECT r.reserva_id, r.fecha, r.hora_inicio, r.hora_fin, c.proveedor_id FROM reservas r JOIN canchas c ON c.cancha_id=r.cancha_id WHERE r.reserva_id=? AND c.proveedor_id=? LIMIT 1");
  $q->bind_param("ii",$reserva_id,$proveedor_id);
  $q->execute(); $row=$q->get_result()->fetch_assoc(); $q->close();
  if(!$row) redirect_err('No autorizado.');

  // Borrar partido/s asociados a la reserva
  $delPart=$conn->prepare("DELETE FROM partidos WHERE reserva_id=?");
  $delPart->bind_param("i",$reserva_id);
  $delPart->execute(); $delPart->close();

  // Borrar reserva
  $del=$conn->prepare("DELETE FROM reservas WHERE reserva_id=? LIMIT 1");
  $del->bind_param("i",$reserva_id);
  if(!$del->execute()){ $del->close(); redirect_err('No se pudo eliminar la reserva.'); }
  $del->close();

  $tipo='reserva_eliminada'; $origen='recepcion';
  $titulo="Reserva eliminada #{$reserva_id}";
  $mensaje="Se eliminó la reserva #{$reserva_id} programada para {$row['fecha']} {$row['hora_inicio']}-{$row['hora_fin']}.";

  $stA=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol='admin'");
  $stA->bind_param("ssss",$tipo,$origen,$titulo,$mensaje); $stA->execute(); $stA->close();

  $stP=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?,?,?,?,?)");
  $stP->bind_param("issss",$proveedor_id,$tipo,$origen,$titulo,$mensaje); $stP->execute(); $stP->close();

  redirect_ok("Reserva #{$reserva_id} eliminada.");
}

/* ----- editar reserva (actualiza CREADOR si cambia el Cliente) ----- */
if ($action === 'edit') {
  $reserva_id    = (int)($_POST['reserva_id'] ?? 0);
  $cancha_id     = (int)($_POST['cancha_id'] ?? 0);
  $fecha         = trim($_POST['fecha'] ?? '');
  $hora_inicio   = trim($_POST['hora_inicio'] ?? '');
  $duracion      = max(0, (int)($_POST['duracion'] ?? 0));
  $tipo_reserva  = $_POST['tipo_reserva'] ?? 'individual';
  $metodo        = $_POST['metodo'] ?? 'club';
  $cliente_nombre= trim($_POST['cliente_nombre'] ?? '');

  if ($reserva_id<=0 || $cancha_id<=0 || $fecha==='' || $hora_inicio==='' || $duracion<=0) redirect_err('Datos incompletos.');

  // Validaciones nuevas
  assert_not_past($fecha, $hora_inicio);
  cancha_capacidad_match($conn, $proveedor_id, $cancha_id, $tipo_reserva);

  // validar propiedad + traer creador actual
  $q=$conn->prepare("
    SELECT r.reserva_id, r.creador_id, u.nombre AS creador_nombre
    FROM reservas r
    JOIN canchas c ON c.cancha_id=r.cancha_id
    JOIN usuarios u ON u.user_id=r.creador_id
    WHERE r.reserva_id=? AND c.proveedor_id=?
    LIMIT 1
  ");
  $q->bind_param("ii",$reserva_id,$proveedor_id);
  $q->execute(); $cur=$q->get_result()->fetch_assoc(); $q->close();
  if(!$cur) redirect_err('No autorizado.');

  // calcular fin
  $t_inicio = strtotime("$fecha $hora_inicio");
  if($t_inicio===false) redirect_err('Hora inválida.');
  $hora_fin = date('H:i:s', $t_inicio + ($duracion*60));

  // conflicto con otras reservas (excluyéndose)
  $st1=$conn->prepare("SELECT 1 FROM reservas WHERE cancha_id=? AND fecha=? AND estado <> 'cancelada' AND reserva_id<>? AND NOT (hora_fin <= ? OR hora_inicio >= ?) LIMIT 1");
  $st1->bind_param("isiss",$cancha_id,$fecha,$reserva_id,$hora_inicio,$hora_fin);
  $st1->execute(); $st1->store_result(); if($st1->num_rows>0){ $st1->close(); redirect_err('Conflicto con otra reserva.'); } $st1->close();

  // promos y precio final
  [$promos_aplic, $pct_total, $precio_base, $precio_final] =
    compute_promos($conn, $proveedor_id, $cancha_id, $fecha, $hora_inicio, $duracion);

  // decidir nuevo creador (si cambió el nombre)
  $creador_id_nuevo = (int)$cur['creador_id'];
  if ($cliente_nombre !== '' && mb_strtolower(trim($cliente_nombre),'UTF-8') !== mb_strtolower(trim((string)$cur['creador_nombre']),'UTF-8')) {
    $tmp_id = create_marked_invited_user($conn, $cliente_nombre);
    if ($tmp_id > 0) $creador_id_nuevo = $tmp_id;
  }

  $conn->begin_transaction();
  try {
    $up=$conn->prepare("UPDATE reservas SET cancha_id=?, fecha=?, hora_inicio=?, hora_fin=?, precio_total=?, tipo_reserva=?, creador_id=? WHERE reserva_id=?");
    $up->bind_param("isssdsii",$cancha_id,$fecha,$hora_inicio,$hora_fin,$precio_final,$tipo_reserva,$creador_id_nuevo,$reserva_id);
    if(!$up->execute()){ $up->close(); throw new Exception('No se pudo actualizar la reserva.'); }
    $up->close();

    // último pago
    $sel=$conn->prepare("SELECT pago_id, estado FROM pagos WHERE reserva_id=? ORDER BY pago_id DESC LIMIT 1");
    $sel->bind_param("i",$reserva_id); $sel->execute(); $p=$sel->get_result()->fetch_assoc(); $sel->close();

    if($p){
      $pid = (int)$p['pago_id'];
      if($p['estado']==='pendiente'){
        $upd=$conn->prepare("UPDATE pagos SET metodo=?, monto=?, jugador_id=? WHERE pago_id=?");
        $upd->bind_param("sdii",$metodo,$precio_final,$creador_id_nuevo,$pid); $upd->execute(); $upd->close();
      }
    }

    // Notificaciones edición (al nuevo titular)
    $tipo='reserva_editada'; $origen='recepcion';
    $titulo="Reserva editada #{$reserva_id}";
    $mensaje="Nueva fecha/horario: {$fecha} {$hora_inicio}-{$hora_fin}.";

    $stA=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
                         SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol='admin'");
    $stA->bind_param("ssss",$tipo,$origen,$titulo,$mensaje); $stA->execute(); $stA->close();

    $stP=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?,?,?,?,?)");
    $stP->bind_param("issss",$proveedor_id,$tipo,$origen,$titulo,$mensaje); $stP->execute(); $stP->close();

    $cl_uid = $creador_id_nuevo;
    $msgCliente = "Su reserva fue actualizada: {$mensaje}";
    $stC=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?,?,?,?,?)");
    $stC->bind_param("issss",$cl_uid,$tipo,$origen,$titulo,$msgCliente); $stC->execute(); $stC->close();

    $conn->commit();
    redirect_ok("Reserva #{$reserva_id} actualizada.");
  } catch(Throwable $e){
    $conn->rollback();
    redirect_err($e->getMessage());
  }
}

/* fallback */
redirect_err('Acción inválida.');
