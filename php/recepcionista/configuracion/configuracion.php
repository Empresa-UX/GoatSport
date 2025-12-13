<?php
// php/recepcionista/configuracion/configuracion.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'recepcionista') {
    header("Location: ../login.php");
    exit();
}

$recepcionista_id = (int)$_SESSION['usuario_id'];
$proveedor_id     = (int)($_SESSION['proveedor_id'] ?? 0);

/* 1) Datos de la cuenta */
$sql = "SELECT nombre, email FROM usuarios WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recepcionista_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$nombre = $usuario['nombre'] ?? '';
$email  = $usuario['email'] ?? '';

/* 2) Datos del club (solo lectura) */
$nombre_club = $telefono = $direccion = $ciudad = $descripcion = '';
if ($proveedor_id > 0) {
    $sql = "SELECT nombre_club, telefono, direccion, ciudad, descripcion
            FROM proveedores_detalle
            WHERE proveedor_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $proveedor_id);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($detalle) {
        $nombre_club = $detalle['nombre_club'] ?? '';
        $telefono    = $detalle['telefono'] ?? '';
        $direccion   = $detalle['direccion'] ?? '';
        $ciudad      = $detalle['ciudad'] ?? '';
        $descripcion = $detalle['descripcion'] ?? '';
    }
}

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
            <input type="text" value="<?= htmlspecialchars($nombre_club) ?>" readonly>

            <label>Teléfono / WhatsApp:</label>
            <input type="text" value="<?= htmlspecialchars($telefono) ?>" readonly>

            <label>Dirección:</label>
            <input type="text" value="<?= htmlspecialchars($direccion) ?>" readonly>

            <label>Ciudad:</label>
            <input type="text" value="<?= htmlspecialchars($ciudad) ?>" readonly>

            <label>Descripción del club:</label>
            <textarea rows="4" readonly><?= htmlspecialchars($descripcion) ?></textarea>

            <button type="submit" class="btn-add" style="margin-top:15px;">Guardar cambios</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
