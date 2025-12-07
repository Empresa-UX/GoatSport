<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';
?>

<div class="section">
    <div class="section-header">
        <h2>Ranking global de jugadores</h2>
        <button onclick="location.href='rankingForm.php'" class="btn-add">Agregar al Ranking</button>
    </div>

    <table>
        <tr>
            <th>Posición</th>
            <th>Nombre</th>
            <th>Puntos</th>
            <th>Partidos</th>
            <th>Victorias</th>
            <th>Derrotas</th>
            <th>% Victorias</th>
            <th>Última actualización</th>
            <th>Acciones</th>
        </tr>
        <?php
        $sql = "
            SELECT 
                r.ranking_id,
                r.usuario_id,
                u.nombre,
                r.puntos,
                r.victorias,
                r.derrotas,
                (r.victorias + r.derrotas) AS partidos_jugados,
                CASE 
                    WHEN (r.victorias + r.derrotas) > 0 
                        THEN ROUND( (r.victorias * 100.0) / (r.victorias + r.derrotas), 0)
                    ELSE 0
                END AS porcentaje_victorias,
                r.updated_at
            FROM ranking r
            INNER JOIN usuarios u ON r.usuario_id = u.user_id
            ORDER BY r.puntos DESC, porcentaje_victorias DESC
        ";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0):
            $posicion = 1;
            while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $posicion ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= (int)$row['puntos'] ?></td>
            <td><?= (int)$row['partidos_jugados'] ?></td>
            <td><?= (int)$row['victorias'] ?></td>
            <td><?= (int)$row['derrotas'] ?></td>
            <td><?= (int)$row['porcentaje_victorias'] ?>%</td>
            <td><?= htmlspecialchars($row['updated_at']) ?></td>
            <td>
                <button class="btn-action edit"
                        onclick="location.href='rankingForm.php?ranking_id=<?= $row['ranking_id'] ?>'">
                    ✏️
                </button>
                <form method="POST" action="rankingAction.php" style="display:inline-block;"
                      onsubmit="return confirm('¿Seguro que quieres eliminar este registro del ranking?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="ranking_id" value="<?= $row['ranking_id'] ?>">
                    <button type="submit" class="btn-action delete">🗑️</button>
                </form>
            </td>
        </tr>
        <?php
            $posicion++;
            endwhile;
        else:
        ?>
        <tr>
            <td colspan="9" style="text-align:center;">No hay datos de ranking disponibles</td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
