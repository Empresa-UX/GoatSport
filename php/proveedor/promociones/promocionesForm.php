<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id  = $_SESSION['usuario_id'];
$promocion_id  = $_GET['promocion_id'] ?? null;

// cargar canchas del proveedor
$stmt = $conn->prepare("SELECT cancha_id, nombre FROM canchas WHERE proveedor_id = ?");
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$canchas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// valores por defecto
$nombre = $descripcion = '';
$porcentaje_descuento = 0;
$minima_reservas = 0;
$fecha_inicio = $fecha_fin = '';
$hora_inicio = $hora_fin = '';
$dias_semana = [];
$cancha_id = null;
$activa = 1;

$accion = 'add';
$title  = 'Crear promoción';

// si estamos editando
if ($promocion_id) {
    $sql = "SELECT * FROM promociones WHERE promocion_id = ? AND proveedor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $promocion_id, $proveedor_id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$p) {
        header("Location: promociones.php");
        exit();
    }

    $nombre = $p['nombre'];
    $descripcion = $p['descripcion'];
    $porcentaje_descuento = $p['porcentaje_descuento'];
    $minima_reservas = $p['minima_reservas'];
    $fecha_inicio = $p['fecha_inicio'];
    $fecha_fin = $p['fecha_fin'];
    $hora_inicio = $p['hora_inicio'];
    $hora_fin = $p['hora_fin'];
    $dias_semana = $p['dias_semana'] ? explode(',', $p['dias_semana']) : [];
    $cancha_id = $p['cancha_id'];
    $activa = $p['activa'];

    $accion = 'edit';
    $title  = 'Editar promoción';
}
?>

<div class="form-container">
    <h2><?= $title ?></h2>

    <form method="POST" action="promocionesAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="promocion_id" value="<?= $promocion_id ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

        <label>Descripción:</label>
        <textarea name="descripcion"><?= htmlspecialchars($descripcion) ?></textarea>

        <label>Aplicar a cancha:</label>
        <select name="cancha_id">
            <option value="">Todas</option>
            <?php foreach ($canchas as $c): ?>
                <option value="<?= $c['cancha_id'] ?>" 
                    <?= ($c['cancha_id']==$cancha_id)?'selected':'' ?>>
                    <?= htmlspecialchars($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Descuento (%):</label>
        <input type="number" step="0.01" name="porcentaje_descuento"
               value="<?= $porcentaje_descuento ?>" required>

        <label>Mínimo de reservas del jugador:</label>
        <input type="number" name="minima_reservas" min="0"
               value="<?= $minima_reservas ?>">

        <label>Fecha de vigencia:</label>
        <div style="display:flex; gap:10px;">
            <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" required>
            <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" required>
        </div>

        <label>Rango horario (opcional):</label>
        <div style="display:flex; gap:10px;">
            <input type="time" name="hora_inicio" value="<?= $hora_inicio ?>">
            <input type="time" name="hora_fin" value="<?= $hora_fin ?>">
        </div>

        <label>Días de la semana (opcional):</label>
        <?php
            $dias = [
                '1' => 'Lunes',
                '2' => 'Martes',
                '3' => 'Miércoles',
                '4' => 'Jueves',
                '5' => 'Viernes',
                '6' => 'Sábado',
                '7' => 'Domingo'
            ];
        ?>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php foreach ($dias as $num => $label): ?>
                <label>
                    <input type="checkbox" name="dias_semana[]" value="<?= $num ?>"
                        <?= in_array($num, $dias_semana) ? 'checked' : '' ?>>
                    <?= $label ?>
                </label>
            <?php endforeach; ?>
        </div>

        <label>Activa:</label>
        <select name="activa">
            <option value="1" <?= $activa?'selected':'' ?>>Sí</option>
            <option value="0" <?= !$activa?'selected':'' ?>>No</option>
        </select>

        <button type="submit" class="btn-add"><?= $title ?></button>
        <a href="promociones.php" style="margin-left:10px;">Volver</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
