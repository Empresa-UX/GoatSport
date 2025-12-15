<?php
// ======================================================================
// file: php/proveedor/notificaciones/notificacionesAction.php
// Marca como leída (rol proveedor). No se puede desmarcar.
// ======================================================================
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header("Location: ../login.php"); exit;
}

/* CSRF */
if (empty($_SESSION['csrf']) || ($_POST['csrf'] ?? '') !== $_SESSION['csrf']) {
  if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(400); exit; }
  header("Location: notificaciones.php?err=" . urlencode("CSRF inválido")); exit;
}

$proveedor_id = (int)$_SESSION['usuario_id'];
$uids = [$proveedor_id];

function placeholders(int $n): string { return implode(',', array_fill(0, $n, '?')); }
function typesFor(array $uids): string { return str_repeat('i', count($uids)); }

$action = $_POST['action'] ?? '';

if ($action === 'mark_read') {
  $nid = (int)($_POST['notificacion_id'] ?? 0);
  if ($nid <= 0) {
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(422); exit; }
    header("Location: notificaciones.php?err=" . urlencode("ID inválido")); exit;
  }
  $ph = placeholders(count($uids));
  $sql = "UPDATE notificaciones SET leida = 1 WHERE notificacion_id = ? AND usuario_id IN ($ph) LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i' . typesFor($uids), $nid, ...$uids);
  $stmt->execute(); $stmt->close();

  if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(204); exit; }
  header("Location: notificaciones.php?ok=" . urlencode("Marcada como leída")); exit;
}

/* Marcado masivo opcional */
if ($action === 'mark_all_read') {
  $ph  = placeholders(count($uids));
  $sql = "UPDATE notificaciones SET leida = 1 WHERE usuario_id IN ($ph) AND leida = 0";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param(typesFor($uids), ...$uids);
  $stmt->execute(); $stmt->close();

  if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(204); exit; }
  header("Location: notificaciones.php?ok=" . urlencode("Todas marcadas como leídas")); exit;
}

if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(400); exit; }
header("Location: notificaciones.php");
exit;
