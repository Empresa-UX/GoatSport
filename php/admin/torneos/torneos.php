<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

$sql = "
    SELECT t.torneo_id, t.nombre, u.nombre AS creador, t.fecha_inicio, t.fecha_fin, t.estado
    FROM torneos t
    JOIN usuarios u ON t.creador_id = u.user_id
    ORDER BY t.fecha_inicio ASC
";
$result = $conn->query($sql);
?>

<div class="section">
    <div class="section-header">
        <h2>Torneos</h2>
        <button onclick="location.href='torneosForm.php'" class="btn-add">Crear torneo</button>
    </div>

    <table>
        <tr>
            <th>Torneo ID</th>
            <th>Nombre</th>
            <th>Creador</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
                $estadoClass = ($row['estado']=='abierto') ? 'status-available' : 'status-unavailable';
            ?>
                <tr>
                    <td><?= $row['torneo_id'] ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['creador']) ?></td>
                    <td><?= $row['fecha_inicio'] ?></td>
                    <td><?= $row['fecha_fin'] ?></td>
                    <td><span class="status-pill <?= $estadoClass ?>"><?= ucfirst($row['estado']) ?></span></td>
                    <td>
                        <button class="btn-action edit"
                            onclick="location.href='torneosForm.php?torneo_id=<?= $row['torneo_id'] ?>'">✏️</button>

                        <form method="POST" action="torneosAction.php" style="display:inline-block;" 
                              onsubmit="return confirm('¿Seguro que quieres eliminar este torneo?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="torneo_id" value="<?= $row['torneo_id'] ?>">
                            <button type="submit" class="btn-action delete">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align:center;">No hay torneos registrados</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
