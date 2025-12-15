<?php
/* =========================================================================
 * file: php/proveedor/canchas/canchasAction.php
 * add / edit / delete. Notifica en delete según estado (aprobada vs pendiente).
 * Edit mantiene notificaciones previas y compara cambios.
 * ========================================================================= */
include '../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405); echo 'Método no permitido'; exit;
}
$csrf = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
  http_response_code(403); echo 'CSRF inválido'; exit;
}

$action = $_POST['action'] ?? '';

/* Helpers */
function redirect_with_errors(array $errors, array $old, string $to='canchasForm.php'){
  $_SESSION['flash_errors'] = $errors;
  $_SESSION['flash_old']    = $old;
  header('Location: '.$to); exit;
}
function own_cancha(mysqli $conn, int $cancha_id, int $proveedor_id): bool {
  $st = $conn->prepare("SELECT 1 FROM canchas WHERE cancha_id=? AND proveedor_id=? LIMIT 1");
  $st->bind_param('ii', $cancha_id, $proveedor_id);
  $st->execute(); $ok = (bool)$st->get_result()->fetch_row(); $st->close();
  return $ok;
}
function time_valid(?string $t): bool { return $t !== null && preg_match('/^\d{2}:\d{2}$/', $t) === 1; }
function notify_admins(mysqli $conn, string $tipo, string $titulo, string $mensaje): void {
  if ($admins = $conn->query("SELECT user_id FROM usuarios WHERE rol='admin'")) {
    while ($a = $admins->fetch_assoc()) {
      $uid = (int)$a['user_id'];
      $stn = $conn->prepare("
        INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje, creada_en, leida)
        VALUES (?, ?, 'sistema', ?, ?, NOW(), 0)
      ");
      if ($stn) { $stn->bind_param('isss', $uid, $tipo, $titulo, $mensaje); $stn->execute(); $stn->close(); }
    }
  }
}

