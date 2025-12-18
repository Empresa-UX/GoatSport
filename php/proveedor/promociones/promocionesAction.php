<?php
/* =========================================================================
 * Acciones promociones (proveedor): add, delete
 * - Validaciones servidor (coinciden con lo pedido)
 * - Notificaciones a admin(es) y recepcionista(s) en alta/baja
 * - Sin campo "activa" en el form: queda activa=1 por defecto
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

/* Notificaciones */
function notify(mysqli $conn, int $usuario_id, string $titulo, string $mensaje){
  $st = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?, 'promocion', 'proveedor', ?, ?)");
  if ($st){ $st->bind_param("iss",$usuario_id,$titulo,$mensaje); $st->execute(); $st->close(); }
}
function destinatarios_admins(mysqli $conn): array {
  $rs = $conn->query("SELECT user_id FROM usuarios WHERE rol='admin'");
  $ids=[]; while($r=$rs->fetch_assoc()) $ids[]=(int)$r['user_id']; return $ids;
}
function destinatarios_recepc(mysqli $conn, int $proveedor_id): array {
  $st=$conn->prepare("SELECT recepcionista_id AS user_id FROM recepcionista_detalle WHERE proveedor_id=?");
  $st->bind_param("i",$proveedor_id); $st->execute();
  $res=$st->get_result(); $ids=[]; while($r=$res->fetch_assoc()) $ids[]=(int)$r['user_id']; $st->close();
  return $ids;
}

if (($_SERVER['REQUEST_METHOD']??'GET')!=='POST') { http_response_code(405); echo 'Método no permitido'; exit; }

$action = $_POST['action'] ?? '';

