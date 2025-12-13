<?php
require_once './../../config.php';
$rol = $_SESSION['rol'] ?? null;
$action = $_POST['action'] ?? '';

function backWith(string $msg=''): void {
  if($msg!=='') $_SESSION['flash']=$msg;
  header('Location: torneos.php'); exit;
}

function validarDatos(array $in, array &$err): bool {
  $err = $err ?? [];
  $estado = $in['estado'] ?? '';
  $tipo   = $in['tipo'] ?? '';
  $fi     = $in['fecha_inicio'] ?? '';
  $ff     = $in['fecha_fin'] ?? '';
  $cap    = (int)($in['capacidad'] ?? 0);
  $pts    = (int)($in['puntos_ganador'] ?? ($in['puntos'] ?? 0)); // acepta ambos nombres

  if (!in_array($estado, ['abierto','cerrado','finalizado'], true)) $err[]='Estado inválido.';
  if (!in_array($tipo, ['individual','equipo'], true)) $err[]='Tipo inválido.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fi)) $err[]='Fecha inicio inválida.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ff)) $err[]='Fecha fin inválida.';
  if ($ff < $fi) $err[]='Fin no puede ser anterior a inicio.';
  if ($cap < 2 || $cap % 2 !== 0) $err[]='Capacidad debe ser par y >= 2.';
  if ($pts < 0) $err[]='Puntos inválidos.';
  return !$err;
}

if ($action === 'add') {
  if ($rol === 'admin') backWith('El administrador no puede crear torneos.');

  $nombre        = trim($_POST['nombre'] ?? '');
  $creador_id    = (int)($_POST['creador_id'] ?? 0);
  $proveedor_id  = (int)($_POST['proveedor_id'] ?? 0);
  $fecha_inicio  = $_POST['fecha_inicio'] ?? '';
  $fecha_fin     = $_POST['fecha_fin'] ?? '';
  $estado        = $_POST['estado'] ?? 'abierto';
  $tipo          = $_POST['tipo'] ?? 'equipo';
  $capacidad     = (int)($_POST['capacidad'] ?? 0);
  $puntos        = (int)($_POST['puntos_ganador'] ?? 0);

  $err=[]; if ($nombre==='') $err[]='Nombre requerido.';
  $x=[]; validarDatos(compact('estado','tipo','fecha_inicio','fecha_fin','capacidad','puntos') + ['puntos_ganador'=>$puntos], $x);
  $err = array_merge($err, $x);
  if ($err) backWith(implode(' ', $err));

  $sql = "INSERT INTO torneos
          (nombre,creador_id,proveedor_id,fecha_inicio,fecha_fin,estado,tipo,capacidad,puntos_ganador)
          VALUES (?,?,?,?,?,?,?,?,?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("siissssii",
    $nombre,$creador_id,$proveedor_id,$fecha_inicio,$fecha_fin,$estado,$tipo,$capacidad,$puntos
  );
  $stmt->execute(); $stmt->close();
  backWith('Torneo creado.');
}

if ($action === 'edit') {
  $torneo_id     = (int)($_POST['torneo_id'] ?? 0);
  if ($torneo_id <= 0) backWith('ID inválido.');

  $nombre        = trim($_POST['nombre'] ?? '');
  $creador_id    = (int)($_POST['creador_id'] ?? 0);
  $proveedor_id  = (int)($_POST['proveedor_id'] ?? 0);
  $fecha_inicio  = $_POST['fecha_inicio'] ?? '';
  $fecha_fin     = $_POST['fecha_fin'] ?? '';
  $estado        = $_POST['estado'] ?? 'abierto';
  $tipo          = $_POST['tipo'] ?? 'equipo';
  $capacidad     = (int)($_POST['capacidad'] ?? 0);
  $puntos        = (int)($_POST['puntos_ganador'] ?? 0);

  $err=[]; if ($nombre==='') $err[]='Nombre requerido.';
  $x=[]; validarDatos(compact('estado','tipo','fecha_inicio','fecha_fin','capacidad','puntos') + ['puntos_ganador'=>$puntos], $x);
  $err = array_merge($err, $x);
  if ($err) backWith(implode(' ', $err));

  $sql = "UPDATE torneos SET
            nombre=?, creador_id=?, proveedor_id=NULLIF(?,0),
            fecha_inicio=?, fecha_fin=?, estado=?, tipo=?, capacidad=?, puntos_ganador=?
          WHERE torneo_id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("siissssiii",
    $nombre,$creador_id,$proveedor_id,$fecha_inicio,$fecha_fin,$estado,$tipo,$capacidad,$puntos,$torneo_id
  );
  $stmt->execute(); $stmt->close();
  backWith('Torneo actualizado.');
}

if ($action === 'delete') {
  if ($rol === 'admin') backWith('El administrador no puede eliminar torneos.');
  $torneo_id = (int)($_POST['torneo_id'] ?? 0);
  if ($torneo_id > 0) {
    $stmt = $conn->prepare("DELETE FROM torneos WHERE torneo_id=?");
    $stmt->bind_param("i",$torneo_id);
    $stmt->execute(); $stmt->close();
  }
  backWith('Torneo eliminado.');
}
backWith();