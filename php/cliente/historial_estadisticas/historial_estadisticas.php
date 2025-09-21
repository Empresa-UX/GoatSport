<?php
include './../../config.php';
include './../includes/header.php';

// ✅ 1) Obtener últimas reservas
$sql_reservas = "
    SELECT r.fecha, r.hora_inicio, r.hora_fin, c.ubicacion, c.cancha_id
    FROM reservas r
    INNER JOIN canchas c ON r.cancha_id = c.cancha_id
    WHERE r.creador_id = ?
    ORDER BY r.fecha DESC, r.hora_inicio DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql_reservas);
$stmt->bind_param("i", $_SESSION['usuario_id']); 
$stmt->execute();
$result_reservas = $stmt->get_result();
$ultimas_reservas = $result_reservas->fetch_all(MYSQLI_ASSOC);

// ✅ 2) Obtener estadísticas desde ranking
$sql_stats = "
    SELECT 
        r.partidos AS partidos_jugados,
        r.victorias,
        r.derrotas,
        r.puntos,
        ROUND((r.victorias / NULLIF(r.partidos, 0)) * 100, 0) AS porcentaje_victorias
    FROM ranking r
    WHERE r.usuario_id = ?
    LIMIT 1
";
$stmt2 = $conn->prepare($sql_stats);
$stmt2->bind_param("i", $_SESSION['usuario_id']);
$stmt2->execute();
$estadisticas = $stmt2->get_result()->fetch_assoc();

// Si el usuario no tiene ranking todavía, ponemos todo en 0
if (!$estadisticas) {
    $estadisticas = [
        'partidos_jugados' => 0,
        'victorias' => 0,
        'derrotas' => 0,
        'puntos' => 0,
        'porcentaje_victorias' => 0
    ];
}
?>

<main>
    <div class="page-wrap">
        <h1 class="page-title">Historial y estadísticas</h1>

        <div class="stats-container">
            <!-- Historial -->
            <div>
                <h2 class="section-title">Últimas reservas</h2>
                <div class="card-white">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Horario</th>
                                <th>Ubicación</th>
                                <th>N° Cancha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ultimas_reservas): ?>
                                <?php foreach ($ultimas_reservas as $reserva): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($reserva['fecha']) ?></td>
                                        <td><?= htmlspecialchars(substr($reserva['hora_inicio'], 0, 5)) ?> - <?= htmlspecialchars(substr($reserva['hora_fin'], 0, 5)) ?></td>
                                        <td><?= htmlspecialchars($reserva['ubicacion']) ?></td>
                                        <td>#<?= htmlspecialchars($reserva['cancha_id']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center;">No tienes reservas aún</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Estadísticas -->
            <div>
                <h2 class="section-title">Estadísticas</h2>
                <div class="card-white">
                    <table>
                        <tbody>
                            <tr>
                                <td class="label-stat">Partidos jugados</td>
                                <td class="value-stat"><?= $estadisticas['partidos_jugados'] ?></td>
                            </tr>
                            <tr>
                                <td class="label-stat">Victorias</td>
                                <td class="value-stat"><?= $estadisticas['victorias'] ?></td>
                            </tr>
                            <tr>
                                <td class="label-stat">Derrotas</td>
                                <td class="value-stat"><?= $estadisticas['derrotas'] ?></td>
                            </tr>
                            <tr>
                                <td class="label-stat">% de Victorias</td>
                                <td class="value-stat"><?= $estadisticas['porcentaje_victorias'] ?>%</td>
                            </tr>
                            <tr>
                                <td class="label-stat">Puntos</td>
                                <td class="value-stat"><?= $estadisticas['puntos'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include './../includes/footer.php'; ?>
