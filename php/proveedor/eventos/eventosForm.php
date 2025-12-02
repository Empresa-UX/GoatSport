<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$evento_id = $_GET['evento_id'] ?? null;

// Cargar canchas del proveedor
$stmt = $conn->prepare("SELECT cancha_id, nombre FROM canchas WHERE proveedor_id = ? ORDER BY nombre");
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$canchas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Default values
$titulo = $descripcion = "";
$fecha_inicio = date('Y-m-d\TH:i');
$fecha_fin = date('Y-m-d\TH:i');
$tipo = "bloqueo";
$cancha_id = null;

$accion = 'add';
$formTitle = 'Nuevo evento especial';

if ($evento_id) {
    $sql = "SELECT * FROM eventos_especiales WHERE evento_id = ? AND proveedor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $evento_id, $proveedor_id);
    $stmt->execute();
    $e = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$e) {
        header("Location: eventos.php");
        exit();
    }

    $titulo = $e['titulo'];
    $descripcion = $e['descripcion'];
    $fecha_inicio = date('Y-m-d\TH:i', strtotime($e['fecha_inicio']));
    $fecha_fin = date('Y-m-d\TH:i', strtotime($e['fecha_fin']));
    $tipo = $e['tipo'];
    $cancha_id = $e['cancha_id'];

    $accion = 'edit';
    $formTitle = 'Editar evento especial';
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form action="eventosAction.php" method="POST">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="evento_id" value="<?= $evento_id ?>">

        <label>Título:</label>
        <input type="text" name="titulo" required value="<?= htmlspecialchars($titulo) ?>">

        <label>Descripción:</label>
        <textarea name="descripcion"><?= htmlspecialchars($descripcion) ?></textarea>

        <label>Tipo:</label>
        <select name="tipo">
            <option value="bloqueo" <?= $tipo == "bloqueo" ? 'selected':'' ?>>Bloqueo</option>
            <option value="torneo" <?= $tipo == "torneo" ? 'selected':'' ?>>Torneo</option>
            <option value="promocion" <?= $tipo == "promocion" ? 'selected':'' ?>>Promoción</option>
            <option value="otro" <?= $tipo == "otro" ? 'selected':'' ?>>Otro</option>
        </select>

        <label>Cancha:</label>
        <select name="cancha_id" required>
            <?php foreach ($canchas as $c): ?>
                <option value="<?= $c['cancha_id'] ?>" 
                    <?= $cancha_id == $c['cancha_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Fecha inicio:</label>
        <input type="datetime-local" name="fecha_inicio" required value="<?= $fecha_inicio ?>">

        <label>Fecha fin:</label>
        <input type="datetime-local" name="fecha_fin" required value="<?= $fecha_fin ?>">

        <button class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
