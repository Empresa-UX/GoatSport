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

/* ===== Guardar/editar resultado ===== */
if ($action === 'save_result') {
  $partido_id = (int)($_POST['partido_id'] ?? 0);
  $resultado  = trim($_POST['resultado'] ?? '');
  $ganador_id = (int)($_POST['ganador_id'] ?? 0);
  if ($partido_id<=0 || $resultado==='') err('Datos inválidos');

  $sql = "
    SELECT p.partido_id, p.jugador1_id, p.jugador2_id, p.fecha, p.resultado AS prev_res, p.ganador_id AS prev_gan,
           t.proveedor_id AS prov_torneo, c.proveedor_id AS prov_cancha
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

  $fechaPartido = date('Y-m-d', strtotime($info['fecha']));
  $prov_t=(int)($info['prov_torneo']??0); $prov_c=(int)($info['prov_cancha']??0);
  if ($prov_t!==$proveedor_id && $prov_c!==$proveedor_id) err('No autorizado',$fechaPartido);

  $j1=(int)$info['jugador1_id']; $j2=(int)$info['jugador2_id'];
  if ($ganador_id!==$j1 && $ganador_id!==$j2) err('Ganador inválido',$fechaPartido);

  $wasSet = (!empty($info['prev_res']) && !empty($info['prev_gan']));
  $up=$conn->prepare("UPDATE partidos SET resultado=?, ganador_id=? WHERE partido_id=?");
  $up->bind_param("sii",$resultado,$ganador_id,$partido_id); $up->execute(); $up->close();

  // Notificaciones
  $tipo = $wasSet ? 'resultado_editado' : 'resultado_nuevo';
  $origen='recepcion';
  $titulo = ($wasSet ? "Resultado editado" : "Resultado cargado") . " (#{$partido_id})";
  $mensaje= "Resultado: {$resultado}.";
  notify_admins($conn,$tipo,$origen,$titulo,$mensaje);
  $proveedor_to_notify = ($prov_t?:$prov_c);
  if ($proveedor_to_notify>0) notify_user($conn,$proveedor_to_notify,$tipo,$origen,$titulo,$mensaje);

  ok($wasSet?'Resultado actualizado':'Resultado guardado',$fechaPartido);
}

/* ===== Eliminar SOLO el resultado ===== */
if ($action === 'delete_result') {
  $partido_id = (int)($_POST['partido_id'] ?? 0);
  if ($partido_id<=0) err('Partido inválido');

  $sql="SELECT p.partido_id, p.fecha, p.resultado, p.ganador_id,
               t.proveedor_id AS prov_torneo, c.proveedor_id AS prov_cancha
        FROM partidos p
        LEFT JOIN torneos t  ON t.torneo_id = p.torneo_id
        LEFT JOIN reservas r ON r.reserva_id = p.reserva_id
        LEFT JOIN canchas  c ON c.cancha_id  = r.cancha_id
        WHERE p.partido_id=? LIMIT 1";
  $st=$conn->prepare($sql); $st->bind_param("i",$partido_id); $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
  if(!$row) err('Partido inexistente');

  $fechaPartido = date('Y-m-d', strtotime($row['fecha']));
  $prov_t=(int)($row['prov_torneo']??0); $prov_c=(int)($row['prov_cancha']??0);
  if ($prov_t!==$proveedor_id && $prov_c!==$proveedor_id) err('No autorizado',$fechaPartido);

  if (empty($row['resultado']) && empty($row['ganador_id'])) ok('Sin cambios',$fechaPartido);

  $up=$conn->prepare("UPDATE partidos SET resultado=NULL, ganador_id=NULL WHERE partido_id=?");
  $up->bind_param("i",$partido_id); $up->execute(); $up->close();

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

  $sql="SELECT p.partido_id, p.fecha,
               t.proveedor_id AS prov_torneo, c.proveedor_id AS prov_cancha
        FROM partidos p
        LEFT JOIN torneos t  ON t.torneo_id = p.torneo_id
        LEFT JOIN reservas r ON r.reserva_id = p.reserva_id
        LEFT JOIN canchas  c ON c.cancha_id  = r.cancha_id
        WHERE p.partido_id=? LIMIT 1";
  $st=$conn->prepare($sql); $st->bind_param("i",$partido_id); $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
  if(!$row) err('Partido inexistente');

  $fechaPartido = date('Y-m-d', strtotime($row['fecha']));
  $prov_t=(int)($row['prov_torneo']??0); $prov_c=(int)($row['prov_cancha']??0);
  if ($prov_t!==$proveedor_id && $prov_c!==$proveedor_id) err('No autorizado',$fechaPartido);

  // Borrar
  $del=$conn->prepare("DELETE FROM partidos WHERE partido_id=?");
  $del->bind_param("i",$partido_id);
  if(!$del->execute()){ $del->close(); err('No se pudo eliminar el partido',$fechaPartido); }
  $del->close();

  // Notificaciones
  $tipo='partido_eliminado'; $origen='recepcion';
  $titulo="Partido eliminado (#{$partido_id})"; $mensaje="Se eliminó el partido de la agenda.";
  notify_admins($conn,$tipo,$origen,$titulo,$mensaje);
  $proveedor_to_notify = ($prov_t?:$prov_c);
  if ($proveedor_to_notify>0) notify_user($conn,$proveedor_to_notify,$tipo,$origen,$titulo,$mensaje);

  ok('Partido eliminado',$fechaPartido);
}

err('Acción inválida');
