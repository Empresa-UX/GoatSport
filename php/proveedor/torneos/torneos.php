<?php
// php/proveedor/torneos/torneos.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

$sql = "
    SELECT 
        t.torneo_id,
        t.nombre,
        t.fecha_inicio,
        t.fecha_fin,
        t.estado,
        t.puntos_ganador,
        COUNT(DISTINCT pa.jugador_id) AS total_jugadores
    FROM torneos t
    LEFT JOIN participaciones pa ON pa.torneo_id = t.torneo_id
    WHERE t.proveedor_id = ?
    GROUP BY t.torneo_id, t.nombre, t.fecha_inicio, t.fecha_fin, t.estado, t.puntos_ganador
    ORDER BY t.fecha_inicio DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section">
    <div class="section-header">
        <h2>Mis torneos</h2>
        <button onclick="location.href='torneosForm.php'" class="btn-add">
            ➕ Crear torneo
        </button>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Fechas</th>
            <th>Estado</th>
            <th>Jugadores</th>
            <th>Puntos ganador</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                $estadoClass = '';
                if ($row['estado'] === 'abierto')
                    $estadoClass = 'status-available';
                elseif ($row['estado'] === 'cerrado')
                    $estadoClass = 'status-pending';
                elseif ($row['estado'] === 'finalizado')
                    $estadoClass = 'status-unavailable';
                ?>
                <tr>
                    <td><?= $row['torneo_id'] ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td>
                        <?= htmlspecialchars($row['fecha_inicio']) ?>
                        -
                        <?= htmlspecialchars($row['fecha_fin']) ?>
                    </td>
                    <td><span class="status-pill <?= $estadoClass ?>"><?= ucfirst($row['estado']) ?></span></td>
                    <td><?= (int) $row['total_jugadores'] ?></td>
                    <td><?= (int) $row['puntos_ganador'] ?></td>
                    <td>
                        <button class="btn-action edit"
                            onclick="location.href='torneosForm.php?torneo_id=<?= $row['torneo_id'] ?>'">✏️</button>
                        <button class="btn-action edit"
                            onclick="location.href='torneoParticipantes.php?torneo_id=<?= $row['torneo_id'] ?>'">
                            👥
                        </button>

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
                <td colspan="7" style="text-align:center;">No tienes torneos creados.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php
$stmt->close();
include '../includes/footer.php';
?>