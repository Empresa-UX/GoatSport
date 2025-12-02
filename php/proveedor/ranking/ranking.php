<?php
// php/proveedor/ranking/ranking.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];

// =======================
// 1) Jugadores del club
// =======================
// - que hayan hecho reservas en canchas del proveedor
// - o que hayan participado en torneos del proveedor

$sqlJugadores = "
    SELECT DISTINCT u.user_id, u.nombre
    FROM usuarios u
    WHERE u.user_id IN (
        -- Creadores de reservas en canchas de este proveedor
        SELECT DISTINCT r.creador_id
        FROM reservas r
        INNER JOIN canchas c ON r.cancha_id = c.cancha_id
        WHERE c.proveedor_id = ?
    )
    OR u.user_id IN (
        -- Participantes de torneos de este proveedor
        SELECT DISTINCT p.jugador_id
        FROM participaciones p
        INNER JOIN torneos t ON p.torneo_id = t.torneo_id
        WHERE t.proveedor_id = ?
    )
    ORDER BY u.nombre ASC
";

$stmt = $conn->prepare($sqlJugadores);
$stmt->bind_param("ii", $proveedor_id, $proveedor_id);
$stmt->execute();
$jugadoresResult = $stmt->get_result();
$jugadores = [];
while ($row = $jugadoresResult->fetch_assoc()) {
    $jugadores[] = $row;
}
$stmt->close();

// Si no hay jugadores, mostramos mensaje simple
if (empty($jugadores)) {
    ?>
    <div class="section">
        <div class="section-header">
            <h2>Ranking de jugadores del club</h2>
        </div>
        <p> Todavía no hay jugadores con actividad en tus canchas o torneos. </p>
    </div>
    <?php
    include '../includes/footer.php';
    exit();
}

// =======================
// 2) Traer ranking global de esos jugadores
// =======================
// Usamos la tabla `ranking`, si algún jugador no tiene fila, lo mostramos con 0 puntos.

$ids = array_column($jugadores, 'user_id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// armamos tipos para bind_param (todos enteros)
$types = str_repeat('i', count($ids));

$sqlRanking = "
    SELECT 
        r.usuario_id,
        r.puntos,
        r.partidos,
        r.victorias,
        r.derrotas
    FROM ranking r
    WHERE r.usuario_id IN ($placeholders)
";

$stmt = $conn->prepare($sqlRanking);
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$rankingResult = $stmt->get_result();
$rankingPorUsuario = [];

while ($r = $rankingResult->fetch_assoc()) {
    $rankingPorUsuario[$r['usuario_id']] = $r;
}
$stmt->close();

// =======================
// 3) Mezclar usuarios + ranking
// =======================

$tablaRanking = [];

foreach ($jugadores as $j) {
    $uid = $j['user_id'];
    $stats = $rankingPorUsuario[$uid] ?? [
        'puntos'    => 0,
        'partidos'  => 0,
        'victorias' => 0,
        'derrotas'  => 0
    ];

    $tablaRanking[] = [
        'user_id'   => $uid,
        'nombre'    => $j['nombre'],
        'puntos'    => (int)$stats['puntos'],
        'partidos'  => (int)$stats['partidos'],
        'victorias' => (int)$stats['victorias'],
        'derrotas'  => (int)$stats['derrotas']
    ];
}

// ordenar: más puntos primero, luego más victorias, luego menos derrotas
usort($tablaRanking, function($a, $b) {
    if ($a['puntos'] != $b['puntos']) {
        return $b['puntos'] <=> $a['puntos'];
    }
    if ($a['victorias'] != $b['victorias']) {
        return $b['victorias'] <=> $a['victorias'];
    }
    return $a['derrotas'] <=> $b['derrotas'];
});
?>

<div class="section">
    <div class="section-header">
        <h2>Ranking de jugadores del club</h2>
    </div>

    <p style="margin-bottom:10px;">
        Se muestran jugadores que han reservado o participado en torneos de tu club.
    </p>

    <table>
        <tr>
            <th>#</th>
            <th>Jugador</th>
            <th>Puntos</th>
            <th>Partidos</th>
            <th>Victorias</th>
            <th>Derrotas</th>
        </tr>

        <?php
        $pos = 1;
        foreach ($tablaRanking as $row):
        ?>
            <tr>
                <td><?= $pos++ ?></td>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><strong><?= $row['puntos'] ?></strong></td>
                <td><?= $row['partidos'] ?></td>
                <td><?= $row['victorias'] ?></td>
                <td><?= $row['derrotas'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
