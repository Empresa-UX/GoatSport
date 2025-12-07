<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

$sql = "
    SELECT 
        t.torneo_id,
        t.nombre,
        t.fecha_inicio,
        t.fecha_fin,
        t.estado,
        t.puntos_ganador,
        u.nombre  AS creador,
        p.nombre  AS proveedor
    FROM torneos t
    INNER JOIN usuarios u ON t.creador_id = u.user_id
    LEFT JOIN usuarios p  ON t.proveedor_id = p.user_id
    ORDER BY t.fecha_inicio DESC, t.torneo_id DESC
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
            <th>ID</th>
            <th>Nombre</th>
            <th>Creador</th>
            <th>Proveedor</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Estado</th>
            <th>Puntos ganador</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
                // clase visual por estado
                $estadoClass = '';
                if ($row['estado'] === 'abierto')      $estadoClass = 'status-available';
                elseif ($row['estado'] === 'cerrado')  $estadoClass = 'status-pending';
                elseif ($row['estado'] === 'finalizado') $estadoClass = 'status-unavailable';
            ?>
                <tr>
                    <td><?= (int)$row['torneo_id'] ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['creador']) ?></td>
                    <td><?= $row['proveedor'] ? htmlspecialchars($row['proveedor']) : '—' ?></td>
                    <td><?= htmlspecialchars($row['fecha_inicio']) ?></td>
                    <td><?= htmlspecialchars($row['fecha_fin']) ?></td>
                    <td>
                        <span class="status-pill <?= $estadoClass ?>">
                            <?= ucfirst($row['estado']) ?>
                        </span>
                    </td>
                    <td><?= (int)$row['puntos_ganador'] ?></td>
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
                <td colspan="9" style="text-align:center;">No hay torneos registrados</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
