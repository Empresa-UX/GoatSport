<?php
/* =========================================================================
 * Acciones de partidos (Proveedor)
 *  - save_result: guarda resultado + ganador, actualiza ranking y puntos,
 *                 empuja ganador al siguiente partido del bracket,
 *                 marca torneo "finalizado" si fue la final.
 *  (SIN CSRF)
 * ========================================================================= */
require_once __DIR__ . '/../../config.php';
if (session_status()===PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') { header('Location: ../login.php'); exit; }

$proveedor_id = (int)$_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

function back($url){ header('Location: '.$url); exit; }

function notificar(mysqli $c, int $u, string $tipo, string $tit, string $msg){
  $st=$c->prepare("INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje) VALUES (?, ?, 'sistema', ?, ?)");
  if($st){ $st->bind_param("isss",$u,$tipo,$tit,$msg); $st->execute(); $st->close(); }
}

/** asegura fila de ranking para el usuario (sin UNIQUE en usuario_id) */
function ensure_ranking_row(mysqli $c, int $uid){
  $st = $c->prepare("SELECT ranking_id FROM ranking WHERE usuario_id=? LIMIT 1");
  $st->bind_param("i",$uid); $st->execute(); $r=$st->get_result()->fetch_assoc(); $st->close();
  if (!$r) {
    $ins = $c->prepare("INSERT INTO ranking (usuario_id, puntos, partidos, victorias, derrotas) VALUES (?, 0, 0, 0, 0)");
    $ins->bind_param("i",$uid); $ins->execute(); $ins->close();
  }
}

/** incrementos de ranking atómicos */
function add_ranking_stats(mysqli $c, int $uid, int $pDelta, int $wDelta, int $lDelta){
  ensure_ranking_row($c,$uid);
  $st=$c->prepare("UPDATE ranking SET partidos=partidos+?, victorias=victorias+?, derrotas=derrotas+? WHERE usuario_id=?");
  $st->bind_param("iiii",$pDelta,$wDelta,$lDelta,$uid); $st->execute(); $st->close();
}

/** suma puntos al usuario en ranking */
function add_ranking_points(mysqli $c, int $uid, int $pts){
  if ($pts<=0) return;
  ensure_ranking_row($c,$uid);
  $st=$c->prepare("UPDATE ranking SET puntos=puntos+? WHERE usuario_id=?");
  $st->bind_param("ii",$pts,$uid); $st->execute(); $st->close();
}

if ($action === 'save_result') {
  $partido_id = (int)($_POST['partido_id'] ?? 0);
  $resultado  = trim($_POST['resultado'] ?? '');
  $ganador_id = (int)($_POST['ganador_id'] ?? 0);

  if ($partido_id<=0) back('torneos.php');

  // Traer partido + torneo y validar propiedad
  $sql = "
    SELECT p.*, t.nombre AS torneo_nombre, t.proveedor_id, t.puntos_ganador
    FROM partidos p INNER JOIN torneos t ON t.torneo_id=p.torneo_id
    WHERE p.partido_id=? AND t.proveedor_id=? LIMIT 1
  ";
  $st=$conn->prepare($sql);
  $st->bind_param("ii",$partido_id,$proveedor_id);
  $st->execute(); $res=$st->get_result();
  $p=$res?$res->fetch_assoc():null; $st->close();
  if (!$p) back('torneos.php');
  if (!$p['jugador1_id'] || !$p['jugador2_id']) back('torneoCronograma.php?torneo_id='.(int)$p['torneo_id']);

  $j1 = (int)$p['jugador1_id']; $j2 = (int)$p['jugador2_id'];
  if ($ganador_id!==$j1 && $ganador_id!==$j2) {
    $_SESSION['flash_errors']=['El ganador debe ser uno de los dos jugadores.'];
    back('partidoResultado.php?partido_id='.$partido_id);
  }

  // Guardar resultado y ganador
  $st=$conn->prepare("UPDATE partidos SET resultado=?, ganador_id=? WHERE partido_id=?");
  $st->bind_param("sii",$resultado,$ganador_id,$partido_id);
  $st->execute(); $st->close();

  // Ranking: +1 partido cada uno; +1 victoria ganador, +1 derrota perdedor
  $perdedor = ($ganador_id===$j1)?$j2:$j1;
  add_ranking_stats($conn,$j1,1, ($ganador_id===$j1)?1:0, ($ganador_id===$j1)?0:1);
  add_ranking_stats($conn,$j2,1, ($ganador_id===$j2)?1:0, ($ganador_id===$j2)?0:1);

  // Puntos al ganador
  $pts = (int)($p['puntos_ganador'] ?? 0);
  if ($pts>0) {
    $ins=$conn->prepare("INSERT INTO puntos_historial (usuario_id, origen, referencia_id, puntos, descripcion) VALUES (?, 'torneo', ?, ?, ?)");
    $desc = 'Puntos por victoria en torneo: '.$p['torneo_nombre'];
    $ins->bind_param("iiis",$ganador_id,$p['torneo_id'],$pts,$desc);
    $ins->execute(); $ins->close();

    add_ranking_points($conn,$ganador_id,$pts);
  }

  // Avance en bracket: buscar siguiente partido y sentar ganador en j1/j2
  // Avance en bracket: usar next_partido_id / next_pos del propio partido
  $bm = $conn->prepare("SELECT next_partido_id, next_pos FROM partidos WHERE partido_id=?");
  $bm->bind_param("i",$partido_id);
  $bm->execute(); $bmr=$bm->get_result(); $map=$bmr?$bmr->fetch_assoc():null; $bm->close();

  if ($map && !empty($map['next_partido_id'])) {
    $nextId = (int)$map['next_partido_id'];
    $pos    = (string)$map['next_pos'];

    // leemos para no pisar si ya está seteado
    $nx = $conn->prepare("SELECT jugador1_id, jugador2_id FROM partidos WHERE partido_id=? LIMIT 1");
    $nx->bind_param("i",$nextId);
    $nx->execute(); $nxr=$nx->get_result(); $nrow=$nxr?$nxr->fetch_assoc():null; $nx->close();

    if ($nrow) {
      if ($pos==='j1' && empty($nrow['jugador1_id'])) {
        $up=$conn->prepare("UPDATE partidos SET jugador1_id=? WHERE partido_id=?");
        $up->bind_param("ii",$ganador_id,$nextId); $up->execute(); $up->close();
      } elseif ($pos==='j2' && empty($nrow['jugador2_id'])) {
        $up=$conn->prepare("UPDATE partidos SET jugador2_id=? WHERE partido_id=?");
        $up->bind_param("ii",$ganador_id,$nextId); $up->execute(); $up->close();
      }
    }
  } else {
    // No hay siguiente => fue la final: cerrar torneo y notificar
    $conn->query("UPDATE torneos SET estado='finalizado' WHERE torneo_id=".(int)$p['torneo_id']." LIMIT 1");
    notificar($conn, $ganador_id, 'torneo_ganado', '¡Ganaste el torneo!', 'Felicitaciones: ganaste el torneo "'.$p['torneo_nombre'].'".');
  }


  back('torneoCronograma.php?torneo_id='.(int)$p['torneo_id']);
}

/* fallback */
back('torneos.php');
