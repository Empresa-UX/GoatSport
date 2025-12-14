<?php
/* =========================================================================
 * file: admin/usuarios/usuariosAction.php (MOD)
 * - delete: después de borrar, notifica a TODOS los recepcionistas
 * - aprobar/denegar proveedor: cambia proveedores_detalle.estado
 * ========================================================================= */
include __DIR__ . '/../../config.php';

$action = $_REQUEST['action'] ?? '';
$rol    = $_REQUEST['rol'] ?? ($_POST['action'] ?? 'clientes'); // compat
if (!in_array(($rol ?? 'clientes'),['clientes','proveedores','recepcionistas'], true)) { /* ok */ }

if ($action === 'add' || $action === 'edit') {
  // (sin cambios: tu flujo actual)
  // ...
}

/* === NUEVO: aprobar / denegar proveedor === */
if ($action === 'approve_proveedor' || $action === 'deny_proveedor') {
  $user_id = (int)($_POST['user_id'] ?? 0);
  if ($user_id > 0) {
    $nuevo = ($action === 'approve_proveedor') ? 'aprobado' : 'rechazado';
    $stmt = $conn->prepare("UPDATE proveedores_detalle SET estado=? WHERE proveedor_id=?");
    $stmt->bind_param("si", $nuevo, $user_id);
    $stmt->execute(); $stmt->close();
  }
  $dest = './proveedoresPendientes.php';
  if ($action === 'approve_proveedor') $dest = './usuarios.php?view=proveedores';
  header("Location: {$dest}");
  exit;
}

/* === DELETE (modificado para notificar recepcionistas) === */
if ($action === 'delete') {
  $rol = $_POST['rol'] ?? 'clientes';
  $user_id = (int)($_POST['user_id'] ?? 0);

  // Traer datos de usuario para mensaje (antes de borrar)
  $u = null;
  if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT user_id, nombre, email, rol FROM usuarios WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }

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

    // Notificar a TODOS los recepcionistas
    if ($u) {
      $rs = $conn->query("SELECT user_id FROM usuarios WHERE rol='recepcionista'");
      if ($rs) {
        $receps = $rs->fetch_all(MYSQLI_ASSOC);
        $titulo = "Usuario eliminado";
        $mensaje = "Se eliminó el usuario «{$u['nombre']}» ({$u['email']}) de rol {$u['rol']}.";
        $tipo = "usuario_eliminado";
        $ins = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje, creada_en, leida) VALUES (?, ?, 'sistema', ?, ?, NOW(), 0)");
        foreach ($receps as $rec) {
          $rid = (int)$rec['user_id'];
          $ins->bind_param('isss', $rid, $tipo, $titulo, $mensaje);
          $ins->execute();
        }
        $ins->close();
      }
    }
  }
  header("Location: usuarios.php?view={$rol}");
  exit;
}

/* fallback */
header("Location: usuarios.php?view=clientes");
exit;
