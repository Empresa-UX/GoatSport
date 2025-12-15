<?php
/* =========================================================================
 * file: php/proveedor/eventos/eventosAction.php
 * Acciones: add, delete
 * - Notifica a administradores y recepcionistas en alta y baja
 * - Validaciones anti-abuso y solapes
 * ========================================================================= */
require_once __DIR__ . '/../../config.php';
if (session_status()===PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') {
  header('Location: ../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

function backWith(string $to, array $err=[], array $old=[]){
  if($err) $_SESSION['flash_errors']=$err;
  if($old) $_SESSION['flash_old']=$old;
  header('Location: '.$to); exit;
}

function notify(mysqli $conn, int $usuario_id, string $tipo, string $titulo, string $mensaje){
  $st = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?, ?, 'sistema', ?, ?)");
  if ($st){ $st->bind_param("isss",$usuario_id,$tipo,$titulo,$mensaje); $st->execute(); $st->close(); }
}

function notify_admins_and_recepcionistas(mysqli $conn, int $proveedor_id, string $tipo, string $titulo, string $mensaje){
  // Admins
  $res = $conn->query("SELECT user_id FROM usuarios WHERE rol='admin'");
  if ($res) while($r=$res->fetch_assoc()) notify($conn,(int)$r['user_id'],$tipo,$titulo,$mensaje);
  // Recepcionistas del proveedor
  $st = $conn->prepare("SELECT recepcionista_id FROM recepcionista_detalle WHERE proveedor_id=?");
  $st->bind_param("i",$proveedor_id);
  $st->execute(); $rs=$st->get_result();
  while($r=$rs->fetch_assoc()) notify($conn,(int)$r['recepcionista_id'],$tipo,$titulo,$mensaje);
  $st->close();
}

if (($_SERVER['REQUEST_METHOD']??'GET')!=='POST') { http_response_code(405); echo 'Método no permitido'; exit; }

$action = $_POST['action'] ?? '';

if ($action==='add') {
  $in = [
    'titulo'        => trim($_POST['titulo'] ?? ''),
    'descripcion'   => trim($_POST['descripcion'] ?? ''),
    'tipo'          => $_POST['tipo'] ?? 'bloqueo',
    'cancha_id'     => (int)($_POST['cancha_id'] ?? 0),
    'fecha_inicio'  => $_POST['fecha_inicio'] ?? '',
    'fecha_fin'     => $_POST['fecha_fin'] ?? '',
  ];
  $err=[];

  // Validaciones básicas
  if ($in['titulo']==='') $err[]='El título es obligatorio.';
  if (mb_strlen($in['titulo'])>100) $err[]='El título no puede superar 100 caracteres.';
  if (mb_strlen($in['descripcion'])>2000) $err[]='La descripción no puede superar 2000 caracteres.';
  if (!in_array($in['tipo'], ['bloqueo','torneo','otro'], true)) $err[]='Tipo inválido.';
  if ($in['cancha_id']<=0) $err[]='Cancha obligatoria.';

  // Formatos de datetime
  if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',$in['fecha_inicio'])) $err[]='Fecha inicio inválida.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',$in['fecha_fin']))    $err[]='Fecha fin inválida.';

  // Cancha pertenece al proveedor
  if ($in['cancha_id']>0){
    $q=$conn->prepare("SELECT nombre FROM canchas WHERE cancha_id=? AND proveedor_id=? LIMIT 1");
    $q->bind_param("ii",$in['cancha_id'],$proveedor_id);
    $q->execute(); $ok = $q->get_result()->fetch_assoc(); $q->close();
    if (!$ok) $err[]='La cancha seleccionada no pertenece a tu club.';
    $canchaNombre = $ok ? $ok['nombre'] : '';
  }

  // Coherencia de fechas (no pasado, fin>inicio, y máximo 30 días)
  $today = new DateTime('today');
  if (!$err){
    $fi = DateTime::createFromFormat('Y-m-d\TH:i',$in['fecha_inicio']);
    $ff = DateTime::createFromFormat('Y-m-d\TH:i',$in['fecha_fin']);
    if (!$fi || !$ff) {
      $err[]='Formato de fecha/hora inválido.';
    } else {
      if ($fi < $today) $err[]='La fecha/hora de inicio no puede ser anterior a hoy.';
      if ($ff < $today) $err[]='La fecha/hora de fin no puede ser anterior a hoy.';
      if ($ff <= $fi)   $err[]='La fecha/hora de fin debe ser posterior al inicio.';
      $spanDays = (int)$fi->diff($ff)->format('%a');
      if ($spanDays > 30) $err[]='La duración del evento no puede superar 30 días.';
    }
  }

  // Evitar solapes con reservas y otros eventos en la misma cancha
  if (!$err){
    $fiSql = str_replace('T',' ',$in['fecha_inicio']).':00';
    $ffSql = str_replace('T',' ',$in['fecha_fin']).':00';

    // Reservas
    $st = $conn->prepare("
      SELECT 1 FROM reservas
      WHERE cancha_id=? AND NOT( CONCAT(fecha,' ',hora_fin) <= ? OR CONCAT(fecha,' ',hora_inicio) >= ? )
      LIMIT 1
    ");
    $st->bind_param("iss",$in['cancha_id'],$fiSql,$ffSql);
    $st->execute(); $busy = (bool)$st->get_result()->fetch_row(); $st->close();
    if ($busy) $err[]='Existe una reserva que solapa el intervalo elegido.';

    // Otros eventos
    if (!$busy){
      $st = $conn->prepare("
        SELECT 1 FROM eventos_especiales
        WHERE cancha_id=? AND NOT( fecha_fin <= ? OR fecha_inicio >= ? )
        LIMIT 1
      ");
      $st->bind_param("iss",$in['cancha_id'],$fiSql,$ffSql);
      $st->execute(); $busy2 = (bool)$st->get_result()->fetch_row(); $st->close();
      if ($busy2) $err[]='Existe otro evento especial que solapa el intervalo elegido.';
    }
  }

  if ($err) backWith('eventosForm.php', $err, $in);

  // Insert
  $sql="INSERT INTO eventos_especiales (cancha_id, proveedor_id, titulo, descripcion, fecha_inicio, fecha_fin, tipo, color)
        VALUES (?, ?, ?, ?, ?, ?, ?, '#FF0000')";
  $st=$conn->prepare($sql);
  $fiSql = str_replace('T',' ',$in['fecha_inicio']).':00';
  $ffSql = str_replace('T',' ',$in['fecha_fin']).':00';
  $st->bind_param("iisssss", $in['cancha_id'], $proveedor_id, $in['titulo'], $in['descripcion'], $fiSql, $ffSql, $in['tipo']);
  $st->execute(); $st->close();

  // Notificar admin y recepcionistas
  $fechaLbl = date('d/m/Y H:i', strtotime($fiSql)) . ' — ' . date('d/m/Y H:i', strtotime($ffSql));
  $tituloN = "Evento especial creado";
  $msgN = "El proveedor #{$proveedor_id} creó el evento \"{$in['titulo']}\" (cancha: {$canchaNombre}) para {$fechaLbl}.";
  notify_admins_and_recepcionistas($conn, $proveedor_id, 'evento_creado', $tituloN, $msgN);

  header('Location: eventos.php'); exit;
}

if ($action==='delete') {
  $evento_id = (int)($_POST['evento_id'] ?? 0);
  if ($evento_id<=0){ header('Location: eventos.php'); exit; }

  // verificar propiedad + traer datos para notificar
  $st=$conn->prepare("
    SELECT e.evento_id, e.titulo, e.fecha_inicio, e.fecha_fin, c.nombre AS cancha_nombre
    FROM eventos_especiales e
    LEFT JOIN canchas c ON c.cancha_id=e.cancha_id
    WHERE e.evento_id=? AND e.proveedor_id=? LIMIT 1
  ");
  $st->bind_param("ii",$evento_id,$proveedor_id);
  $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
  if (!$row){ header('Location: eventos.php'); exit; }

  // borrar
  $del=$conn->prepare("DELETE FROM eventos_especiales WHERE evento_id=? AND proveedor_id=?");
  $del->bind_param("ii",$evento_id,$proveedor_id);
  $del->execute(); $del->close();

  // notificar admin y recepcionistas
  $fechaLbl = date('d/m/Y H:i', strtotime($row['fecha_inicio'])) . ' — ' . date('d/m/Y H:i', strtotime($row['fecha_fin']));
  $tituloN = "Evento especial eliminado";
  $msgN = "El proveedor #{$proveedor_id} eliminó el evento \"{$row['titulo']}\" (cancha: {$row['cancha_nombre']}) que estaba programado para {$fechaLbl}.";
  notify_admins_and_recepcionistas($conn, $proveedor_id, 'evento_eliminado', $tituloN, $msgN);

  header('Location: eventos.php'); exit;
}

header('Location: eventos.php');
