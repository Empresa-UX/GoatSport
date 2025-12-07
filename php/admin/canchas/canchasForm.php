<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$cancha_id       = $_GET['cancha_id'] ?? null;
$proveedor_id    = '';
$nombre          = '';
$descripcion     = '';
$ubicacion       = '';
$tipo            = '';
$capacidad       = '';
$precio          = '';
$hora_apertura   = '';
$hora_cierre     = '';
$duracion_turno  = 60;
$activa          = 1;

$accion    = 'add';
$formTitle = 'Agregar Cancha';

if ($cancha_id) {
    $stmt = $conn->prepare("SELECT * FROM canchas WHERE cancha_id = ?");
    $stmt->bind_param("i", $cancha_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $proveedor_id   = $row['proveedor_id'];
        $nombre         = htmlspecialchars($row['nombre']);
        $descripcion    = htmlspecialchars($row['descripcion'] ?? '');
        $ubicacion      = htmlspecialchars($row['ubicacion']);
        $tipo           = htmlspecialchars($row['tipo'] ?? '');
        $capacidad      = $row['capacidad'];
        $precio         = $row['precio'];
        $hora_apertura  = $row['hora_apertura'];
        $hora_cierre    = $row['hora_cierre'];
        $duracion_turno = $row['duracion_turno'];
        $activa         = $row['activa'];
        $accion         = 'edit';
        $formTitle      = 'Editar Cancha';
    }
    $stmt->close();
}

// Proveedores para el combo
$proveedores = $conn->query("
    SELECT user_id, nombre, email
    FROM usuarios
    WHERE rol = 'proveedor'
    ORDER BY nombre ASC
");
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="canchasAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="cancha_id" value="<?= htmlspecialchars($cancha_id) ?>">

        <label>Proveedor:</label>
        <select name="proveedor_id" required>
            <option value="">-- Seleccione proveedor --</option>
            <?php while ($p = $proveedores->fetch_assoc()): ?>
                <option value="<?= $p['user_id'] ?>"
                    <?= ($p['user_id'] == $proveedor_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['email']) ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= $nombre ?>" required>

        <label>Descripción:</label>
        <textarea name="descripcion" rows="3"><?= $descripcion ?></textarea>

        <label>Ubicación:</label>
        <input type="text" name="ubicacion" value="<?= $ubicacion ?>" required>

        <label>Tipo de cancha:</label>
        <input type="text" name="tipo" value="<?= $tipo ?>" placeholder="clásica, panorámica, etc.">

        <label>Capacidad (jugadores):</label>
        <input type="number" name="capacidad" value="<?= htmlspecialchars($capacidad) ?>" min="0">

        <label>Precio por turno:</label>
        <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars($precio) ?>" min="0" required>

        <label>Hora de apertura:</label>
        <input type="time" name="hora_apertura" value="<?= htmlspecialchars($hora_apertura) ?>">

        <label>Hora de cierre:</label>
        <input type="time" name="hora_cierre" value="<?= htmlspecialchars($hora_cierre) ?>">

        <label>Duración del turno (minutos):</label>
        <input type="number" name="duracion_turno" value="<?= (int)$duracion_turno ?>" min="15" step="15" required>

        <label>
            <input type="checkbox" name="activa" value="1" <?= $activa ? 'checked' : '' ?>>
            Cancha activa
        </label>

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
