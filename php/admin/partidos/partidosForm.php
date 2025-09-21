<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$partido_id = $_GET['partido_id'] ?? null;
$torneo_id = $jugador1_id = $jugador2_id = $fecha = $resultado = '';
$accion = 'add';
$formTitle = 'Agregar Partido';

if($partido_id){
    $stmt = $conn->prepare("SELECT * FROM partidos WHERE partido_id=?");
    $stmt->bind_param("i", $partido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $torneo_id = $row['torneo_id'];
        $jugador1_id = $row['jugador1_id'];
        $jugador2_id = $row['jugador2_id'];
        $fecha = $row['fecha'];
        $resultado = $row['resultado'];
        $accion = 'edit';
        $formTitle = 'Editar Partido';
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="partidosAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="partido_id" value="<?= $partido_id ?>">

        <label>Torneo:</label>
        <select name="torneo_id" required>
            <?php
            $torneos = $conn->query("SELECT torneo_id, nombre FROM torneos ORDER BY nombre ASC");
            while($t = $torneos->fetch_assoc()):
            ?>
                <option value="<?= $t['torneo_id'] ?>" <?= ($t['torneo_id']==$torneo_id)?'selected':'' ?>>
                    <?= htmlspecialchars($t['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Jugador 1:</label>
        <select name="jugador1_id" required>
            <?php
            $usuarios = $conn->query("SELECT user_id, nombre FROM usuarios ORDER BY nombre ASC");
            while($u = $usuarios->fetch_assoc()):
            ?>
                <option value="<?= $u['user_id'] ?>" <?= ($u['user_id']==$jugador1_id)?'selected':'' ?>>
                    <?= htmlspecialchars($u['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Jugador 2:</label>
        <select name="jugador2_id" required>
            <?php
            $usuarios->data_seek(0);
            while($u = $usuarios->fetch_assoc()):
            ?>
                <option value="<?= $u['user_id'] ?>" <?= ($u['user_id']==$jugador2_id)?'selected':'' ?>>
                    <?= htmlspecialchars($u['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Fecha:</label>
        <input type="date" name="fecha" 
            value="<?= $fecha ? date('Y-m-d', strtotime($fecha)) : '' ?>">

        <label>Resultado:</label>
        <input type="text" name="resultado" value="<?= $resultado ?>">

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
