<?php
/* =========================================================================
 * file: php/recepcionista/partidos/partidosAction.php  (REEMPLAZA COMPLETO)
 * ========================================================================= */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config.php';

$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);
$action       = $_POST['action'] ?? '';

function redirect_with(string $base, array $params = []): void {
  $qs = http_build_query($params); $sep = (strpos($base,'?')===false)?'?':'&';
  header('Location: '.$base.($qs?$sep.$qs:'')); exit;
}
function ok(string $msg, ?string $fecha=null): void { $p=['ok'=>$msg]; if($fecha)$p['fecha']=$fecha; redirect_with('partidos.php',$p); }
function err(string $msg, ?string $fecha=null): void { $p=['err'=>$msg]; if($fecha)$p['fecha']=$fecha; redirect_with('partidos.php',$p); }

function notify_admins(mysqli $conn, string $tipo, string $origen, string $titulo, string $mensaje): void {
  $sql = "INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
          SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol='admin'";
  $st=$conn->prepare($sql); $st->bind_param("ssss",$tipo,$origen,$titulo,$mensaje); $st->execute(); $st->close();
}
function notify_user(mysqli $conn, int $user_id, string $tipo, string $origen, string $titulo, string $mensaje): void {
  $st=$conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?,?,?,?,?)");
  $st->bind_param("issss",$user_id,$tipo,$origen,$titulo,$mensaje); $st->execute(); $st->close();
}

/** Upsert simple en ranking (solo puntos) */
function ranking_add_points(mysqli $conn, int $usuario_id, int $delta): void {
  $sel = $conn->prepare("SELECT ranking_id FROM ranking WHERE usuario_id=? LIMIT 1");
  $sel->bind_param("i",$usuario_id);
  $sel->execute();
  $sel->bind_result($rid);
  $exists = $sel->fetch();
  $sel->close();

  if ($exists) {
    $up=$conn->prepare("UPDATE ranking SET puntos = GREATEST(0, puntos + ?) WHERE usuario_id=?");
    $up->bind_param("ii",$delta,$usuario_id);
    $up->execute();
    $up->close();
  } else {
    $pmax = max(0,$delta);
    $in=$conn->prepare("INSERT INTO ranking (usuario_id, puntos, partidos, victorias, derrotas) VALUES (?,?,?,?,?)");
    $zero=0;
    $in->bind_param("iiiii",$usuario_id,$pmax,$zero,$zero,$zero);
    $in->execute();
    $in->close();
  }
}

/** true si ya existe historial de este torneo */
function has_tournament_points(mysqli $conn, int $usuario_id, int $torneo_id): bool {
  $q=$conn->prepare("SELECT 1 FROM puntos_historial WHERE usuario_id=? AND origen='torneo' AND referencia_id=? LIMIT 1");
  $q->bind_param("ii",$usuario_id,$torneo_id);
  $q->execute();
  $q->store_result();
  $ok = $q->num_rows>0;
  $q->close();
  return $ok;
}

