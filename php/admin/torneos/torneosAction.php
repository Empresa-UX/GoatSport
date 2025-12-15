<?php
require_once './../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_POST['action'] ?? '';

function back(string $msg=''): void {
  if ($msg !== '') $_SESSION['flash'] = $msg;
  header('Location: torneos.php'); exit;
}

function validar(array $in, array &$err): bool {
  $estado = $in['estado'] ?? '';
  $tipo   = $in['tipo'] ?? '';
  $fi     = $in['fecha_inicio'] ?? '';
  $ff     = $in['fecha_fin'] ?? '';
  $cap    = (int)($in['capacidad'] ?? 0);
  $pts    = (int)($in['puntos_ganador'] ?? 0);

  if (!in_array($estado, ['abierto','cerrado','finalizado'], true)) $err[]='Estado inválido.';
  if (!in_array($tipo, ['individual','equipo'], true)) $err[]='Tipo inválido.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fi)) $err[]='Fecha inicio inválida.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ff)) $err[]='Fecha fin inválida.';
  if ($ff < $fi) $err[]='Fecha fin no puede ser anterior a inicio.';
  if (!in_array($cap, [4,8,16,32,64], true)) $err[]='Capacidad inválida.';
  if ($pts < 100) $err[]='Puntos ganador debe ser >= 100.';

  return !$err;
}

/* EDIT */
if ($action === 'edit') {
  $torneo_id = (int)($_POST['torneo_id'] ?? 0);
  if ($torneo_id <= 0) back('ID inválido.');

  $nombre        = trim($_POST['nombre'] ?? '');
  $creador_id    = (int)($_POST['creador_id'] ?? 0);
  $proveedor_id  = (int)($_POST['proveedor_id'] ?? 0);
  $fecha_inicio  = $_POST['fecha_inicio'] ?? '';
  $fecha_fin     = $_POST['fecha_fin'] ?? '';
  $estado        = $_POST['estado'] ?? 'abierto';
  $tipo          = $_POST['tipo'] ?? 'equipo';
  $capacidad     = (int)($_POST['capacidad'] ?? 0);
  $puntos        = (int)($_POST['puntos_ganador'] ?? 0);

  $err = [];
  if ($nombre === '') $err[] = 'Nombre requerido.';
  validar(compact('estado','tipo','fecha_inicio','fecha_fin','capacidad') + ['puntos_ganador'=>$puntos], $err);
  if ($err) back(implode(' ', $err));

  $sql = "
    UPDATE torneos SET
      nombre=?, creador_id=?, proveedor_id=NULLIF(?,0),
      fecha_inicio=?, fecha_fin=?, estado=?, tipo=?,
      capacidad=?, puntos_ganador=?
    WHERE torneo_id=?
  ";
  $st = $conn->prepare($sql);
  // 10 params => 10 types
  $st->bind_param(
    "siissssiiii",
    $nombre, $creador_id, $proveedor_id,
    $fecha_inicio, $fecha_fin, $estado, $tipo,
    $capacidad, $puntos, $torneo_id
  );
  $st->execute();
  $st->close();

  back('Torneo actualizado.');
}

/* DELETE */
if ($action === 'delete') {
  $torneo_id = (int)($_POST['torneo_id'] ?? 0);
  if ($torneo_id <= 0) back('ID inválido.');

  $del = $conn->prepare("DELETE FROM torneos WHERE torneo_id=?");
  $del->bind_param("i", $torneo_id);
  $del->execute();
  $del->close();

  back('Torneo eliminado.');
}

back();
