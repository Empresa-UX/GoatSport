<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\torneos\salirTorneo.php
 * ========================================================================= */
session_start();
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') { header("Location: /php/login.php"); exit; }

$cliente_id = (int)$_SESSION['usuario_id'];
$torneo_id  = isset($_POST['torneo_id']) ? (int)$_POST['torneo_id'] : 0;
if ($torneo_id <= 0) { header("Location: /php/cliente/torneos/torneos.php?err=Solicitud inválida"); exit; }

/* Torneo válido */
$st = $conn->prepare("SELECT t.torneo_id, t.nombre, t.proveedor_id, COALESCE(u.nombre,'—') AS club FROM torneos t LEFT JOIN usuarios u ON u.user_id=t.proveedor_id WHERE t.torneo_id=? LIMIT 1");
$st->bind_param("i", $torneo_id);
$st->execute();
$torneo = $st->get_result()->fetch_assoc();
$st->close();
if (!$torneo) { header("Location: /php/cliente/torneos/torneos.php?err=Torneo no disponible"); exit; }

/* ¿Estoy inscripto? */
$st = $conn->prepare("SELECT participacion_id FROM participaciones WHERE jugador_id=? AND torneo_id=? LIMIT 1");
$st->bind_param("ii", $cliente_id, $torneo_id);
$st->execute();
$par = $st->get_result()->fetch_assoc();
$st->close();
if (!$par) { header("Location: /php/cliente/torneos/torneos.php?ok=No estabas inscripto"); exit; }

/* Eliminar participación */
$st = $conn->prepare("DELETE FROM participaciones WHERE jugador_id=? AND torneo_id=? LIMIT 1");
$st->bind_param("ii", $cliente_id, $torneo_id);
$st->execute();
$st->close();

/* Notificaciones */
$tituloJugador  = "Saliste del torneo";
$mensajeJugador = "Has salido del torneo \"".$torneo['nombre']."\" en el club ".$torneo['club'].".";
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
    $tituloProv  = "Un jugador salió de \"".$torneo['nombre']."\"";
    $mensajeProv = ($jug ? $jug['nombre'] : 'Un jugador')." se dio de baja del torneo.";
    $st = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'torneo', ?, ?)");
    $st->bind_param("iss", $provId, $tituloProv, $mensajeProv);
    $st->execute();
    $st->close();
}

header("Location: /php/cliente/torneos/torneos.php?ok=Te diste de baja");
exit;