if ($action==='add') {
  // recoger
  $nombre = trim($_POST['nombre'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $cancha_id = (int)($_POST['cancha_id'] ?? 0); // 0 = todas
  $pct = $_POST['porcentaje_descuento'] ?? '';
  $fi  = $_POST['fecha_inicio'] ?? '';
  $ff  = $_POST['fecha_fin'] ?? '';
  $hi  = $_POST['hora_inicio'] ?? null;
  $hf  = $_POST['hora_fin'] ?? null;
  $dias = $_POST['dias_semana'] ?? []; // array '1'..'7'
  $minRes = (int)($_POST['minima_reservas'] ?? 0);

  $old = [
    'nombre'=>$nombre,'descripcion'=>$descripcion,'cancha_id'=>$cancha_id,
    'porcentaje_descuento'=>$pct,'fecha_inicio'=>$fi,'fecha_fin'=>$ff,
    'hora_inicio'=>$hi,'hora_fin'=>$hf,'dias_semana'=>$dias,'minima_reservas'=>$minRes
  ];
  $err=[];

  // validar
  if ($nombre==='') $err[]='El título es obligatorio.';
  if (mb_strlen($nombre)>100) $err[]='El título no puede superar 100 caracteres.';
  if (mb_strlen($descripcion)>2000) $err[]='La descripción no puede superar 2000 caracteres.';

  if (!is_numeric($pct)) $err[]='Porcentaje de descuento inválido.';
  else {
    $pct=(float)$pct;
    if (!($pct>0 && $pct<100)) $err[]='El % de descuento debe ser mayor a 0 y menor a 100.';
  }

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$fi)) $err[]='Fecha inicio inválida.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$ff)) $err[]='Fecha fin inválida.';
  $today = date('Y-m-d');
  if ($fi && $fi < $today) $err[]='La fecha inicio no puede ser anterior a hoy.';
  if ($ff && $ff < $today) $err[]='La fecha fin no puede ser anterior a hoy.';
  if ($fi && $ff && $ff < $fi) $err[]='La fecha fin no puede ser anterior a la fecha inicio.';

  if ($hi && !preg_match('/^\d{2}:\d{2}$/',$hi)) $err[]='Hora inicio inválida.';
  if ($hf && !preg_match('/^\d{2}:\d{2}$/',$hf)) $err[]='Hora fin inválida.';
  if ($fi && $ff && $fi===$ff && $hi && $hf && $hf <= $hi) $err[]='En el mismo día, la hora fin debe ser posterior a la hora inicio.';

  // días: al menos uno
  if (!is_array($dias) || count($dias)===0) $err[]='Seleccioná al menos un día de la semana.';
  // normalizar días; 7 días => NULL (todos)
  $dias_sql = null;
  if (is_array($dias) && count($dias)>0){
    $dias_clean = array_values(array_unique(array_filter($dias, fn($d)=>preg_match('/^[1-7]$/',$d))));
    sort($dias_clean, SORT_NUMERIC);
    if (count($dias_clean) < 7) $dias_sql = implode(',', $dias_clean); // menos de 7 => set
    // 7 => NULL (todos)
  }

  // cancha del proveedor (si se indicó una)
  if ($cancha_id>0){
    $q=$conn->prepare("SELECT 1 FROM canchas WHERE cancha_id=? AND proveedor_id=? AND activa=1");
    $q->bind_param("ii",$cancha_id,$proveedor_id); $q->execute();
    $ok=$q->get_result()->fetch_row(); $q->close();
    if(!$ok) $err[]='La cancha seleccionada no pertenece a tu club.';
  }

  if ($err) backWith('promocionesForm.php', $err, $old);

  // Insert (activa=1 por defecto)
  $sql="INSERT INTO promociones (proveedor_id, cancha_id, nombre, descripcion, porcentaje_descuento, fecha_inicio, fecha_fin, hora_inicio, hora_fin, dias_semana, minima_reservas, activa)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
  $st=$conn->prepare($sql);
  $cancha_val = $cancha_id>0 ? $cancha_id : null;
  $hi_val = $hi ?: null; $hf_val = $hf ?: null;
$st->bind_param("iissdsssssi",
  $proveedor_id, $cancha_val, $nombre, $descripcion, $pct, $fi, $ff, $hi_val, $hf_val, $dias_sql, $minRes
);

  $st->execute(); $st->close();

  // Notificar admin(es) y recepcionista(s)
  $admins = destinatarios_admins($conn);
  $recs   = destinatarios_recepc($conn, $proveedor_id);
  $titulo = "Nueva promoción creada";
  $msg    = "Se creó la promoción \"{$nombre}\" para el proveedor #{$proveedor_id} (vigencia {$fi} a {$ff}).";
  foreach (array_unique(array_merge($admins,$recs)) as $uid) notify($conn, (int)$uid, $titulo, $msg);

  header('Location: promociones.php'); exit;
}

if ($action==='delete') {
  $promocion_id = (int)($_POST['promocion_id'] ?? 0);
  if ($promocion_id<=0){ header('Location: promociones.php'); exit; }

  // verificar propiedad y obtener nombre para la notificación
  $st=$conn->prepare("SELECT nombre FROM promociones WHERE promocion_id=? AND proveedor_id=?");
  $st->bind_param("ii",$promocion_id,$proveedor_id);
  $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
  if (!$row){ header('Location: promociones.php'); exit; }

  $nombre = (string)$row['nombre'];

  $del=$conn->prepare("DELETE FROM promociones WHERE promocion_id=? AND proveedor_id=?");
  $del->bind_param("ii",$promocion_id,$proveedor_id);
  $del->execute(); $del->close();

  // Notificar admin(es) y recepcionista(s)
  $admins = destinatarios_admins($conn);
  $recs   = destinatarios_recepc($conn, $proveedor_id);
  $titulo = "Promoción eliminada";
  $msg    = "Se eliminó la promoción \"{$nombre}\" del proveedor #{$proveedor_id}.";
  foreach (array_unique(array_merge($admins,$recs)) as $uid) notify($conn, (int)$uid, $titulo, $msg);

  header('Location: promociones.php'); exit;
}

header('Location: promociones.php');
