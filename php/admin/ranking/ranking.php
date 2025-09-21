<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';
?>

<div class="section">
    <div class="section-header">
        <h2>Ranking</h2>
        <button onclick="location.href='rankingForm.php'" class="btn-add">Agregar al Ranking</button>
    </div>

    <table>
        <tr>
            <th>Posición</th>
            <th>Nombre</th>
            <th>Puntos</th>
            <th>Partidos</th>
            <th>Victorias</th>
            <th>% de Victorias</th>
            <th>Acciones</th>
        </tr>
        <?php
        $sql = "
            SELECT r.ranking_id, r.usuario_id, u.nombre, r.puntos, r.partidos, r.victorias,
                   ROUND((r.victorias / NULLIF(r.partidos, 0)) * 100, 0) AS porcentaje_victorias
            FROM ranking r
            INNER JOIN usuarios u ON r.usuario_id = u.user_id
            ORDER BY r.puntos DESC
        ";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0):
            $posicion = 1;
            while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $posicion ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= $row['puntos'] ?></td>
            <td><?= $row['partidos'] ?></td>
            <td><?= $row['victorias'] ?></td>
            <td><?= $row['porcentaje_victorias'] ?>%</td>
            <td>
                <button class="btn-action edit" onclick="location.href='rankingForm.php?ranking_id=<?= $row['ranking_id'] ?>'">✏️</button>
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
            <td colspan="7" style="text-align:center;">No hay datos de ranking disponibles</td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
