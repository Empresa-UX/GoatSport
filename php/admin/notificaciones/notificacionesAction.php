<?php
// ======================================================================
// file: admin/notificaciones/notificacionesAction.php
// Marca una o todas como leídas (rol admin)
// ======================================================================
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* CSRF */
if (empty($_SESSION['csrf']) || ($_POST['csrf'] ?? '') !== $_SESSION['csrf']) {
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
        http_response_code(400);
        exit;
    }
    header("Location: notificaciones.php?err=" . urlencode("CSRF inválido"));
    exit;
}

$admin_id = (int)$_SESSION['usuario_id'];
$uids = [$admin_id];

function placeholders(int $n): string { return implode(',', array_fill(0, $n, '?')); }
function typesFor(array $uids): string { return str_repeat('i', count($uids)); }

$action = $_POST['action'] ?? '';

if ($action === 'mark_all_read') {
    $ph = placeholders(count($uids));
    $sql = "UPDATE notificaciones SET leida = 1 WHERE usuario_id IN ($ph) AND leida = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(typesFor($uids), ...$uids);
    $stmt->execute(); $stmt->close();

    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(204); exit; }
    header("Location: notificaciones.php?ok=" . urlencode("Todas marcadas como leídas"));
    exit;
}

if ($action === 'toggle_read') {
    $nid   = (int)($_POST['notificacion_id'] ?? 0);
    $state = ($_POST['state'] ?? '1') === '1' ? 1 : 0;
    if ($nid <= 0) {
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(422); exit; }
        header("Location: notificaciones.php?err=" . urlencode("ID inválido"));
        exit;
    }
    $ph = placeholders(count($uids));
    $sql = "UPDATE notificaciones SET leida = ? WHERE notificacion_id = ? AND usuario_id IN ($ph) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii' . typesFor($uids), $state, $nid, ...$uids);
    $stmt->execute(); $stmt->close();

    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(204); exit; }
    header("Location: notificaciones.php?ok=" . urlencode($state ? "Marcada como leída" : "Marcada como no leída"));
    exit;
}

if ($action === 'mark_read' || $action === 'mark_unread') {
    $nid   = (int)($_POST['notificacion_id'] ?? 0);
    $state = $action === 'mark_read' ? 1 : 0;
    if ($nid <= 0) {
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(422); exit; }
        header("Location: notificaciones.php?err=" . urlencode("ID inválido"));
        exit;
    }
    $ph = placeholders(count($uids));
    $sql = "UPDATE notificaciones SET leida = ? WHERE notificacion_id = ? AND usuario_id IN ($ph) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii' . typesFor($uids), $state, $nid, ...$uids);
    $stmt->execute(); $stmt->close();

    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(204); exit; }
    header("Location: notificaciones.php?ok=" . urlencode($state ? "Marcada como leída" : "Marcada como no leída"));
    exit;
}

if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(400); exit; }
header("Location: notificaciones.php");
exit;
