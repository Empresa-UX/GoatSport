<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';
?>

<div class="section">
    <div class="section-header">
        <h2>Reportes</h2>
        <button onclick="location.href='reportesForm.php'" class="btn-add">Agregar reporte</button>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Reporte</th>
            <th>Descripción</th>
            <th>Usuario</th>
            <th>Email</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php
        $sql = "SELECT r.id, r.nombre_reporte, r.descripcion, r.fecha_reporte, r.estado,
                       u.nombre AS usuario_nombre, u.email AS usuario_email
                FROM reportes r
                INNER JOIN usuarios u ON r.usuario_id = u.user_id
                ORDER BY r.fecha_reporte DESC";

        if ($result = $conn->query($sql)):
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= htmlspecialchars($row['nombre_reporte']) ?></td>
            <td><?= htmlspecialchars($row['descripcion']) ?></td>
            <td><?= htmlspecialchars($row['usuario_nombre']) ?></td>
            <td><?= htmlspecialchars($row['usuario_email']) ?></td>
            <td><?= htmlspecialchars($row['fecha_reporte']) ?></td>
            <td>
                <?php if ($row['estado'] === 'Pendiente'): ?>
                    <span class="status-pill status-booked">Pendiente</span>
                <?php else: ?>
                    <span class="status-pill status-available">Resuelto</span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn-action edit" 
                        onclick="location.href='reportesForm.php?id=<?= $row['id'] ?>'">✏️</button>

                <form method="POST" action="reportesAction.php" style="display:inline-block;"
                      onsubmit="return confirm('¿Seguro que quieres eliminar este reporte?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn-action delete">🗑️</button>
                </form>
            </td>
        </tr>
        <?php
                endwhile;
            else:
        ?>
        <tr>
            <td colspan="8" style="text-align:center;">No hay reportes registrados</td>
        </tr>
        <?php
            endif;
            $result->free();
        else:
        ?>
        <tr>
            <td colspan="8" style="text-align:center; color:#b00;">
                Error al consultar la base de datos.
            </td>
        </tr>
        <?php
        endif;
        ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
