<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$id = $_GET['id'] ?? null;

$nombre_reporte = '';
$descripcion = '';
$respuesta_proveedor = '';
$usuario_id = '';
$cancha_id = '';
$reserva_id = '';
$fecha_reporte = date('Y-m-d');
$estado = 'Pendiente';

$accion = 'add';
$formTitle = 'Agregar Reporte';

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM reportes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $nombre_reporte      = $row['nombre_reporte'];
        $descripcion         = $row['descripcion'];
        $respuesta_proveedor = $row['respuesta_proveedor'];
        $usuario_id          = $row['usuario_id'];
        $cancha_id           = $row['cancha_id'];
        $reserva_id          = $row['reserva_id'];
        $fecha_reporte       = $row['fecha_reporte'];
        $estado              = $row['estado'];
        $accion              = 'edit';
        $formTitle           = 'Editar Reporte';
    }
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

        <label>Respuesta del proveedor:</label>
        <textarea name="respuesta_proveedor"><?= $respuesta_proveedor ?></textarea>

        <label>Usuario:</label>
        <select name="usuario_id" required>
            <?php
            $usuarios = $conn->query("SELECT user_id, nombre FROM usuarios ORDER BY nombre ASC");
            while($u = $usuarios->fetch_assoc()):
            ?>
            <option value="<?= $u['user_id'] ?>" <?= $usuario_id==$u['user_id']?'selected':'' ?>>
                <?= htmlspecialchars($u['nombre']) ?>
            </option>
            <?php endwhile; ?>
        </select>

        <label>Cancha relacionada:</label>
        <select name="cancha_id">
            <option value="">Ninguna</option>
            <?php
            $canchas = $conn->query("SELECT cancha_id, nombre FROM canchas ORDER BY nombre");
            while($c = $canchas->fetch_assoc()):
            ?>
            <option value="<?= $c['cancha_id'] ?>" <?= $cancha_id==$c['cancha_id']?'selected':'' ?>>
                <?= htmlspecialchars($c['nombre']) ?>
            </option>
            <?php endwhile; ?>
        </select>

        <label>Reserva relacionada:</label>
        <select name="reserva_id">
            <option value="">Ninguna</option>
            <?php
            $reservas = $conn->query("
                SELECT reserva_id, fecha, hora_inicio, hora_fin 
                FROM reservas ORDER BY reserva_id DESC
            ");
            while($r = $reservas->fetch_assoc()):
            ?>
            <option value="<?= $r['reserva_id'] ?>" <?= $reserva_id==$r['reserva_id']?'selected':'' ?>>
                #<?= $r['reserva_id'] ?> - <?= $r['fecha'] ?> <?= $r['hora_inicio'] ?>
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
