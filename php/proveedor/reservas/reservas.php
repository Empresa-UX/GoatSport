<?php
// php/proveedor/reservas/reservas.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

$sql = "
    SELECT 
        r.reserva_id,
        r.fecha,
        r.hora_inicio,
        r.hora_fin,
        r.estado,
        c.nombre AS cancha,
        u.nombre AS creador
    FROM reservas r
    INNER JOIN canchas c ON r.cancha_id = c.cancha_id
    INNER JOIN usuarios u ON r.creador_id = u.user_id
    WHERE c.proveedor_id = ?
    ORDER BY r.fecha DESC, r.hora_inicio DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section">
    <div class="section-header">
        <h2>Reservas de mis canchas</h2>
        <!-- Si quisieras permitir alta manual: botón a reservasForm.php sin id -->
        <!-- <button onclick="location.href='reservasForm.php'" class="btn-add">Agregar reserva</button> -->
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Cancha</th>
            <th>Creada por</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $estadoClass = '';
                    if ($row['estado'] === 'confirmada')      $estadoClass = 'status-available';
                    elseif ($row['estado'] === 'pendiente')   $estadoClass = 'status-pending';
                    elseif ($row['estado'] === 'cancelada')   $estadoClass = 'status-unavailable';
                    elseif ($row['estado'] === 'no_show')     $estadoClass = 'status-unavailable';
                ?>
                <tr>
                    <td><?= $row['reserva_id'] ?></td>
                    <td><?= htmlspecialchars($row['fecha']) ?></td>
                    <td><?= htmlspecialchars(substr($row['hora_inicio'],0,5) . ' - ' . substr($row['hora_fin'],0,5)) ?></td>
                    <td><?= htmlspecialchars($row['cancha']) ?></td>
                    <td><?= htmlspecialchars($row['creador']) ?></td>
                    <td><span class="status-pill <?= $estadoClass ?>"><?= ucfirst($row['estado']) ?></span></td>
                    <td>
                        <button class="btn-action edit"
                            onclick="location.href='reservasForm.php?reserva_id=<?= $row['reserva_id'] ?>'">✏️</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align:center;">No hay reservas para tus canchas</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php
$stmt->close();
include '../includes/footer.php';
?>
