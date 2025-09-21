<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$cancha_id = $_GET['cancha_id'] ?? null;
$nombre = $ubicacion = $tipo = '';
$capacidad = 0;
$precio = 0.00;
$accion = 'add';
$formTitle = 'Agregar Cancha';

if($cancha_id){
    $stmt = $conn->prepare("SELECT * FROM canchas WHERE cancha_id=?");
    $stmt->bind_param("i", $cancha_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $nombre = htmlspecialchars($row['nombre']);
        $ubicacion = htmlspecialchars($row['ubicacion']);
        $tipo = htmlspecialchars($row['tipo']);
        $capacidad = (int)$row['capacidad'];
        $precio = (float)$row['precio'];
        $accion = 'edit';
        $formTitle = 'Editar Cancha';
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="canchasAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="cancha_id" value="<?= $cancha_id ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= $nombre ?>" required>

        <label>Ubicación:</label>
        <input type="text" name="ubicacion" value="<?= $ubicacion ?>" required>

        <label>Tipo:</label>
        <input type="text" name="tipo" value="<?= $tipo ?>">

        <label>Capacidad:</label>
        <input type="number" name="capacidad" value="<?= $capacidad ?>" min="0">

        <label>Precio:</label>
        <input type="number" step="0.01" name="precio" value="<?= $precio ?>" min="0">

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
