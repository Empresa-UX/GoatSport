<?php
/* ========================================================================
 * file: recepcionista/canchas/canchasAction.php
 * Procesa aprobar / denegar / eliminar + notificaciones al proveedor
 * ======================================================================== */
include './../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/* --- Guardrails --- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405); echo 'Método no permitido'; exit;
}
$action    = $_POST['action']    ?? '';
$cancha_id = (int)($_POST['cancha_id'] ?? 0);
$csrf_post = $_POST['csrf']      ?? '';

if (!$cancha_id || !in_array($action, ['aprobar','denegar','eliminar'], true)) {
  http_response_code(400); echo 'Parámetros inválidos'; exit;
}
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf_post)) {
  http_response_code(403); echo 'CSRF inválido'; exit;
}

/* --- Traer info antes de mutar --- */
$stmt = $conn->prepare("SELECT cancha_id, proveedor_id, nombre FROM canchas WHERE cancha_id = ?");
$stmt->bind_param('i', $cancha_id);
$stmt->execute();
$cancha = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cancha) { http_response_code(404); echo 'Cancha no encontrada'; exit; }

$proveedor_id = (int)$cancha['proveedor_id'];
$nombreCancha = (string)$cancha['nombre'];

/* --- Ejecutar acción --- */
$ok = false; $titulo=''; $mensaje=''; $tipo='';
if ($action === 'aprobar') {
  $stmt = $conn->prepare("UPDATE canchas SET estado='aprobado' WHERE cancha_id=?");
  $stmt->bind_param('i', $cancha_id);
  $ok = $stmt->execute(); $stmt->close();
  $titulo='Cancha aprobada';
  $mensaje="Tu cancha «{$nombreCancha}» fue aprobada y ya está visible.";
  $tipo='cancha_aprobada';
} elseif ($action === 'denegar') {
  $stmt = $conn->prepare("UPDATE canchas SET estado='denegado' WHERE cancha_id=?");
  $stmt->bind_param('i', $cancha_id);
  $ok = $stmt->execute(); $stmt->close();
  $titulo='Cancha denegada';
  $mensaje="Tu cancha «{$nombreCancha}» fue denegada. Podés revisar y reenviar.";
  $tipo='cancha_denegada';
} else { // eliminar
  /* Por qué: se elimina definitivamente a pedido; FKs en reservas → ON DELETE CASCADE. */
  $stmt = $conn->prepare("DELETE FROM canchas WHERE cancha_id=?");
  $stmt->bind_param('i', $cancha_id);
  $ok = $stmt->execute(); $stmt->close();
  $titulo='Cancha eliminada';
  $mensaje="Tu cancha «{$nombreCancha}» fue eliminada por el administrador.";
  $tipo='cancha_eliminada';
}

/* --- Notificación (no bloqueante) --- */
if ($ok) {
  $stmt = $conn->prepare("
    INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje, creada_en, leida)
    VALUES (?, ?, 'sistema', ?, ?, NOW(), 0)
  ");
  if ($stmt) {
    $stmt->bind_param('isss', $proveedor_id, $tipo, $titulo, $mensaje);
    $stmt->execute();
    $stmt->close();
  }
}

/* --- Redirigir de vuelta --- */
$back = $_SERVER['HTTP_REFERER'] ?? null;
/* Fallback según contexto por claridad */
if (!$back) {
  $back = ($action === 'aprobar' || $action === 'denegar')
    ? './../admin/canchasPendientes.php'
    : './canchas.php';
}
header('Location: ' . $back);
exit;
