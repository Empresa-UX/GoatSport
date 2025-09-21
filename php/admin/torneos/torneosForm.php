<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$torneo_id = $_GET['torneo_id'] ?? null;
$nombre = $creador_id = $fecha_inicio = $fecha_fin = $estado = '';
$accion = 'add';
$formTitle = 'Crear Torneo';

if($torneo_id){
    $stmt = $conn->prepare("SELECT * FROM torneos WHERE torneo_id=?");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $nombre = $row['nombre'];
        $creador_id = $row['creador_id'];
        $fecha_inicio = $row['fecha_inicio'];
        $fecha_fin = $row['fecha_fin'];
        $estado = $row['estado'];
        $accion = 'edit';
        $formTitle = 'Editar Torneo';
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="torneosAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="torneo_id" value="<?= $torneo_id ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

        <label>Creador:</label>
        <select name="creador_id" required>
            <?php
            $usuarios = $conn->query("SELECT user_id, nombre FROM usuarios ORDER BY nombre ASC");
            while($u = $usuarios->fetch_assoc()):
            ?>
                <option value="<?= $u['user_id'] ?>" <?= ($u['user_id']==$creador_id)?'selected':'' ?>>
                    <?= htmlspecialchars($u['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Fecha inicio:</label>
        <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" required>

        <label>Fecha fin:</label>
        <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" required>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="abierto" <?= ($estado=='abierto')?'selected':'' ?>>Abierto</option>
            <option value="cerrado" <?= ($estado=='cerrado')?'selected':'' ?>>Cerrado</option>
        </select><br><br>

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
