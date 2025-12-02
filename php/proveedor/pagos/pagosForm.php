<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

$pago_id   = $_GET['pago_id'] ?? null;
$estado    = '';
$fecha_pago = '';
$metodo    = '';
$monto     = 0;
$reserva_id = 0;
$jugador   = '';

if ($pago_id) {
    // Aseguramos que el pago sea de una reserva de ESTE proveedor
    $sql = "
        SELECT p.*, u.nombre AS jugador
        FROM pagos p
        INNER JOIN reservas r ON p.reserva_id = r.reserva_id
        INNER JOIN canchas c ON r.cancha_id = c.cancha_id
        INNER JOIN usuarios u ON p.jugador_id = u.user_id
        WHERE p.pago_id = ? AND c.proveedor_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $pago_id, $proveedor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $estado     = $row['estado'];
        $fecha_pago = $row['fecha_pago'];
        $metodo     = $row['metodo'];
        $monto      = $row['monto'];
        $reserva_id = $row['reserva_id'];
        $jugador    = $row['jugador'];
    } else {
        // pago no pertenece al proveedor
        $stmt->close();
        header("Location: pagos.php");
        exit();
    }
    $stmt->close();
} else {
    // para proveedor no soportamos "alta" manual de pagos
    header("Location: pagos.php");
    exit();
}
?>

<div class="form-container">
    <h2>Actualizar pago #<?= $pago_id ?></h2>

    <form method="POST" action="pagosAction.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="pago_id" value="<?= $pago_id ?>">

        <label>Reserva:</label>
        <input type="text" value="#<?= $reserva_id ?>" disabled>

        <label>Jugador:</label>
        <input type="text" value="<?= htmlspecialchars($jugador) ?>" disabled>

        <label>Método:</label>
        <input type="text" value="<?= ucfirst(str_replace('_',' ', $metodo)) ?>" disabled>

        <label>Monto:</label>
        <input type="text" value="$<?= number_format($monto, 2, ',', '.') ?>" disabled>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="pagado"   <?= ($estado=='pagado')?'selected':'' ?>>Pagado</option>
            <option value="pendiente"<?= ($estado=='pendiente')?'selected':'' ?>>Pendiente</option>
            <option value="cancelado"<?= ($estado=='cancelado')?'selected':'' ?>>Cancelado</option>
        </select>

        <label>Fecha de pago (si está pagado):</label>
        <input type="date" name="fecha_pago"
               value="<?= $fecha_pago ? date('Y-m-d', strtotime($fecha_pago)) : '' ?>">

        <button type="submit" class="btn-add">Guardar cambios</button>
        <a href="pagos.php" style="margin-left:10px; font-size:14px; text-decoration:none;">Cancelar</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
