<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\torneos\unirseTorneo.php
 * ========================================================================= */
session_start();
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') { header("Location: /php/login.php"); exit; }

$cliente_id = (int)$_SESSION['usuario_id'];
$torneo_id  = isset($_POST['torneo_id']) ? (int)$_POST['torneo_id'] : 0;
$return     = isset($_POST['return']) ? $_POST['return'] : '';
if ($torneo_id <= 0) { header("Location: /php/cliente/torneos/torneos.php?err=Solicitud inválida"); exit; }

/* Torneo válido + reglas */
$st = $conn->prepare("
  SELECT t.torneo_id, t.nombre, t.estado, t.proveedor_id, t.capacidad, t.fecha_inicio,
         COALESCE(prov.nombre,'—') AS club,
         (SELECT COUNT(*) FROM participaciones p WHERE p.torneo_id=t.torneo_id AND p.estado='aceptada') AS inscriptos
  FROM torneos t
  LEFT JOIN usuarios prov ON prov.user_id = t.proveedor_id
  WHERE t.torneo_id=? LIMIT 1
");
$st->bind_param("i", $torneo_id);
$st->execute();
$torneo = $st->get_result()->fetch_assoc();
$st->close();

if (!$torneo) { header("Location: /php/cliente/torneos/torneos.php?err=Torneo no disponible"); exit; }

$hoy = date('Y-m-d');
$capacidad  = (int)$torneo['capacidad'];
$inscr      = (int)$torneo['inscriptos'];
if ($torneo['estado'] !== 'abierto') { header("Location: /php/cliente/torneos/torneos.php?err=Torneo cerrado"); exit; }
if ($torneo['fecha_inicio'] < $hoy)  { header("Location: /php/cliente/torneos/torneos.php?err=El torneo ya comenzó"); exit; }
if ($capacidad > 0 && $inscr >= $capacidad) { header("Location: /php/cliente/torneos/torneos.php?err=No hay cupos disponibles"); exit; }

/* Ya inscripto? */
$st = $conn->prepare("SELECT participacion_id FROM participaciones WHERE jugador_id=? AND torneo_id=? LIMIT 1");
$st->bind_param("ii", $cliente_id, $torneo_id);
$st->execute();
$ya = $st->get_result()->fetch_assoc();
$st->close();

if ($ya) {
    $msg = "Ya estabas inscripto";
} else {
    $st = $conn->prepare("INSERT INTO participaciones (jugador_id, reserva_id, torneo_id, es_creador, estado) VALUES (?, NULL, ?, 0, 'aceptada')");
    $st->bind_param("ii", $cliente_id, $torneo_id);
    $st->execute();
    $st->close();

    $tituloJugador  = "Inscripción confirmada";
    $mensajeJugador = "Te uniste al torneo \"".$torneo['nombre']."\" en el club ".$torneo['club'].".";
    $st = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'torneo', ?, ?)");
    $st->bind_param("iss", $cliente_id, $tituloJugador, $mensajeJugador);
    $st->execute();
    $st->close();

    if (!empty($torneo['proveedor_id'])) {
        $st = $conn->prepare("SELECT nombre FROM usuarios WHERE user_id=? LIMIT 1");
        $st->bind_param("i", $cliente_id);
        $st->execute();
        $jug = $st->get_result()->fetch_assoc();
        $st->close();

        $provId      = (int)$torneo['proveedor_id'];
        $tituloProv  = "Nuevo inscripto en \"".$torneo['nombre']."\"";
        $mensajeProv = ($jug ? $jug['nombre'] : 'Un jugador')." se inscribió en tu torneo.";
        $st = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'torneo', ?, ?)");
        $st->bind_param("iss", $provId, $tituloProv, $mensajeProv);
        $st->execute();
        $st->close();
    }

    $msg = "Inscripción exitosa";
}

/* Redirect */
$destinoSeguro = '/php/cliente/torneos/torneos.php';
if ($return && str_starts_with($return, '/php/cliente/torneos/')) { $destinoSeguro = $return; }
$sep = (strpos($destinoSeguro,'?') !== false) ? '&' : '?';
header("Location: ".$destinoSeguro.$sep."ok=".urlencode($msg));
exit;