/* === ADD === */
if ($action === 'add') {
  $nombre          = trim($_POST['nombre'] ?? '');
  $tipo            = trim($_POST['tipo'] ?? '');
  $cap_txt         = trim($_POST['capacidad_txt'] ?? '');
  $precio_raw      = $_POST['precio'] ?? '0';
  $hora_apertura   = ($_POST['hora_apertura'] ?? '') !== '' ? $_POST['hora_apertura'] : null;
  $hora_cierre     = ($_POST['hora_cierre']   ?? '') !== '' ? $_POST['hora_cierre']   : null;
  $descripcion     = trim($_POST['descripcion'] ?? '');

  $old = [
    'nombre'=>$nombre,'tipo'=>$tipo,'capacidad_txt'=>$cap_txt,'precio'=>$precio_raw,
    'hora_apertura'=>$hora_apertura,'hora_cierre'=>$hora_cierre,'descripcion'=>$descripcion
  ];

  $errors = [];
  $precio = is_numeric($precio_raw) ? (float)$precio_raw : -1;
  if ($nombre === '') $errors[] = 'El nombre es obligatorio.';
  if ($tipo === '')   $errors[] = 'El tipo es obligatorio.';
  if (!in_array($cap_txt, ['Individual','Equipo'], true)) $errors[] = 'Capacidad inválida.';
  $capacidad = ($cap_txt === 'Individual') ? 2 : 4;
  if ($precio <= 0) $errors[] = 'El precio por hora debe ser mayor a 0.';
  if (!time_valid($hora_apertura) || !time_valid($hora_cierre)) $errors[] = 'Horas inválidas.';
  if (time_valid($hora_apertura) && time_valid($hora_cierre) && $hora_apertura >= $hora_cierre) $errors[] = 'La hora de apertura no puede ser mayor o igual a la de cierre.';

  if ($errors) redirect_with_errors($errors, $old);

  /* INSERT: 8 params -> 'isssidss' */
  $stmt = $conn->prepare("
    INSERT INTO canchas (
      proveedor_id, nombre, descripcion, tipo, capacidad, precio,
      hora_apertura, hora_cierre, duracion_turno, estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 60, 'pendiente')
  ");
  $stmt->bind_param('isssidss',
    $proveedor_id, $nombre, $descripcion, $tipo, $capacidad, $precio, $hora_apertura, $hora_cierre
  );
  $stmt->execute();
  $stmt->close();

  notify_admins(
    $conn,
    'cancha_nueva',
    'Nueva cancha creada',
    "El proveedor #{$proveedor_id} creó la cancha «{$nombre}». Pendiente de aprobación."
  );

  header('Location: canchas.php'); exit;
}

/* === EDIT === */
if ($action === 'edit') {
  $cancha_id = (int)($_POST['cancha_id'] ?? 0);
  if (!$cancha_id || !own_cancha($conn, $cancha_id, $proveedor_id)) { http_response_code(404); echo 'No encontrada'; exit; }

  /* Traer valores actuales para comparar */
  $curr = $conn->prepare("SELECT nombre, descripcion, tipo, capacidad, precio, hora_apertura, hora_cierre, activa FROM canchas WHERE cancha_id=? AND proveedor_id=?");
  $curr->bind_param('ii', $cancha_id, $proveedor_id);
  $curr->execute(); $prev = $curr->get_result()->fetch_assoc(); $curr->close();
  if (!$prev) { http_response_code(404); echo 'No encontrada'; exit; }

  $nombre          = trim($_POST['nombre'] ?? '');
  $tipo            = trim($_POST['tipo'] ?? '');
  $cap_txt         = trim($_POST['capacidad_txt'] ?? '');
  $precio_raw      = $_POST['precio'] ?? '0';
  $hora_apertura   = ($_POST['hora_apertura'] ?? '') !== '' ? $_POST['hora_apertura'] : null;
  $hora_cierre     = ($_POST['hora_cierre']   ?? '') !== '' ? $_POST['hora_cierre']   : null;
  $descripcion     = trim($_POST['descripcion'] ?? '');
  $activa          = isset($_POST['activa']) ? (int)$_POST['activa'] : (int)$prev['activa'];

  $old = [
    'nombre'=>$nombre,'tipo'=>$tipo,'capacidad_txt'=>$cap_txt,'precio'=>$precio_raw,
    'hora_apertura'=>$hora_apertura,'hora_cierre'=>$hora_cierre,'descripcion'=>$descripcion,'activa'=>$activa
  ];

  $errors = [];
  $precio = is_numeric($precio_raw) ? (float)$precio_raw : -1;
  if ($nombre === '') $errors[] = 'El nombre es obligatorio.';
  if ($tipo === '')   $errors[] = 'El tipo es obligatorio.';
  if (!in_array($cap_txt, ['Individual','Equipo'], true)) $errors[] = 'Capacidad inválida.';
  $capacidad = ($cap_txt === 'Individual') ? 2 : 4;
  if ($precio <= 0) $errors[] = 'El precio por hora debe ser mayor a 0.';
  if (!time_valid($hora_apertura) || !time_valid($hora_cierre)) $errors[] = 'Horas inválidas.';
  if (time_valid($hora_apertura) && time_valid($hora_cierre) && $hora_apertura >= $hora_cierre) $errors[] = 'La hora de apertura no puede ser mayor o igual a la de cierre.';

  if ($errors) redirect_with_errors($errors, $old, 'canchasForm.php?cancha_id='.$cancha_id);

  /* UPDATE: incluye 'activa' -> 10 params -> 'sssidssiii' */
  $st = $conn->prepare("
    UPDATE canchas
    SET nombre=?, descripcion=?, tipo=?, capacidad=?, precio=?, hora_apertura=?, hora_cierre=?, activa=?
    WHERE cancha_id=? AND proveedor_id=?
  ");
  $st->bind_param('sssidssiii',
    $nombre, $descripcion, $tipo, $capacidad, $precio, $hora_apertura, $hora_cierre, $activa, $cancha_id, $proveedor_id
  );
  $st->execute(); $st->close();

  /* Notificación: si el ÚNICO cambio fue activa 1->0 => desactivada; si no => editada */
  $solo_desactivada =
      ((int)$prev['activa'] === 1 && $activa === 0) &&
      ($nombre === (string)$prev['nombre']) &&
      ($descripcion === (string)($prev['descripcion'] ?? '')) &&
      ($tipo === (string)($prev['tipo'] ?? '')) &&
      ($capacidad === (int)($prev['capacidad'] ?? 0)) &&
      ((float)$precio === (float)$prev['precio']) &&
      ($hora_apertura === (string)($prev['hora_apertura'] ? substr($prev['hora_apertura'],0,5) : null)) &&
      ($hora_cierre   === (string)($prev['hora_cierre'] ? substr($prev['hora_cierre'],0,5) : null));

  if ($solo_desactivada) {
    notify_admins(
      $conn,
      'cancha_desactivada',
      'Cancha desactivada',
      "El proveedor #{$proveedor_id} desactivó la cancha «{$nombre}»."
    );
  } else {
    notify_admins(
      $conn,
      'cancha_editada',
      'Cancha editada',
      "El proveedor #{$proveedor_id} editó la cancha «{$nombre}»."
    );
  }

  header('Location: canchas.php'); exit;
}

/* === DELETE === */
if ($action === 'delete') {
  $cancha_id = (int)($_POST['cancha_id'] ?? 0);
  if (!$cancha_id || !own_cancha($conn, $cancha_id, $proveedor_id)) { http_response_code(404); echo 'No encontrada'; exit; }

  /* Traemos estado y nombre para notificar adecuadamente antes de borrar */
  $info = $conn->prepare("SELECT nombre, estado FROM canchas WHERE cancha_id=? AND proveedor_id=?");
  $info->bind_param('ii', $cancha_id, $proveedor_id);
  $info->execute(); $row = $info->get_result()->fetch_assoc(); $info->close();

  $nombre = $row['nombre'] ?? ('#'.$cancha_id);
  $estado = $row['estado'] ?? '';

  $st = $conn->prepare("DELETE FROM canchas WHERE cancha_id=? AND proveedor_id=?");
  $st->bind_param('ii', $cancha_id, $proveedor_id);
  $st->execute(); $st->close();

  if ($estado === 'aprobado') {
    notify_admins(
      $conn,
      'cancha_eliminada',
      'Cancha eliminada por proveedor',
      "El proveedor #{$proveedor_id} eliminó la cancha aprobada «{$nombre}»."
    );
  } elseif ($estado === 'pendiente') {
    notify_admins(
      $conn,
      'cancha_pendiente_cancelada',
      'Cancha pendiente cancelada por proveedor',
      "El proveedor #{$proveedor_id} canceló la cancha pendiente «{$nombre}»."
    );
  }

  header('Location: canchas.php'); exit;
}

/* fallback */
header('Location: canchas.php'); exit;
