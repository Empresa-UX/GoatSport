<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

$sql = "
    SELECT p.partido_id, 
           t.nombre AS torneo, 
           j1.nombre AS jugador1, 
           j2.nombre AS jugador2,
           p.fecha,
           p.resultado
    FROM partidos p
    JOIN torneos t ON p.torneo_id = t.torneo_id
    JOIN usuarios j1 ON p.jugador1_id = j1.user_id
    JOIN usuarios j2 ON p.jugador2_id = j2.user_id
    ORDER BY p.fecha ASC
";
$result = $conn->query($sql);
?>

<div class="section">
    <div class="section-header">
        <h2>Partidos</h2>
        <button onclick="location.href='partidosForm.php'" class="btn-add">Agregar partido</button>
    </div>

    <table>
        <tr>
            <th>Partido ID</th>
            <th>Torneo</th>
            <th>Jugador 1</th>
            <th>Jugador 2</th>
            <th>Fecha</th>
            <th>Resultado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['partido_id'] ?></td>
                    <td><?= htmlspecialchars($row['torneo']) ?></td>
                    <td><?= htmlspecialchars($row['jugador1']) ?></td>
                    <td><?= htmlspecialchars($row['jugador2']) ?></td>
                    <td><?= $row['fecha'] ?></td>
                    <td><?= htmlspecialchars($row['resultado']) ?></td>
                    <td>
                        <button class="btn-action edit" 
                            onclick="location.href='partidosForm.php?partido_id=<?= $row['partido_id'] ?>'">✏️</button>

                        <form method="POST" action="partidosAction.php" style="display:inline-block;" 
                              onsubmit="return confirm('¿Seguro que quieres eliminar este partido?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="partido_id" value="<?= $row['partido_id'] ?>">
                            <button type="submit" class="btn-action delete">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align:center;">No hay partidos registrados</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
