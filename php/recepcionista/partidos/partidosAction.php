<?php
/* =========================================================================
 * file: php/recepcionista/partidos/partidosAction.php  (REEMPLAZA COMPLETO)
 * ========================================================================= */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config.php';

$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);
$action       = $_POST['action'] ?? '';

function redirect_with(string $base, array $params = []): void {
  $qs = http_build_query($params);
  $sep = (strpos($base, '?') === false) ? '?' : '&';
  header('Location: ' . $base . ($qs ? $sep . $qs : ''));
  exit;
}
function ok(string $msg, ?string $fecha=null): void {
  $params = ['ok'=>$msg];
  if ($fecha) $params['fecha']=$fecha;
  redirect_with('partidos.php', $params);
}
function err(string $msg, ?string $fecha=null): void {
  $params = ['err'=>$msg];
  if ($fecha) $params['fecha']=$fecha;
  redirect_with('partidos.php', $params);
}

if ($action === 'save_result') {
  $partido_id = (int)($_POST['partido_id'] ?? 0);
  $resultado  = trim($_POST['resultado'] ?? '');
  $ganador_id = (int)($_POST['ganador_id'] ?? 0);

  if ($partido_id<=0 || $resultado==='') err('Datos inválidos');

  $sql = "
    SELECT p.partido_id, p.jugador1_id, p.jugador2_id, p.fecha,
           t.proveedor_id AS prov_torneo,
           c.proveedor_id AS prov_cancha
    FROM partidos p
    LEFT JOIN torneos t  ON t.torneo_id = p.torneo_id
    LEFT JOIN reservas r ON r.reserva_id = p.reserva_id
    LEFT JOIN canchas  c ON c.cancha_id  = r.cancha_id
    WHERE p.partido_id = ?
    LIMIT 1
  ";
  $st = $conn->prepare($sql);
  $st->bind_param("i", $partido_id);
  $st->execute();
  $info = $st->get_result()->fetch_assoc();
  $st->close();

  if (!$info) err('Partido inexistente');
  $fechaPartido = date('Y-m-d', strtotime($info['fecha']));

  $prov_t = (int)($info['prov_torneo'] ?? 0);
  $prov_c = (int)($info['prov_cancha'] ?? 0);
  if ($prov_t !== $proveedor_id && $prov_c !== $proveedor_id) err('No autorizado', $fechaPartido);

  $j1 = (int)$info['jugador1_id']; 
  $j2 = (int)$info['jugador2_id'];
  if ($ganador_id !== $j1 && $ganador_id !== $j2) err('Ganador inválido', $fechaPartido);

  $up = $conn->prepare("UPDATE partidos SET resultado = ?, ganador_id = ? WHERE partido_id = ?");
  $up->bind_param("sii", $resultado, $ganador_id, $partido_id);
  $up->execute(); 
  $up->close();

  ok('Resultado guardado', $fechaPartido);
}

err('Acción inválida');
