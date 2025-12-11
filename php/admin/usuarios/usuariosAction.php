<?php
/* =========================================================================
 * file: admin/usuarios/usuariosAction.php
 * Acciones unificadas por rol: add | edit | delete
 * ========================================================================= */
include __DIR__ . '/../../config.php';

$action = $_REQUEST['action'] ?? '';
$rol    = $_REQUEST['rol'] ?? 'clientes';
if (!in_array($rol,['clientes','proveedores','recepcionistas'], true)) $rol = 'clientes';

if ($action === 'add') {
  $nombre = $_POST['nombre'] ?? '';
  $email  = $_POST['email'] ?? '';
  $pass   = $_POST['contrasenia'] ?? '';

  $rolDb = $rol === 'clientes' ? 'cliente' : ($rol === 'proveedores' ? 'proveedor' : 'recepcionista');
  $puntos = (int)($_POST['puntos'] ?? 0);

  $stmt = $conn->prepare("INSERT INTO usuarios (nombre,email,contrasenia,rol,puntos) VALUES (?,?,?,?,?)");
  $stmt->bind_param("ssssi", $nombre, $email, $pass, $rolDb, $puntos);
  $stmt->execute();
  $newId = $stmt->insert_id;
  $stmt->close();

  if ($rol === 'clientes') {
    $telefono = $_POST['telefono'] ?? null;
    $ciudad   = $_POST['ciudad'] ?? null;
    $q = $conn->prepare("INSERT INTO cliente_detalle (cliente_id, telefono, ciudad) VALUES (?,?,?)");
    $q->bind_param("iss", $newId, $telefono, $ciudad);
    $q->execute(); $q->close();
  } elseif ($rol === 'proveedores') {
    $club = $_POST['nombre_club'] ?? null;
    $tel  = $_POST['telefono'] ?? null;
    $ciu  = $_POST['ciudad'] ?? null;
    $q = $conn->prepare("INSERT INTO proveedores_detalle (proveedor_id, nombre_club, telefono, ciudad) VALUES (?,?,?,?)");
    $q->bind_param("isss", $newId, $club, $tel, $ciu);
    $q->execute(); $q->close();
  } else {
    $prov = (int)($_POST['proveedor_id'] ?? 0);
    $q = $conn->prepare("INSERT INTO recepcionista_detalle (recepcionista_id, proveedor_id) VALUES (?,?)");
    $q->bind_param("ii", $newId, $prov);
    $q->execute(); $q->close();
  }

  header("Location: usuarios.php?view={$rol}");
  exit;
}

if ($action === 'edit') {
  $user_id = (int)($_POST['user_id'] ?? 0);
  if ($user_id <= 0) { header("Location: usuarios.php?view={$rol}"); exit; }

  $nombre = $_POST['nombre'] ?? '';
  $email  = $_POST['email'] ?? '';
  $pass   = $_POST['contrasenia'] ?? '';
  $puntos = (int)($_POST['puntos'] ?? 0);

  $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=?, contrasenia=?, puntos=? WHERE user_id=?");
  $stmt->bind_param("sssii", $nombre, $email, $pass, $puntos, $user_id);
  $stmt->execute(); $stmt->close();

  if ($rol === 'clientes') {
    $telefono = $_POST['telefono'] ?? null;
    $ciudad   = $_POST['ciudad'] ?? null;
    // UPSERT simple
    $q = $conn->prepare("INSERT INTO cliente_detalle (cliente_id, telefono, ciudad) VALUES (?,?,?)
                         ON DUPLICATE KEY UPDATE telefono=VALUES(telefono), ciudad=VALUES(ciudad)");
    $q->bind_param("iss", $user_id, $telefono, $ciudad);
    $q->execute(); $q->close();
  } elseif ($rol === 'proveedores') {
    $club = $_POST['nombre_club'] ?? null;
    $tel  = $_POST['telefono'] ?? null;
    $ciu  = $_POST['ciudad'] ?? null;
    $q = $conn->prepare("INSERT INTO proveedores_detalle (proveedor_id, nombre_club, telefono, ciudad) VALUES (?,?,?,?)
                         ON DUPLICATE KEY UPDATE nombre_club=VALUES(nombre_club), telefono=VALUES(telefono), ciudad=VALUES(ciudad)");
    $q->bind_param("isss", $user_id, $club, $tel, $ciu);
    $q->execute(); $q->close();
  } else {
    $prov = (int)($_POST['proveedor_id'] ?? 0);
    $q = $conn->prepare("INSERT INTO recepcionista_detalle (recepcionista_id, proveedor_id) VALUES (?,?)
                         ON DUPLICATE KEY UPDATE proveedor_id=VALUES(proveedor_id)");
    $q->bind_param("ii", $user_id, $prov);
    $q->execute(); $q->close();
  }

  header("Location: usuarios.php?view={$rol}");
  exit;
}

if ($action === 'delete') {
  $user_id = (int)($_POST['user_id'] ?? 0);
  if ($user_id > 0) {
    if ($rol === 'clientes') {
      $conn->query("DELETE FROM cliente_detalle WHERE cliente_id = {$user_id}");
    } elseif ($rol === 'proveedores') {
      $conn->query("DELETE FROM proveedores_detalle WHERE proveedor_id = {$user_id}");
    } else {
      $conn->query("DELETE FROM recepcionista_detalle WHERE recepcionista_id = {$user_id}");
    }
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute(); $stmt->close();
  }
  header("Location: usuarios.php?view={$rol}");
  exit;
}

/* fallback */
header("Location: usuarios.php?view={$rol}");
exit;
