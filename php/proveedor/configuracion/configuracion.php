<?php
// php/proveedor/configuracion/configuracion.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];

// 1) Datos básicos del usuario
$sql = "SELECT nombre, email FROM usuarios WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

$nombre = $usuario['nombre'] ?? '';
$email  = $usuario['email'] ?? '';

// 2) Datos del club (proveedores_detalle)
$sql = "
    SELECT nombre_club, telefono, direccion, ciudad, descripcion
    FROM proveedores_detalle
    WHERE proveedor_id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
$detalle = $result->fetch_assoc();
$stmt->close();

$nombre_club = $detalle['nombre_club'] ?? '';
$telefono    = $detalle['telefono'] ?? '';
$direccion   = $detalle['direccion'] ?? '';
$ciudad      = $detalle['ciudad'] ?? '';
$descripcion = $detalle['descripcion'] ?? '';

$mensaje_ok    = $_GET['ok']  ?? null;
$mensaje_error = $_GET['err'] ?? null;
?>

<div class="section">
    <div class="section-header">
        <h2>Mi perfil / Datos del club</h2>
    </div>

    <?php if ($mensaje_ok): ?>
        <div style="padding:10px; margin-bottom:15px; border-radius:8px; background:#e1f7e1; color:#2e7d32;">
            <?= htmlspecialchars($mensaje_ok) ?>
        </div>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
        <div style="padding:10px; margin-bottom:15px; border-radius:8px; background:#fdecea; color:#c62828;">
            <?= htmlspecialchars($mensaje_error) ?>
        </div>
    <?php endif; ?>

    <div class="form-container" style="max-width:600px;">
        <form action="configuracionAction.php" method="POST">
            <input type="hidden" name="action" value="update_profile">

            <h3>Datos de la cuenta</h3>

            <label>Nombre (contacto):</label>
            <input type="text" name="nombre" required value="<?= htmlspecialchars($nombre) ?>">

            <label>Email:</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>">

            <hr style="margin:20px 0;">

            <h3>Datos del club</h3>

            <label>Nombre del club:</label>
            <input type="text" name="nombre_club" value="<?= htmlspecialchars($nombre_club) ?>">

            <label>Teléfono / WhatsApp:</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>">

            <label>Dirección:</label>
            <input type="text" name="direccion" value="<?= htmlspecialchars($direccion) ?>">

            <label>Ciudad:</label>
            <input type="text" name="ciudad" value="<?= htmlspecialchars($ciudad) ?>">

            <label>Descripción del club:</label>
            <textarea name="descripcion" rows="4"><?= htmlspecialchars($descripcion) ?></textarea>

            <button type="submit" class="btn-add" style="margin-top:15px;">
                Guardar cambios
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
