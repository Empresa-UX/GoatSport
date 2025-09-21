<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$id = $_GET['id'] ?? null;
$nombre_reporte = $descripcion = '';
$usuario_id = '';
$fecha_reporte = date('Y-m-d');
$estado = 'Pendiente';
$accion = 'add';
$formTitle = 'Agregar Reporte';

if($id){
    $stmt = $conn->prepare("SELECT * FROM reportes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $nombre_reporte = htmlspecialchars($row['nombre_reporte']);
        $descripcion = htmlspecialchars($row['descripcion']);
        $usuario_id = $row['usuario_id'];
        $fecha_reporte = $row['fecha_reporte'];
        $estado = $row['estado'];
        $accion = 'edit';
        $formTitle = 'Editar Reporte';
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="reportesAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <label>Nombre del reporte:</label>
        <input type="text" name="nombre_reporte" value="<?= $nombre_reporte ?>" required>

        <label>Descripción:</label>
        <textarea name="descripcion" required><?= $descripcion ?></textarea>

        <label>Usuario:</label>
        <select name="usuario_id" required>
            <?php
            $usuarios = $conn->query("SELECT user_id, nombre, email FROM usuarios ORDER BY nombre ASC");
            while($u = $usuarios->fetch_assoc()):
            ?>
            <option value="<?= $u['user_id'] ?>" <?= $u['user_id']==$usuario_id?'selected':'' ?>>
                <?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['email']) ?>)
            </option>
            <?php endwhile; ?>
        </select>

        <label>Fecha:</label>
        <input type="date" name="fecha_reporte" value="<?= $fecha_reporte ?>" required>

        <label>Estado:</label>
        <select name="estado">
            <option value="Pendiente" <?= $estado=='Pendiente'?'selected':'' ?>>Pendiente</option>
            <option value="Resuelto" <?= $estado=='Resuelto'?'selected':'' ?>>Resuelto</option>
        </select>

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
