<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

$reserva_id = $_GET['reserva_id'] ?? null;
if (!$reserva_id) {
    header("Location: reservas.php");
    exit();
}

$sql = "
    SELECT 
        r.*,
        c.nombre AS cancha,
        u.nombre AS creador
    FROM reservas r
    INNER JOIN canchas c ON r.cancha_id = c.cancha_id
    INNER JOIN usuarios u ON r.creador_id = u.user_id
    WHERE r.reserva_id = ? AND c.proveedor_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $reserva_id, $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
if (!($row = $result->fetch_assoc())) {
    // La reserva no pertenece a este proveedor
    $stmt->close();
    header("Location: reservas.php");
    exit();
}
$stmt->close();

$estado = $row['estado'];
?>

<div class="form-container">
    <h2>Actualizar reserva #<?= $row['reserva_id'] ?></h2>

    <form method="POST" action="reservasAction.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="reserva_id" value="<?= $row['reserva_id'] ?>">

        <label>Cancha:</label>
        <input type="text" value="<?= htmlspecialchars($row['cancha']) ?>" disabled>

        <label>Fecha:</label>
        <input type="text" value="<?= htmlspecialchars($row['fecha']) ?>" disabled>

        <label>Horario:</label>
        <input type="text" 
            value="<?= substr($row['hora_inicio'],0,5) . ' - ' . substr($row['hora_fin'],0,5) ?>" 
            disabled>

        <label>Creada por:</label>
        <input type="text" value="<?= htmlspecialchars($row['creador']) ?>" disabled>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="pendiente"  <?= ($estado=='pendiente')?'selected':'' ?>>Pendiente</option>
            <option value="confirmada" <?= ($estado=='confirmada')?'selected':'' ?>>Confirmada</option>
            <option value="cancelada"  <?= ($estado=='cancelada')?'selected':'' ?>>Cancelada</option>
            <option value="no_show"    <?= ($estado=='no_show')?'selected':'' ?>>No se presentó</option>
        </select>

        <button type="submit" class="btn-add">Guardar cambios</button>
        <a href="reservas.php" style="margin-left:10px; font-size:14px; text-decoration:none;">Volver</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
