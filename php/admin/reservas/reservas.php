<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

$sql = "
    SELECT r.reserva_id, 
           c.nombre AS cancha, 
           u.nombre AS creador,
           r.fecha, r.hora_inicio, r.hora_fin, r.estado
    FROM reservas r
    JOIN canchas c ON r.cancha_id = c.cancha_id
    JOIN usuarios u ON r.creador_id = u.user_id
    ORDER BY r.fecha, r.hora_inicio
";
$result = $conn->query($sql);
?>

<div class="section">
    <div class="section-header">
        <h2>Reservas</h2>
        <button onclick="location.href='reservasForm.php'" class="btn-add">Agregar reserva</button>
    </div>

    <table>
        <tr>
            <th>Reserva ID</th>
            <th>Cancha</th>
            <th>Creador</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $estadoClass = '';
                switch($row['estado']){
                    case 'confirmada': $estadoClass='status-available'; break;
                    case 'pendiente': $estadoClass='status-pending'; break;
                    case 'cancelada': $estadoClass='status-unavailable'; break;
                }
            ?>
                <tr>
                    <td><?= $row['reserva_id'] ?></td>
                    <td><?= htmlspecialchars($row['cancha']) ?></td>
                    <td><?= htmlspecialchars($row['creador']) ?></td>
                    <td><?= $row['fecha'] ?></td>
                    <td><?= $row['hora_inicio'] ?> - <?= $row['hora_fin'] ?></td>
                    <td><span class="status-pill <?= $estadoClass ?>"><?= ucfirst($row['estado']) ?></span></td>
                    <td>
                        <button class="btn-action edit" onclick="location.href='reservasForm.php?reserva_id=<?= $row['reserva_id'] ?>'">✏️</button>

                        <form method="POST" action="reservasAction.php" style="display:inline-block;" 
                              onsubmit="return confirm('¿Seguro que quieres eliminar esta reserva?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="reserva_id" value="<?= $row['reserva_id'] ?>">
                            <button type="submit" class="btn-action delete">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">No hay reservas registradas</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
