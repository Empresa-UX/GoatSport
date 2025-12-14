<?php
include './../../config.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
  $stmt = $conn->prepare("
    INSERT INTO reportes (nombre_reporte, descripcion, respuesta_proveedor, usuario_id, cancha_id, reserva_id, fecha_reporte, estado, tipo_falla)
    VALUES (?,?,?,?,?,?,?,?,?)
  ");
  $tipo_falla = $_POST['tipo_falla'] ?? 'cancha';
  $stmt->bind_param(
    "sssiiisss",
    $_POST['nombre_reporte'],
    $_POST['descripcion'],
    $_POST['respuesta_proveedor'],
    $_POST['usuario_id'],
    $_POST['cancha_id'],
    $_POST['reserva_id'],
    $_POST['fecha_reporte'],
    $_POST['estado'],
    $tipo_falla
  );
  $stmt->execute(); $stmt->close();
  header('Location: reportes.php'); exit;
}

if ($action === 'edit') {
  $stmt = $conn->prepare("
    UPDATE reportes 
    SET nombre_reporte=?, descripcion=?, respuesta_proveedor=?, usuario_id=?, cancha_id=?, reserva_id=?, fecha_reporte=?, estado=?, tipo_falla=?
    WHERE id=?
  ");
  $tipo_falla = $_POST['tipo_falla'] ?? 'cancha';
  $stmt->bind_param(
    "sssiiisssi",
    $_POST['nombre_reporte'],
    $_POST['descripcion'],
    $_POST['respuesta_proveedor'],
    $_POST['usuario_id'],
    $_POST['cancha_id'],
    $_POST['reserva_id'],
    $_POST['fecha_reporte'],
    $_POST['estado'],
    $tipo_falla,
    $_POST['id']
  );
  $stmt->execute(); $stmt->close();
  header('Location: reportes.php'); exit;
}

if ($action === 'delete') {
  $stmt = $conn->prepare("DELETE FROM reportes WHERE id=?");
  $stmt->bind_param("i", $_POST['id']);
  $stmt->execute(); $stmt->close();
  header('Location: reportes.php'); exit;
}

/* === Nuevo: actualizar solo el estado (Pendiente -> Resuelto) y notificar al autor === */
if ($action === 'update_estado') {
  $id     = (int)($_POST['id'] ?? 0);
  $estado = $_POST['estado'] ?? '';

  $ok = false;
  $notified = false;

  if ($id > 0 && $estado === 'Resuelto') {
    // Actualiza estado si estaba Pendiente
    $stmt = $conn->prepare("UPDATE reportes SET estado='Resuelto' WHERE id=? AND estado='Pendiente'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    if ($ok) {
      // Buscar autor y título para notificación
      $q = $conn->prepare("SELECT usuario_id, nombre_reporte FROM reportes WHERE id=?");
      $q->bind_param("i", $id);
      $q->execute();
      $rep = $q->get_result()->fetch_assoc();
      $q->close();

      if ($rep && (int)$rep['usuario_id'] > 0) {
        $uid = (int)$rep['usuario_id'];
        $tituloN = "Reporte resuelto";
        $mensajeN = "Tu reporte «".$rep['nombre_reporte']."» fue marcado como Resuelto.";
        $tipoN = "reporte_resuelto";
        $n = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje, creada_en, leida) VALUES (?, ?, 'sistema', ?, ?, NOW(), 0)");
        $n->bind_param("isss", $uid, $tipoN, $tituloN, $mensajeN);
        $notified = $n->execute();
        $n->close();
      }
    }
  }

  header('Content-Type: application/json');
  echo json_encode(['ok' => $ok, 'notified' => $notified]);
  exit;
}

/* fallback */
header('Location: reportes.php'); exit;
