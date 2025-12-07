<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$pago_id    = $_GET['pago_id'] ?? null;

$reserva_id        = '';
$jugador_id        = '';
$monto             = '';
$metodo            = 'club';
$referencia        = '';
$detalle           = '';
$estado            = 'pendiente';
$fecha_pago        = '';

$accion    = 'add';
$formTitle = 'Registrar Pago';

if ($pago_id) {
    $stmt = $conn->prepare("SELECT * FROM pagos WHERE pago_id = ?");
    $stmt->bind_param("i", $pago_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $reserva_id   = $row['reserva_id'];
        $jugador_id   = $row['jugador_id'];
        $monto        = $row['monto'];
        $metodo       = $row['metodo'];
        $referencia   = $row['referencia_gateway'];
        $detalle      = $row['detalle'];
        $estado       = $row['estado'];
        $fecha_pago   = $row['fecha_pago'];
        $accion       = 'edit';
        $formTitle    = 'Editar Pago';
    }
    $stmt->close();
}

// Para combo de reservas
$sqlReservas = "
    SELECT 
        r.reserva_id,
        r.fecha,
        r.hora_inicio,
        r.hora_fin,
        c.nombre AS cancha
    FROM reservas r
    INNER JOIN canchas c ON r.cancha_id = c.cancha_id
    ORDER BY r.fecha DESC, r.hora_inicio DESC
";
$reservas = $conn->query($sqlReservas);

// Para combo de jugadores
$usuarios = $conn->query("SELECT user_id, nombre FROM usuarios WHERE rol = 'cliente' ORDER BY nombre ASC");

// valor para datetime-local
$fecha_pago_input = '';
if (!empty($fecha_pago)) {
    $fecha_pago_input = date('Y-m-d\TH:i', strtotime($fecha_pago));
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="pagosAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="pago_id" value="<?= htmlspecialchars($pago_id) ?>">

        <label>Reserva:</label>
        <select name="reserva_id" required>
            <option value="">-- Selecciona una reserva --</option>
            <?php if ($reservas && $reservas->num_rows > 0): ?>
                <?php while ($r = $reservas->fetch_assoc()): 
                    $label = '#'.$r['reserva_id'].' - '.$r['cancha'].' - '.$r['fecha'].' '.substr($r['hora_inicio'],0,5).'–'.substr($r['hora_fin'],0,5);
                    ?>
                    <option value="<?= $r['reserva_id'] ?>" <?= ($r['reserva_id'] == $reserva_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>

        <label>Jugador:</label>
        <select name="jugador_id" required>
            <option value="">-- Selecciona un jugador --</option>
            <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                <?php while ($u = $usuarios->fetch_assoc()): ?>
                    <option value="<?= $u['user_id'] ?>" <?= ($u['user_id'] == $jugador_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>

        <label>Monto:</label>
        <input type="number" step="0.01" name="monto" value="<?= htmlspecialchars($monto) ?>" required>

        <label>Método de pago:</label>
        <select name="metodo" required>
            <option value="club"         <?= $metodo==='club' ? 'selected' : '' ?>>Pagar en el club</option>
            <option value="mercado_pago" <?= $metodo==='mercado_pago' ? 'selected' : '' ?>>Mercado Pago</option>
            <option value="tarjeta"      <?= $metodo==='tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
        </select>

        <label>Referencia gateway (opcional):</label>
        <input type="text" name="referencia_gateway" value="<?= htmlspecialchars($referencia) ?>">

        <label>Detalle (opcional):</label>
        <textarea name="detalle" rows="3"><?= htmlspecialchars($detalle) ?></textarea>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="pagado"    <?= ($estado=='pagado')    ? 'selected' : '' ?>>Pagado</option>
            <option value="pendiente" <?= ($estado=='pendiente') ? 'selected' : '' ?>>Pendiente</option>
            <option value="cancelado" <?= ($estado=='cancelado') ? 'selected' : '' ?>>Cancelado</option>
        </select>

        <label>Fecha y hora de pago:</label>
        <input type="datetime-local" name="fecha_pago" value="<?= $fecha_pago_input ?>">

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
