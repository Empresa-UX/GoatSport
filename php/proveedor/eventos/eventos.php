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
        e.evento_id,
        e.titulo,
        e.descripcion,
        e.fecha_inicio,
        e.fecha_fin,
        e.tipo,
        c.nombre AS cancha
    FROM eventos_especiales e
    INNER JOIN canchas c ON e.cancha_id = c.cancha_id
    WHERE e.proveedor_id = ?
    ORDER BY e.fecha_inicio DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<div class="section">
    <div class="section-header">
        <h2>Eventos especiales / Bloqueos</h2>
        <button onclick="location.href='eventosForm.php'" class="btn-add">Nuevo evento</button>
    </div>

    <table>
        <tr>
            <th>Título</th>
            <th>Cancha</th>
            <th>Tipo</th>
            <th>Desde</th>
            <th>Hasta</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($e = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($e['titulo']) ?></td>
                    <td><?= htmlspecialchars($e['cancha']) ?></td>
                    <td><?= ucfirst($e['tipo']) ?></td>
                    <td><?= $e['fecha_inicio'] ?></td>
                    <td><?= $e['fecha_fin'] ?></td>
                    <td>
                        <button class="btn-action edit"
                            onclick="location.href='eventosForm.php?evento_id=<?= $e['evento_id'] ?>'">
                            ✏️
                        </button>

                        <form method="POST" action="eventosAction.php"
                            style="display:inline-block;" 
                            onsubmit="return confirm('¿Eliminar evento?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="evento_id" value="<?= $e['evento_id'] ?>">
                            <button class="btn-action delete">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center;">No hay eventos registrados.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
