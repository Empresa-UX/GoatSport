<?php 
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

// Traer canchas en estado pendiente o denegado
$sql = "
    SELECT 
        c.cancha_id,
        c.nombre,
        c.ubicacion,
        c.tipo,
        c.precio,
        c.estado,
        u.nombre AS proveedor,
        u.email AS proveedor_email
    FROM canchas c
    INNER JOIN usuarios u ON u.user_id = c.proveedor_id
    WHERE c.estado IN ('pendiente','denegado')
    ORDER BY c.estado, c.nombre ASC
";

$result = $conn->query($sql);
?>

<div class="section">
    <div class="section-header">
        <h2>Canchas a Definir</h2>
    </div>

    <style>
        .pill {
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: bold;
            text-transform: capitalize;
            color: white;
        }
        .pendiente { background: #e69d00; }
        .aprobado { background: #009b0c; }
        .denegado { background: #c70000; }
    </style>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Proveedor</th>
            <th>Ubicación</th>
            <th>Tipo</th>
            <th>Precio</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php while ($c = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $c['cancha_id'] ?></td>

                <td><?= htmlspecialchars($c['nombre']) ?></td>

                <td>
                    <strong><?= htmlspecialchars($c['proveedor']) ?></strong><br>
                    <small><?= htmlspecialchars($c['proveedor_email']) ?></small>
                </td>

                <td><?= htmlspecialchars($c['ubicacion']) ?></td>
                <td><?= htmlspecialchars($c['tipo']) ?: '--' ?></td>
                <td>$<?= number_format($c['precio'],2,',','.') ?></td>

                <td>
                    <span class="pill <?= $c['estado'] ?>">
                        <?= $c['estado'] ?>
                    </span>
                </td>

                <td>
                    <!-- Aprobar -->
                    <form method="POST" action="../canchas/canchasAction.php" style="display:inline-block;">
                        <input type="hidden" name="action" value="aprobar">
                        <input type="hidden" name="cancha_id" value="<?= $c['cancha_id'] ?>">
                        <button class="btn-action" style="background:#0a8f08; color:white;">✔️</button>
                    </form>

                    <!-- Denegar -->
                    <form method="POST" action="../canchas/canchasAction.php" style="display:inline-block;">
                        <input type="hidden" name="action" value="denegar">
                        <input type="hidden" name="cancha_id" value="<?= $c['cancha_id'] ?>">
                        <button class="btn-action" style="background:#b50000; color:white;">✖️</button>
                    </form>

                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
