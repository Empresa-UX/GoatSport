<?php
// php/proveedor/canchas/canchas.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

$sql = "
    SELECT 
        cancha_id,
        nombre,
        ubicacion,
        tipo,
        capacidad,
        precio,
        hora_apertura,
        hora_cierre,
        duracion_turno,
        activa
    FROM canchas
    WHERE proveedor_id = ?
    ORDER BY nombre ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section">
    <div class="section-header">
        <h2>Mis canchas</h2>
        <button onclick="location.href='canchasForm.php'" class="btn-add">
            ➕ Nueva cancha
        </button>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Ubicación</th>
            <th>Tipo</th>
            <th>Capacidad</th>
            <th>Precio</th>
            <th>Horario</th>
            <th>Duración</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $estadoClass = $row['activa'] ? 'status-available' : 'status-unavailable';
                    $estadoTexto = $row['activa'] ? 'Activa' : 'Inactiva';
                    $hora_ap = $row['hora_apertura'] ? substr($row['hora_apertura'],0,5) : '-';
                    $hora_ci = $row['hora_cierre'] ? substr($row['hora_cierre'],0,5) : '-';
                ?>
                <tr>
                    <td><?= $row['cancha_id'] ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['ubicacion']) ?></td>
                    <td><?= htmlspecialchars($row['tipo'] ?? '-') ?></td>
                    <td><?= $row['capacidad'] !== null ? (int)$row['capacidad'] : '-' ?></td>
                    <td>$<?= number_format($row['precio'], 2, ',', '.') ?></td>
                    <td><?= $hora_ap ?> - <?= $hora_ci ?></td>
                    <td><?= (int)($row['duracion_turno'] ?? 60) ?> min</td>
                    <td>
                        <span class="status-pill <?= $estadoClass ?>"><?= $estadoTexto ?></span>
                    </td>
                    <td>
                        <button class="btn-action edit"
                            onclick="location.href='canchasForm.php?cancha_id=<?= $row['cancha_id'] ?>'">✏️</button>

                        <form method="POST" action="canchasAction.php" style="display:inline-block;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="cancha_id" value="<?= $row['cancha_id'] ?>">
                            <input type="hidden" name="activa" value="<?= $row['activa'] ? 0 : 1 ?>">
                            <button type="submit" class="btn-action edit">
                                <?= $row['activa'] ? '⏸️' : '▶️' ?>
                            </button>
                        </form>

                        <!-- OPCIONAL: borrar, si querés permitirlo -->
                        <!--
                        <form method="POST" action="canchasAction.php" style="display:inline-block;"
                              onsubmit="return confirm('¿Seguro que quieres eliminar esta cancha?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="cancha_id" value="<?= $row['cancha_id'] ?>">
                            <button type="submit" class="btn-action delete">🗑️</button>
                        </form>
                        -->
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" style="text-align:center;">No tienes canchas cargadas.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php
$stmt->close();
include '../includes/footer.php';
?>
