<?php
// php/proveedor/canchas/canchasForm.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

$cancha_id = $_GET['cancha_id'] ?? null;
$accion    = 'add';
$formTitle = 'Nueva cancha';

// valores por defecto
$nombre = $ubicacion = $tipo = '';
$capacidad = '';
$precio = '0.00';
$hora_apertura = '08:00';
$hora_cierre   = '23:00';
$duracion_turno = 90;
$activa = 1;

if ($cancha_id) {
    $sql = "
        SELECT *
        FROM canchas
        WHERE cancha_id = ? AND proveedor_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cancha_id, $proveedor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nombre          = $row['nombre'];
        $ubicacion       = $row['ubicacion'];
        $tipo            = $row['tipo'];
        $capacidad       = $row['capacidad'];
        $precio          = $row['precio'];
        $hora_apertura   = $row['hora_apertura'] ? substr($row['hora_apertura'],0,5) : '08:00';
        $hora_cierre     = $row['hora_cierre'] ? substr($row['hora_cierre'],0,5) : '23:00';
        $duracion_turno  = $row['duracion_turno'] ?: 90;
        $activa          = $row['activa'];
        $accion          = 'edit';
        $formTitle       = 'Editar cancha';
    } else {
        $stmt->close();
        header("Location: canchas.php");
        exit();
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="canchasAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="cancha_id" value="<?= htmlspecialchars($cancha_id ?? '') ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

        <label>Ubicación:</label>
        <input type="text" name="ubicacion" value="<?= htmlspecialchars($ubicacion) ?>" required>

        <label>Tipo de cancha:</label>
        <input type="text" name="tipo" value="<?= htmlspecialchars($tipo) ?>" placeholder="Indoor, outdoor, césped sintético, etc.">

        <label>Capacidad (jugadores):</label>
        <input type="number" name="capacidad" min="2" max="8" 
               value="<?= htmlspecialchars($capacidad) ?>">

        <label>Precio por turno:</label>
        <input type="number" step="0.01" min="0" name="precio" 
               value="<?= htmlspecialchars($precio) ?>" required>

        <label>Hora de apertura:</label>
        <input type="time" name="hora_apertura" value="<?= htmlspecialchars($hora_apertura) ?>">

        <label>Hora de cierre:</label>
        <input type="time" name="hora_cierre" value="<?= htmlspecialchars($hora_cierre) ?>">

        <label>Duración del turno (minutos):</label>
        <input type="number" name="duracion_turno" min="30" step="15" 
               value="<?= htmlspecialchars($duracion_turno) ?>" required>

        <label>Estado:</label>
        <select name="activa">
            <option value="1" <?= $activa ? 'selected' : '' ?>>Activa</option>
            <option value="0" <?= !$activa ? 'selected' : '' ?>>Inactiva</option>
        </select>

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
        <a href="canchas.php" style="margin-left:10px; font-size:14px; text-decoration:none;">Cancelar</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
