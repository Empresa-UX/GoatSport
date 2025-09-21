<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$reserva_id = $_GET['reserva_id'] ?? null;
$cancha_id = $creador_id = $fecha = $hora_inicio = $hora_fin = $estado = '';
$accion = 'add';
$formTitle = 'Agregar Reserva';

if($reserva_id){
    $stmt = $conn->prepare("SELECT * FROM reservas WHERE reserva_id=?");
    $stmt->bind_param("i", $reserva_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $cancha_id = $row['cancha_id'];
        $creador_id = $row['creador_id'];
        $fecha = $row['fecha'];
        $hora_inicio = $row['hora_inicio'];
        $hora_fin = $row['hora_fin'];
        $estado = $row['estado'];
        $accion = 'edit';
        $formTitle = 'Editar Reserva';
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="reservasAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="reserva_id" value="<?= $reserva_id ?>">

        <label>Cancha:</label>
        <select name="cancha_id" required>
            <?php
            $canchas = $conn->query("SELECT cancha_id, nombre FROM canchas ORDER BY nombre ASC");
            while ($c = $canchas->fetch_assoc()):
            ?>
                <option value="<?= $c['cancha_id'] ?>" <?= ($c['cancha_id']==$cancha_id)?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Creador:</label>
        <select name="creador_id" required>
            <?php
            $usuarios = $conn->query("SELECT user_id, nombre FROM usuarios ORDER BY nombre ASC");
            while ($u = $usuarios->fetch_assoc()):
            ?>
                <option value="<?= $u['user_id'] ?>" <?= ($u['user_id']==$creador_id)?'selected':'' ?>><?= htmlspecialchars($u['nombre']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Fecha:</label>
        <input type="date" name="fecha" value="<?= $fecha ?>" required>

        <label>Hora inicio:</label>
        <input type="time" name="hora_inicio" value="<?= $hora_inicio ?>" required>

        <label>Hora fin:</label>
        <input type="time" name="hora_fin" value="<?= $hora_fin ?>" required>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="confirmada" <?= ($estado=='confirmada')?'selected':'' ?>>Confirmada</option>
            <option value="pendiente" <?= ($estado=='pendiente')?'selected':'' ?>>Pendiente</option>
            <option value="cancelada" <?= ($estado=='cancelada')?'selected':'' ?>>Cancelada</option>
        </select><br><br>

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
