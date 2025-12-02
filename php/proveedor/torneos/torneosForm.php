<?php
// php/proveedor/torneos/torneosForm.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

$torneo_id = $_GET['torneo_id'] ?? null;
$accion    = 'add';
$formTitle = 'Crear torneo';

// valores iniciales
$nombre = '';
$fecha_inicio = '';
$fecha_fin = '';
$estado = 'abierto';
$puntos_ganador = 0;

if ($torneo_id) {
    $sql = "
        SELECT *
        FROM torneos
        WHERE torneo_id = ? AND proveedor_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $torneo_id, $proveedor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nombre          = $row['nombre'];
        $fecha_inicio    = $row['fecha_inicio'];
        $fecha_fin       = $row['fecha_fin'];
        $estado          = $row['estado'];
        $puntos_ganador  = $row['puntos_ganador'];
        $accion          = 'edit';
        $formTitle       = 'Editar torneo';
    } else {
        $stmt->close();
        header("Location: torneos.php");
        exit();
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="torneosAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="torneo_id" value="<?= htmlspecialchars($torneo_id ?? '') ?>">

        <label>Nombre del torneo:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

        <label>Fecha de inicio:</label>
        <input type="date" name="fecha_inicio" 
               value="<?= $fecha_inicio ? htmlspecialchars($fecha_inicio) : '' ?>" required>

        <label>Fecha de fin:</label>
        <input type="date" name="fecha_fin" 
               value="<?= $fecha_fin ? htmlspecialchars($fecha_fin) : '' ?>" required>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="abierto"    <?= $estado=='abierto'    ? 'selected' : '' ?>>Abierto</option>
            <option value="cerrado"    <?= $estado=='cerrado'    ? 'selected' : '' ?>>Cerrado</option>
            <option value="finalizado" <?= $estado=='finalizado' ? 'selected' : '' ?>>Finalizado</option>
        </select>

        <label>Puntos para el ganador:</label>
        <input type="number" name="puntos_ganador" min="0" 
               value="<?= htmlspecialchars($puntos_ganador) ?>" required>

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
        <a href="torneos.php" style="margin-left:10px; font-size:14px; text-decoration:none;">Cancelar</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
