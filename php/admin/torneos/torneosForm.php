<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$torneo_id     = $_GET['torneo_id'] ?? null;
$nombre        = '';
$creador_id    = '';
$proveedor_id  = 0; // 0 = sin asignar
$fecha_inicio  = '';
$fecha_fin     = '';
$estado        = 'abierto';
$puntos        = 0;

$accion    = 'add';
$formTitle = 'Crear Torneo';

if ($torneo_id) {
    $stmt = $conn->prepare("SELECT * FROM torneos WHERE torneo_id = ?");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nombre        = $row['nombre'];
        $creador_id    = $row['creador_id'];
        $proveedor_id  = $row['proveedor_id'] ?? 0;
        $fecha_inicio  = $row['fecha_inicio'];
        $fecha_fin     = $row['fecha_fin'];
        $estado        = $row['estado'];
        $puntos        = (int)$row['puntos_ganador'];
        $accion        = 'edit';
        $formTitle     = 'Editar Torneo';
    }
    $stmt->close();
}

// Combo de usuarios (creador)
$usuarios = $conn->query("SELECT user_id, nombre FROM usuarios ORDER BY nombre ASC");

// Combo de proveedores
$proveedores = $conn->query("SELECT user_id, nombre FROM usuarios WHERE rol = 'proveedor' ORDER BY nombre ASC");
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="torneosAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="torneo_id" value="<?= htmlspecialchars($torneo_id) ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

        <label>Creador:</label>
        <select name="creador_id" required>
            <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                <?php while ($u = $usuarios->fetch_assoc()): ?>
                    <option value="<?= $u['user_id'] ?>" <?= ($u['user_id'] == $creador_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>

        <label>Proveedor (club donde se juega):</label>
        <select name="proveedor_id">
            <option value="0">-- Sin asignar --</option>
            <?php if ($proveedores && $proveedores->num_rows > 0): ?>
                <?php while ($p = $proveedores->fetch_assoc()): ?>
                    <option value="<?= $p['user_id'] ?>" <?= ($p['user_id'] == $proveedor_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>

        <label>Fecha inicio:</label>
        <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>" required>

        <label>Fecha fin:</label>
        <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>" required>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="abierto"     <?= ($estado === 'abierto')     ? 'selected' : '' ?>>Abierto</option>
            <option value="cerrado"     <?= ($estado === 'cerrado')     ? 'selected' : '' ?>>Cerrado</option>
            <option value="finalizado"  <?= ($estado === 'finalizado')  ? 'selected' : '' ?>>Finalizado</option>
        </select>

        <label>Puntos para el ganador:</label>
        <input type="number" name="puntos_ganador" value="<?= htmlspecialchars($puntos) ?>" min="0">

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
