
<?php
/* =====================================================================
 * file: php/recepcionista/reportes/reportesAction.php
 * ===================================================================== */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$recep_id     = (int)($_SESSION['usuario_id'] ?? 0);
$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);
if (!$recep_id || !$proveedor_id) { header('Location: ../login.php'); exit; }

$action = $_POST['action'] ?? '';

function back_ok(string $m){ header('Location: reportes.php?ok='.rawurlencode($m)); exit; }
function back_err(string $m){ header('Location: reportes.php?err='.rawurlencode($m)); exit; }

function notify_admins(mysqli $conn, string $tipo, string $origen, string $titulo, string $mensaje): void {
  $sql = "INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
          SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol='admin'";
  $st=$conn->prepare($sql); $st->bind_param("ssss",$tipo,$origen,$titulo,$mensaje); $st->execute(); $st->close();
}

if ($action === 'create') {
  $nombre   = trim($_POST['nombre_reporte'] ?? '');
  $desc     = trim($_POST['descripcion'] ?? '');
  $tipo     = $_POST['tipo_falla'] ?? 'cancha';
  $canchaId = isset($_POST['cancha_id']) ? (int)$_POST['cancha_id'] : 0;
  $reserva  = trim($_POST['reserva_id'] ?? '');

  if ($nombre === '' || $desc === '') back_err('Datos inválidos');
  if (!in_array($tipo, ['sistema','cancha'], true)) $tipo='cancha';

  $hoy = date('Y-m-d'); // why: fecha SIEMPRE HOY

  // normalización y validación condicional
  $cancha_id = null;
  $reserva_id = null;

  if ($tipo === 'cancha') {
    if ($canchaId <= 0) back_err('Seleccioná la cancha');
    if (!ctype_digit($reserva) || (int)$reserva <= 0) back_err('Ingresá un ID de reserva válido');

    $cancha_id = $canchaId;
    $reserva_id = (int)$reserva;

    // validar que la cancha sea del proveedor
    $chk=$conn->prepare("SELECT 1 FROM canchas WHERE cancha_id=? AND proveedor_id=?");
    $chk->bind_param("ii",$cancha_id,$proveedor_id); $chk->execute();
    $ok=$chk->get_result()->fetch_row(); $chk->close();
    if(!$ok) back_err('La cancha no pertenece a tu club');
  }

  $ins=$conn->prepare("
    INSERT INTO reportes (nombre_reporte, descripcion, respuesta_proveedor, usuario_id, cancha_id, reserva_id, fecha_reporte, estado, tipo_falla)
    VALUES (?,?,?,?,?,?,CURDATE(), 'Pendiente', ?)
  ");
  $respuesta = null; // why: nullable
  $ins->bind_param("sssiiis", $nombre, $desc, $respuesta, $recep_id, $cancha_id, $reserva_id, $tipo);

  try {
    $ins->execute(); $newId = $conn->insert_id; $ins->close();
  } catch (Throwable $e) {
    back_err('No se pudo crear el reporte');
  }

  notify_admins($conn,'reporte_nuevo','recepcion',"Nuevo reporte (#{$newId})","Tipo: {$tipo}. Título: {$nombre}.");
  back_ok('Reporte creado');
}

back_err('Acción inválida');