<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

// Filtro simple por estado (opcional)
$filtro_estado = $_GET['estado'] ?? 'todos';
$extraEstado = '';
if (in_array($filtro_estado, ['pendiente', 'confirmada', 'cancelada', 'no_show'])) {
    $extraEstado = "WHERE r.estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}

$sql = "
    SELECT 
        r.reserva_id, 
        c.nombre AS cancha, 
        u.nombre AS creador,
        r.fecha, 
        r.hora_inicio, 
        r.hora_fin, 
        r.estado,
        r.tipo_reserva,
        r.precio_total
    FROM reservas r
    JOIN canchas c ON r.cancha_id = c.cancha_id
    JOIN usuarios u ON r.creador_id = u.user_id
    $extraEstado
    ORDER BY r.fecha DESC, r.hora_inicio DESC
";
$result = $conn->query($sql);
?>

<div class="section">
    <div class="section-header">
        <h2>Reservas</h2>
        <button onclick="location.href='reservasForm.php'" class="btn-add">Agregar reserva</button>
    </div>

    <style>
        .filtros-admin {
            display: flex;
            gap: 15px;
            align-items: center;
            background: white;
            padding: 8px 12px;
            width: max-content;
        }

        .filtros-admin label {
            font-weight: bold;
            color: #043b3d;
            font-size: 16px;
        }

        .filtros-admin select {
            padding: 6px 10px;
            font-size: 14px;
            border: 1px solid #c8c8c8;
            border-radius: 8px;
            background: #f8f8f8;
            color: #333;
            transition: .2s ease;
            cursor: pointer;
        }

        .filtros-admin select:hover {
            background: #fff;
            border-color: #0a5557;
        }

        .filtros-admin select:focus {
            outline: none;
            border-color: #0a5557;
            background: #fff;
            box-shadow: 0 0 4px rgba(4, 59, 61, 0.3);
        }
    </style>


    <!-- Filtro por estado -->
    <form method="GET" class="filtros-admin">
        <label>
            Estado:
            <select name="estado" onchange="this.form.submit()">
                <option value="todos" <?= $filtro_estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="confirmada" <?= $filtro_estado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                <option value="cancelada" <?= $filtro_estado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                <option value="no_show" <?= $filtro_estado === 'no_show' ? 'selected' : '' ?>>No se presentó</option>
            </select>
        </label>
    </form>


    <table>
        <tr>
            <th>ID</th>
            <th>Cancha</th>
            <th>Creador</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Tipo</th>
            <th>Precio total</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $estadoClass = '';
                switch ($row['estado']) {
                    case 'confirmada':
                        $estadoClass = 'status-available';
                        break;
                    case 'pendiente':
                        $estadoClass = 'status-pending';
                        break;
                    case 'cancelada':
                        $estadoClass = 'status-unavailable';
                        break;
                    case 'no_show':
                        $estadoClass = 'status-unavailable';
                        break;
                }

                $tipoLabel = $row['tipo_reserva'] === 'individual' ? 'Individual' : 'Por equipo';
                ?>
                <tr>
                    <td><?= $row['reserva_id'] ?></td>
                    <td><?= htmlspecialchars($row['cancha']) ?></td>
                    <td><?= htmlspecialchars($row['creador']) ?></td>
                    <td><?= htmlspecialchars($row['fecha']) ?></td>
                    <td><?= substr($row['hora_inicio'], 0, 5) . ' - ' . substr($row['hora_fin'], 0, 5) ?></td>
                    <td><?= htmlspecialchars($tipoLabel) ?></td>
                    <td>$<?= number_format($row['precio_total'], 2, ',', '.') ?></td>
                    <td>
                        <span class="status-pill <?= $estadoClass ?>">
                            <?= $row['estado'] === 'no_show' ? 'No se presentó' : ucfirst($row['estado']) ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn-action edit"
                            onclick="location.href='reservasForm.php?reserva_id=<?= $row['reserva_id'] ?>'">✏️</button>

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
            <tr>
                <td colspan="9" style="text-align:center;">No hay reservas registradas</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>