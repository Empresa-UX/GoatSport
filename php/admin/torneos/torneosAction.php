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

/* add/edit quedan por compatibilidad, pero ya no se usan desde la UI */
if ($action === 'add') {
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
  $torneo_id = (int)($_POST['torneo_id'] ?? 0);
  if ($torneo_id <= 0) backWith('ID inválido.');

  /* 1) Traer datos del torneo antes de borrar */
  $stmt = $conn->prepare("
    SELECT t.torneo_id, t.nombre, t.creador_id, t.proveedor_id, t.fecha_inicio, t.fecha_fin
    FROM torneos t
    WHERE t.torneo_id = ?
  ");
  $stmt->bind_param("i", $torneo_id);
  $stmt->execute();
  $info = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($info) {
    $nombre       = (string)$info['nombre'];
    $creador_id   = (int)$info['creador_id'];
    $proveedor_id = (int)($info['proveedor_id'] ?? 0);
    $fi           = $info['fecha_inicio'] ?? null;
    $ff           = $info['fecha_fin'] ?? null;

    $fi_lbl = $fi ? date('d/m', strtotime($fi)) : '—';
    $ff_lbl = $ff ? date('d/m', strtotime($ff)) : '—';

    /* 2) Borrar torneo */
    $del = $conn->prepare("DELETE FROM torneos WHERE torneo_id=?");
    $del->bind_param("i", $torneo_id);
    $del->execute();
    $del->close();

    /* 3) Armar destinatarios: proveedor, recepcionistas del proveedor, creador (cliente) */
    $destinatarios = [];

    if ($proveedor_id > 0) {
      $destinatarios[$proveedor_id] = true;

      // Recepcionistas asignados a este proveedor
      $rs = $conn->prepare("SELECT recepcionista_id FROM recepcionista_detalle WHERE proveedor_id=?");
      $rs->bind_param("i", $proveedor_id);
      $rs->execute();
      $rres = $rs->get_result();
      while ($row = $rres->fetch_assoc()) {
        $destinatarios[(int)$row['recepcionista_id']] = true;
      }
      $rs->close();
    }

    if ($creador_id > 0) {
      $destinatarios[$creador_id] = true;
    }

    /* 4) Insertar notificaciones */
    if (!empty($destinatarios)) {
      $titulo  = "Torneo eliminado: {$nombre}";
      $mensaje = "El torneo \"{$nombre}\" (del {$fi_lbl} al {$ff_lbl}) ha sido eliminado.";

      $ins = $conn->prepare("
        INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
        VALUES (?, 'torneo_eliminado', 'sistema', ?, ?)
      ");

      foreach (array_keys($destinatarios) as $uid) {
        $uid = (int)$uid;
        if ($uid <= 0) continue;
        $ins->bind_param("iss", $uid, $titulo, $mensaje);
        $ins->execute();
      }
      $ins->close();
    }
  }

  backWith('Torneo eliminado.');
}

backWith();
