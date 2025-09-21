<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$pago_id = $_GET['pago_id'] ?? null;
$reserva_id = $jugador_id = $monto = $estado = $fecha_pago = '';
$accion = 'add';
$formTitle = 'Registrar Pago';

if($pago_id){
    $stmt = $conn->prepare("SELECT * FROM pagos WHERE pago_id=?");
    $stmt->bind_param("i", $pago_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $reserva_id = $row['reserva_id'];
        $jugador_id = $row['jugador_id'];
        $monto = $row['monto'];
        $estado = $row['estado'];
        $fecha_pago = $row['fecha_pago'];
        $accion = 'edit';
        $formTitle = 'Editar Pago';
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="pagosAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="pago_id" value="<?= $pago_id ?>">
        

        <label>Reserva:</label>
        <input type="number" name="reserva_id" value="<?= $reserva_id ?>" required>

        <label>Jugador:</label>
        <select name="jugador_id" required>
            <?php
            $usuarios = $conn->query("SELECT user_id, nombre FROM usuarios ORDER BY nombre ASC");
            while($u = $usuarios->fetch_assoc()):
            ?>
                <option value="<?= $u['user_id'] ?>" <?= ($u['user_id']==$jugador_id)?'selected':'' ?>>
                    <?= htmlspecialchars($u['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Monto:</label>
        <input type="number" step="0.01" name="monto" value="<?= $monto ?? '' ?>" required>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="pagado" <?= ($estado=='pagado')?'selected':'' ?>>Pagado</option>
            <option value="pendiente" <?= ($estado=='pendiente')?'selected':'' ?>>Pendiente</option>
            <option value="cancelado" <?= ($estado=='cancelado')?'selected':'' ?>>Cancelado</option>
        </select>

        <label>Fecha de pago:</label>
        <input type="date" name="fecha_pago" 
            value="<?= $fecha_pago ? date('Y-m-d', strtotime($fecha_pago)) : '' ?>">

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
