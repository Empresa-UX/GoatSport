<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';
?>

<div class="section">
    <div class="section-header">
        <h2>Pagos</h2>
        <button onclick="location.href='pagosForm.php'" class="btn-add">Registrar pago</button>
    </div>

    <table>
        <tr>
            <th>Pago ID</th>
            <th>Reserva</th>
            <th>Jugador</th>
            <th>Monto</th>
            <th>Estado</th>
            <th>Fecha de pago</th>
            <th>Acciones</th>
        </tr>

        <?php
        $sql = "
            SELECT p.pago_id, p.reserva_id, u.nombre AS jugador, p.monto, p.estado, p.fecha_pago
            FROM pagos p
            INNER JOIN usuarios u ON p.jugador_id = u.user_id
            ORDER BY p.pago_id ASC
        ";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
                $estadoClass = '';
                if ($row['estado'] === 'pagado')
                    $estadoClass = 'status-available';
                elseif ($row['estado'] === 'pendiente')
                    $estadoClass = 'status-pending';
                elseif ($row['estado'] === 'cancelado')
                    $estadoClass = 'status-unavailable';
                ?>
                <tr>
                    <td><?= $row['pago_id'] ?></td>
                    <td># <?= $row['reserva_id'] ?></td>
                    <td><?= htmlspecialchars($row['jugador']) ?></td>
                    <td>$<?= number_format($row['monto'], 2, ',', '.') ?></td>
                    <td><span class="status-pill <?= $estadoClass ?>"><?= ucfirst($row['estado']) ?></span></td>
                    <td><?= $row['fecha_pago'] ? $row['fecha_pago'] : '-' ?></td>
                    <td>
                        <button class="btn-action edit"
                            onclick="location.href='pagosForm.php?pago_id=<?= $row['pago_id'] ?>'">✏️</button>

                        <form method="POST" action="pagosAction.php" style="display:inline-block;"
                            onsubmit="return confirm('¿Seguro que quieres eliminar este pago?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="pago_id" value="<?= $row['pago_id'] ?>">
                            <button type="submit" class="btn-action delete">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php
            endwhile;
        else:
            ?>
            <tr>
                <td colspan="7" style="text-align:center;">No hay pagos registrados</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>