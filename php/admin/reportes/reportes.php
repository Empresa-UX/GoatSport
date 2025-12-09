<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';
?>

<div class="section">
    <div class="section-header">
        <h2>Reportes</h2>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Reporte</th>
            <th>Usuario</th>
            <th>Cancha</th>
            <th>Reserva</th>
            <th>Fecha</th>
            <th>Estado</th>
        </tr>

        <?php
        $sql = "
            SELECT r.*, 
                   u.nombre AS usuario_nombre,
                   u.email AS usuario_email,
                   c.nombre AS cancha_nombre
            FROM reportes r
            JOIN usuarios u ON r.usuario_id = u.user_id
            LEFT JOIN canchas c ON r.cancha_id = c.cancha_id
            ORDER BY r.fecha_reporte DESC
        ";

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
                $estadoClass = ($row['estado'] === 'Pendiente') ? 'status-pending' : 'status-available';
        ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['nombre_reporte']) ?></td>

            <td>
                <?= htmlspecialchars($row['usuario_nombre']) ?><br>
                <small><?= htmlspecialchars($row['usuario_email']) ?></small>
            </td>

            <td><?= $row['cancha_nombre'] ? htmlspecialchars($row['cancha_nombre']) : '-' ?></td>
            <td><?= $row['reserva_id'] ?: '-' ?></td>

            <td><?= $row['fecha_reporte'] ?></td>

            <td><span class="status-pill <?= $estadoClass ?>"><?= $row['estado'] ?></span></td>
        </tr>
        <?php endwhile; else: ?>
        <tr>
            <td colspan="8" style="text-align:center;">No hay reportes registrados</td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
