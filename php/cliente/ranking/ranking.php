<?php
include './../../config.php';
include './../includes/header.php';

$sql = "
    SELECT 
        r.ranking_id,
        r.usuario_id,
        u.nombre,
        r.puntos,
        r.partidos,
        r.victorias,
        ROUND((r.victorias / NULLIF(r.partidos, 0)) * 100, 0) AS porcentaje_victorias
    FROM ranking r
    INNER JOIN usuarios u ON r.usuario_id = u.user_id
    ORDER BY r.puntos DESC
";
$result = $conn->query($sql);
?>

    <div class="page-wrap">
        <h1 class="page-title">Ranking</h1>

        <div class="card-white">
            <table>
                <thead>
                    <tr>
                        <th>Posición</th>
                        <th>Nombre</th>
                        <th>Puntos</th>
                        <th>Partidos</th>
                        <th>Victorias</th>
                        <th>% Victorias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php $posicion = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $posicion ?></td>
                                <td><?= htmlspecialchars($row['nombre']) ?></td>
                                <td><?= $row['puntos'] ?></td>
                                <td><?= $row['partidos'] ?></td>
                                <td><?= $row['victorias'] ?></td>
                                <td><?= $row['porcentaje_victorias'] ?>%</td>
                            </tr>
                            <?php $posicion++; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No hay datos de ranking disponibles</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include './../includes/footer.php'; ?>
