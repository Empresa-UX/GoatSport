<?php
// php/proveedor/pagos/pagos.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

// Pagos SOLO de reservas de las canchas de este proveedor
$sql = "
    SELECT 
        p.pago_id,
        p.reserva_id,
        u.nombre AS jugador,
        p.monto,
        p.estado,
        p.metodo,
        p.fecha_pago
    FROM pagos p
    INNER JOIN reservas r ON p.reserva_id = r.reserva_id
    INNER JOIN canchas c ON r.cancha_id = c.cancha_id
    INNER JOIN usuarios u ON p.jugador_id = u.user_id
    WHERE c.proveedor_id = ?
    ORDER BY p.fecha_pago DESC, p.pago_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section">
    <div class="section-header">
        <h2>Pagos de mis canchas</h2>
        <!-- Para proveedor normalmente no registramos pagos manuales -->
        <!-- <button onclick="location.href='pagosForm.php'" class="btn-add">Registrar pago</button> -->
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Reserva</th>
            <th>Jugador</th>
            <th>Método</th>
            <th>Monto</th>
            <th>Estado</th>
            <th>Fecha pago</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $estadoClass = '';
                    if ($row['estado'] === 'pagado')    $estadoClass = 'status-available';
                    elseif ($row['estado'] === 'pendiente') $estadoClass = 'status-pending';
                    elseif ($row['estado'] === 'cancelado') $estadoClass = 'status-unavailable';
                ?>
                <tr>
                    <td><?= $row['pago_id'] ?></td>
                    <td>#<?= $row['reserva_id'] ?></td>
                    <td><?= htmlspecialchars($row['jugador']) ?></td>
                    <td><?= ucfirst(str_replace('_',' ', $row['metodo'])) ?></td>
                    <td>$<?= number_format($row['monto'], 2, ',', '.') ?></td>
                    <td><span class="status-pill <?= $estadoClass ?>"><?= ucfirst($row['estado']) ?></span></td>
                    <td><?= $row['fecha_pago'] ? $row['fecha_pago'] : '-' ?></td>
                    <td>
                        <!-- Solo permitimos editar estado/fecha -->
                        <button class="btn-action edit"
                            onclick="location.href='pagosForm.php?pago_id=<?= $row['pago_id'] ?>'">✏️</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align:center;">No hay pagos registrados para tus canchas</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php
$stmt->close();
include '../includes/footer.php';
?>
