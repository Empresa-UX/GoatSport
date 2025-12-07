<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

// Traer canchas + proveedor + cantidad reservas
$sql = "
    SELECT 
        c.cancha_id,
        c.nombre,
        c.ubicacion,
        c.tipo,
        c.capacidad,
        c.precio,
        c.activa,
        c.hora_apertura,
        c.hora_cierre,
        c.duracion_turno,
        u.nombre AS proveedor,
        u.email AS proveedor_email,
        (SELECT COUNT(*) FROM reservas r WHERE r.cancha_id = c.cancha_id) AS total_reservas
    FROM canchas c
    INNER JOIN usuarios u ON u.user_id = c.proveedor_id
    ORDER BY c.nombre ASC
";

$result = $conn->query($sql);
?>

<div class="section">
    <div class="section-header">
        <h2>Canchas</h2>
        <button onclick="location.href='canchasForm.php'" class="btn-add">Agregar Cancha</button>
    </div>

    <style>
        .status-pill.active {
            background:#0a8f08;
            color:#fff;
        }
        .status-pill.inactive {
            background:#b50000;
            color:#fff;
        }
    </style>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Proveedor</th>
            <th>Ubicación</th>
            <th>Tipo</th>
            <th>Precio</th>
            <th>Horario</th>
            <th>Estado</th>
            <th>Reservas</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($c = $result->fetch_assoc()): ?>
                <?php
                    $hora_apertura = $c['hora_apertura'] ? substr($c['hora_apertura'],0,5) : '--:--';
                    $hora_cierre   = $c['hora_cierre']   ? substr($c['hora_cierre'],0,5)   : '--:--';
                ?>
                <tr>
                    <td><?= $c['cancha_id'] ?></td>

                    <td><?= htmlspecialchars($c['nombre']) ?></td>

                    <td>
                        <strong><?= htmlspecialchars($c['proveedor']) ?></strong><br>
                        <small><?= htmlspecialchars($c['proveedor_email']) ?></small>
                    </td>

                    <td><?= htmlspecialchars($c['ubicacion']) ?></td>
                    <td><?= htmlspecialchars($c['tipo']) ?></td>
                    <td>$<?= number_format($c['precio'],2,',','.') ?></td>

                    <td>
                        <?= $hora_apertura ?> - <?= $hora_cierre ?><br>
                        <small><?= (int)$c['duracion_turno'] ?> min</small>
                    </td>

                    <td>
                        <span class="status-pill <?= $c['activa'] ? 'active' : 'inactive' ?>">
                            <?= $c['activa'] ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </td>

                    <td><?= (int)$c['total_reservas'] ?></td>

                    <td>
                        <!-- Editar -->
                        <button class="btn-action edit"
                            onclick="location.href='canchasForm.php?cancha_id=<?= $c['cancha_id'] ?>'">✏️</button>

                        <!-- Eliminar -->
                        <form method="POST" action="canchasAction.php" style="display:inline-block;"
                              onsubmit="return confirm('¿Seguro que quieres eliminar esta cancha?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="cancha_id" value="<?= $c['cancha_id'] ?>">
                            <button type="submit" class="btn-action delete">🗑️</button>
                        </form>

                        <!-- Activar / desactivar -->
                        <form method="POST" action="canchasAction.php" style="display:inline-block;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="cancha_id" value="<?= $c['cancha_id'] ?>">
                            <button type="submit" class="btn-action" title="Activar / desactivar"
                                    style="background:#ccc;">🔁</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" style="text-align:center;">No hay canchas registradas</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