/** asignar puntos al ganador del torneo (idempotente). Devuelve true si efectivamente otorgó */
function award_tournament_points(mysqli $conn, int $usuario_id, int $torneo_id, int $puntos): bool {
  if ($puntos<=0) return false;
  if (has_tournament_points($conn,$usuario_id,$torneo_id)) return false;

  $ins = $conn->prepare("
    INSERT INTO puntos_historial (usuario_id, origen, referencia_id, puntos, descripcion)
    VALUES (?,?,?,?,?)
  ");

  $origen = 'torneo';
  $desc   = "Ganador torneo #{$torneo_id}";
  $ins->bind_param("isiis", $usuario_id, $origen, $torneo_id, $puntos, $desc);
  $ins->execute();
  $ins->close();

  ranking_add_points($conn, $usuario_id, $puntos);
  return true;
}

/** revertir puntos si se cambia/elimina ganador de la final */
function revert_tournament_points(mysqli $conn, int $usuario_id, int $torneo_id, int $puntos): void {
  if ($puntos<=0) return;

  $del=$conn->prepare("DELETE FROM puntos_historial WHERE usuario_id=? AND origen='torneo' AND referencia_id=?");
  $del->bind_param("ii",$usuario_id,$torneo_id);
  $del->execute();
  $del->close();

  ranking_add_points($conn,$usuario_id,-$puntos);
}

/** Helpers fecha/hora: obtiene datetimes sólidos para inicio/fin */
function compute_start_end(array $row): array {
  $start = null; $end=null;
  if (!empty($row['r_fecha']) && !empty($row['r_hora_inicio'])) {
    $start = $row['r_fecha'].' '.$row['r_hora_inicio'];
    if (!empty($row['r_hora_fin'])) $end = $row['r_fecha'].' '.$row['r_hora_fin'];
  }
  if (!$start) $start = $row['p_fecha'];
  if (!$end)   $end   = $row['p_fecha'];
  return [$start,$end];
}

/** marcar torneo como finalizado (idempotente) */
function mark_tournament_finalized(mysqli $conn, int $torneo_id): void {
  if ($torneo_id <= 0) return;
  $st = $conn->prepare("UPDATE torneos SET estado='finalizado' WHERE torneo_id=? AND estado <> 'finalizado'");
  $st->bind_param("i", $torneo_id);
  $st->execute();
  $st->close();
}

/* ===== Guardar/editar resultado ===== */
if ($action === 'save_result') {
  $partido_id = (int)($_POST['partido_id'] ?? 0);
  $resultado  = trim($_POST['resultado'] ?? '');
  $ganador_id = (int)($_POST['ganador_id'] ?? 0);
  if ($partido_id<=0 || $resultado==='') err('Datos inválidos');

  $sql = "
    SELECT
      p.partido_id, p.torneo_id, p.jugador1_id, p.jugador2_id,
      p.fecha AS p_fecha, p.resultado AS prev_res, p.ganador_id AS prev_gan,
      p.next_partido_id, p.next_pos,
      t.proveedor_id AS prov_torneo, t.puntos_ganador, t.nombre AS torneo_nombre,
      r.fecha AS r_fecha, r.hora_inicio AS r_hora_inicio, r.hora_fin AS r_hora_fin,
      c.proveedor_id AS prov_cancha
    FROM partidos p
    LEFT JOIN torneos t  ON t.torneo_id = p.torneo_id
    LEFT JOIN reservas r ON r.reserva_id = p.reserva_id
    LEFT JOIN canchas  c ON c.cancha_id  = r.cancha_id
    WHERE p.partido_id = ? LIMIT 1
  ";
  $st = $conn->prepare($sql);
  $st->bind_param("i", $partido_id);
  $st->execute();
  $info = $st->get_result()->fetch_assoc();
  $st->close();
  if(!$info) err('Partido inexistente');

  $fechaPartido = date('Y-m-d', strtotime($info['p_fecha']));
  $prov_t=(int)($info['prov_torneo']??0); $prov_c=(int)($info['prov_cancha']??0);
  if ($prov_t!==$proveedor_id && $prov_c!==$proveedor_id) err('No autorizado',$fechaPartido);

  [$startStr,$endStr] = compute_start_end($info);
  $now = new DateTimeImmutable('now');
  $start = new DateTimeImmutable($startStr);
  if ($now < $start) err('Aún no comenzó este partido. Podrás cargarlo cuando inicie.', $fechaPartido);

  $j1=(int)($info['jugador1_id'] ?? 0);
  $j2=(int)($info['jugador2_id'] ?? 0);
  if ($j1<=0 || $j2<=0) err('Este partido todavía no tiene ambos jugadores definidos.', $fechaPartido);
  if ($ganador_id!==$j1 && $ganador_id!==$j2) err('Ganador inválido',$fechaPartido);

  $wasSet = (!empty($info['prev_res']) && !empty($info['prev_gan']));
  $prevWinner = (int)($info['prev_gan'] ?? 0);
  $puntosGanador = (int)($info['puntos_ganador'] ?? 0);
  $torneo_id = (int)($info['torneo_id'] ?? 0);
  $isFinal = ($torneo_id>0 && empty($info['next_partido_id'])); // final detect

  $torneoNombre = trim((string)($info['torneo_nombre'] ?? ''));
  if ($torneoNombre === '') $torneoNombre = "Torneo #{$torneo_id}";

  $conn->begin_transaction();

  $awarded = false; // para notificación idempotente al ganador
  try {
    // Guardar resultado
    $up=$conn->prepare("UPDATE partidos SET resultado=?, ganador_id=? WHERE partido_id=?");
    $up->bind_param("sii",$resultado,$ganador_id,$partido_id);
    $up->execute();
    $up->close();

    // Propagar ganador a siguiente match si aplica
    $nextId  = (int)($info['next_partido_id'] ?? 0);
    $nextPos = ($info['next_pos'] ?? null);
    if ($nextId > 0 && ($nextPos === 'j1' || $nextPos === 'j2')) {
      $nx = ($nextPos === 'j1')
        ? $conn->prepare("UPDATE partidos SET jugador1_id=? WHERE partido_id=?")
        : $conn->prepare("UPDATE partidos SET jugador2_id=? WHERE partido_id=?");
      $nx->bind_param("ii", $ganador_id, $nextId);
      $nx->execute();
      $nx->close();
    }

    // ✅ Si es final: otorgar puntos + marcar torneo finalizado
    if ($isFinal) {
      // puntos torneo (si config > 0)
      if ($ganador_id>0 && $puntosGanador>0) {
        if ($wasSet && $prevWinner && $prevWinner !== $ganador_id) {
          revert_tournament_points($conn,$prevWinner,$torneo_id,$puntosGanador);
        }
        $awarded = award_tournament_points($conn,$ganador_id,$torneo_id,$puntosGanador);
      }

      // ✅ marcar torneo como finalizado siempre que se cargue la final
      mark_tournament_finalized($conn, $torneo_id);
    }

    $conn->commit();
  } catch (Throwable $e) {
    $conn->rollback();
    err('Error guardando el resultado',$fechaPartido);
  }

  // Notificaciones generales (admins + proveedor)
  $tipo = $wasSet ? 'resultado_editado' : 'resultado_nuevo';
  $origen='recepcion';
  $titulo = ($wasSet ? "Resultado editado" : "Resultado cargado") . " (#{$partido_id})";
  $mensaje= "Resultado: {$resultado}.";
  notify_admins($conn,$tipo,$origen,$titulo,$mensaje);
  $proveedor_to_notify = ($prov_t?:$prov_c);
  if ($proveedor_to_notify>0) notify_user($conn,$proveedor_to_notify,$tipo,$origen,$titulo,$mensaje);

  // ✅ Notificación al ganador del torneo (solo si realmente se otorgaron puntos ahora)
  if ($isFinal && $awarded) {
    $tipoW   = 'torneo_ganado';
    $origenW = 'recepcion';
    $tituloW = "🏆 ¡Ganaste {$torneoNombre}!";
    $mensajeW= "Felicitaciones, ganaste el torneo y recibiste {$puntosGanador} puntos.";
    notify_user($conn, $ganador_id, $tipoW, $origenW, $tituloW, $mensajeW);
  }

  ok($wasSet?'Resultado actualizado':'Resultado guardado',$fechaPartido);
}

/* ===== Eliminar SOLO el resultado ===== */
if ($action === 'delete_result') {
  $partido_id = (int)($_POST['partido_id'] ?? 0);
  if ($partido_id<=0) err('Partido inválido');

  $sql="SELECT p.partido_id, p.fecha AS p_fecha, p.resultado, p.ganador_id,
               p.next_partido_id, p.next_pos,
               p.torneo_id, t.puntos_ganador,
               r.fecha AS r_fecha, r.hora_inicio AS r_hora_inicio, r.hora_fin AS r_hora_fin,
               t.proveedor_id AS prov_torneo, c.proveedor_id AS prov_cancha
        FROM partidos p
        LEFT JOIN torneos t  ON t.torneo_id = p.torneo_id
        LEFT JOIN reservas r ON r.reserva_id = p.reserva_id
        LEFT JOIN canchas  c ON c.cancha_id  = r.cancha_id
        WHERE p.partido_id=? LIMIT 1";
  $st=$conn->prepare($sql);
  $st->bind_param("i",$partido_id);
  $st->execute();
  $row=$st->get_result()->fetch_assoc();
  $st->close();
  if(!$row) err('Partido inexistente');

  $fechaPartido = date('Y-m-d', strtotime($row['p_fecha']));
  $prov_t=(int)($row['prov_torneo']??0); $prov_c=(int)($row['prov_cancha']??0);
  if ($prov_t!==$proveedor_id && $prov_c!==$proveedor_id) err('No autorizado',$fechaPartido);

  if (empty($row['resultado']) && empty($row['ganador_id'])) ok('Sin cambios',$fechaPartido);

  $conn->begin_transaction();
  try {
    $up=$conn->prepare("UPDATE partidos SET resultado=NULL, ganador_id=NULL WHERE partido_id=?");
    $up->bind_param("i",$partido_id);
    $up->execute();
    $up->close();

    $nextId  = (int)($row['next_partido_id'] ?? 0);
    $nextPos = ($row['next_pos'] ?? null);
    if ($nextId > 0 && ($nextPos === 'j1' || $nextPos === 'j2')) {
      $nx = ($nextPos === 'j1')
        ? $conn->prepare("UPDATE partidos SET jugador1_id=NULL WHERE partido_id=?")
        : $conn->prepare("UPDATE partidos SET jugador2_id=NULL WHERE partido_id=?");
      $nx->bind_param("i", $nextId);
      $nx->execute();
      $nx->close();
    }

    $torneo_id=(int)($row['torneo_id']??0);
    $isFinal = ($torneo_id>0 && empty($row['next_partido_id']));
    $prevWinner=(int)($row['ganador_id']??0);
    $puntosGanador=(int)($row['puntos_ganador']??0);
    if ($isFinal && $prevWinner>0 && $puntosGanador>0) {
      revert_tournament_points($conn,$prevWinner,$torneo_id,$puntosGanador);
    }

    $conn->commit();
  } catch (Throwable $e) {
    $conn->rollback();
    err('No se pudo eliminar el resultado',$fechaPartido);
  }

  $tipo='resultado_eliminado'; $origen='recepcion';
  $titulo="Resultado eliminado (#{$partido_id})"; $mensaje="Se eliminó el resultado del partido.";
  notify_admins($conn,$tipo,$origen,$titulo,$mensaje);
  $proveedor_to_notify = ($prov_t?:$prov_c);
  if ($proveedor_to_notify>0) notify_user($conn,$proveedor_to_notify,$tipo,$origen,$titulo,$mensaje);

  ok('Resultado eliminado',$fechaPartido);
}

/* ===== Eliminar el PARTIDO por completo ===== */
if ($action === 'delete_match') {
  $partido_id = (int)($_POST['partido_id'] ?? 0);
  if ($partido_id<=0) err('Partido inválido');

  $sql="SELECT p.partido_id, p.fecha AS p_fecha,
               p.torneo_id, t.puntos_ganador, p.ganador_id, p.next_partido_id,
               t.proveedor_id AS prov_torneo, c.proveedor_id AS prov_cancha
        FROM partidos p
        LEFT JOIN torneos t  ON t.torneo_id = p.torneo_id
        LEFT JOIN reservas r ON r.reserva_id = p.reserva_id
        LEFT JOIN canchas  c ON c.cancha_id  = r.cancha_id
        WHERE p.partido_id=? LIMIT 1";
  $st=$conn->prepare($sql);
  $st->bind_param("i",$partido_id);
  $st->execute();
  $row=$st->get_result()->fetch_assoc();
  $st->close();
  if(!$row) err('Partido inexistente');

  $fechaPartido = date('Y-m-d', strtotime($row['p_fecha']));
  $prov_t=(int)($row['prov_torneo']??0); $prov_c=(int)($row['prov_cancha']??0);
  if ($prov_t!==$proveedor_id && $prov_c!==$proveedor_id) err('No autorizado',$fechaPartido);

  $conn->begin_transaction();
  try {
    $torneo_id=(int)($row['torneo_id']??0);
    $isFinal = ($torneo_id>0 && empty($row['next_partido_id']));
    $prevWinner=(int)($row['ganador_id']??0);
    $puntosGanador=(int)($row['puntos_ganador']??0);
    if ($isFinal && $prevWinner>0 && $puntosGanador>0) {
      revert_tournament_points($conn,$prevWinner,$torneo_id,$puntosGanador);
    }

    $del=$conn->prepare("DELETE FROM partidos WHERE partido_id=?");
    $del->bind_param("i",$partido_id);
    if(!$del->execute()){ throw new Exception('db'); }
    $del->close();

    $conn->commit();
  } catch (Throwable $e) {
    $conn->rollback();
    err('No se pudo eliminar el partido',$fechaPartido);
  }

  $tipo='partido_eliminado'; $origen='recepcion';
  $titulo="Partido eliminado (#{$partido_id})"; $mensaje="Se eliminó el partido de la agenda.";
  notify_admins($conn,$tipo,$origen,$titulo,$mensaje);
  $proveedor_to_notify = ($prov_t?:$prov_c);
  if ($proveedor_to_notify>0) notify_user($conn,$proveedor_to_notify,$tipo,$origen,$titulo,$mensaje);

  ok('Partido eliminado',$fechaPartido);
}

err('Acción inválida');
