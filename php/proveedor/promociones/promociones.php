<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];

$sql = "
    SELECT 
        p.promocion_id,
        p.nombre,
        p.descripcion,
        p.porcentaje_descuento,
        p.fecha_inicio,
        p.fecha_fin,
        p.hora_inicio,
        p.hora_fin,
        p.dias_semana,
        p.minima_reservas,
        p.activa,
        c.nombre AS cancha_nombre
    FROM promociones p
    LEFT JOIN canchas c ON p.cancha_id = c.cancha_id
    WHERE p.proveedor_id = ?
    ORDER BY p.fecha_inicio DESC, p.nombre ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<div class="section">
    <div class="section-header">
        <h2>Promociones</h2>
        <button onclick="location.href='promocionesForm.php'" class="btn-add">Crear promoción</button>
    </div>

    <table>
        <tr>
            <th>Nombre</th>
            <th>Cancha</th>
            <th>Descuento</th>
            <th>Condiciones</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($p = $result->fetch_assoc()): ?>

                <?php
                // cancha
                $cancha = $p['cancha_nombre'] ? $p['cancha_nombre'] : 'Todas';

                // descuento
                $desc = number_format($p['porcentaje_descuento'], 2) . '%';

                // condiciones
                $cond = [];

                if ($p['minima_reservas'] > 0) {
                    $cond[] = "Mín. {$p['minima_reservas']} reservas";
                }

                if ($p['hora_inicio'] && $p['hora_fin']) {
                    $cond[] = substr($p['hora_inicio'], 0, 5) . " - " . substr($p['hora_fin'], 0, 5);
                }

                if ($p['dias_semana']) {
                    $dias = explode(',', $p['dias_semana']);
                    $map = ['1'=>'L','2'=>'M','3'=>'X','4'=>'J','5'=>'V','6'=>'S','7'=>'D'];
                    $human = array_map(fn($d) => $map[$d], $dias);

                    $cond[] = "Días: " . implode('', $human);
                }

                $condStr = $cond ? implode('<br>', $cond) : '-';

                // estado
                $estadoClass = $p['activa'] ? 'status-available' : 'status-unavailable';
                $estadoText = $p['activa'] ? 'Activa' : 'Inactiva';
                ?>

                <tr>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($cancha) ?></td>
                    <td><?= $desc ?></td>
                    <td><?= $condStr ?></td>
                    <td><?= "{$p['fecha_inicio']}<br>{$p['fecha_fin']}" ?></td>
                    <td><span class="status-pill <?= $estadoClass ?>"><?= $estadoText ?></span></td>
                    <td>
                        <button class="btn-action edit"
                                onclick="location.href='promocionesForm.php?promocion_id=<?= $p['promocion_id'] ?>'">✏️</button>

                        <form method="POST" action="promocionesAction.php"
                              style="display:inline-block;"
                              onsubmit="return confirm('¿Eliminar esta promoción?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="promocion_id" value="<?= $p['promocion_id'] ?>">
                            <button type="submit" class="btn-action delete">🗑️</button>
                        </form>
                    </td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">No hay promociones.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
