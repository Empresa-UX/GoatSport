<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$admin_id = $_SESSION['usuario_id'];

$mensaje_ok    = $_GET['ok']  ?? null;
$mensaje_error = $_GET['err'] ?? null;

// Si viene POST, actualizar datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');

    if ($nombre === '' || $email === '') {
        header("Location: configuracion.php?err=" . urlencode("Nombre y email son obligatorios."));
        exit();
    }

    $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, email=? WHERE user_id=? AND rol='admin' LIMIT 1");
    $stmt->bind_param("ssi", $nombre, $email, $admin_id);
    $stmt->execute();
    $stmt->close();

    header("Location: configuracion.php?ok=" . urlencode("Perfil actualizado correctamente."));
    exit();
}

// Obtener datos del admin
$stmt = $conn->prepare("SELECT nombre, email, fecha_registro FROM usuarios WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

$nombre = htmlspecialchars($admin['nombre']);
$email = htmlspecialchars($admin['email']);
$fecha_registro = $admin['fecha_registro'];
?>

<div class="section">
    <div class="section-header">
        <h2>Mi perfil (Administrador)</h2>
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

        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div style="
                width:50px; height:50px; border-radius:50%; 
                background:#043b3d; color:#fff; display:flex; 
                justify-content:center; align-items:center;
                font-size:20px; font-weight:bold;">
                <?= strtoupper(substr($nombre, 0, 1)) ?>
            </div>
            <div>
                <strong style="font-size:18px;"><?= $nombre ?></strong><br>
                <span style="font-size:13px; color:#555;">
                    Rol: Administrador<br>
                    Registrado desde: <?= date("d/m/Y H:i", strtotime($fecha_registro)) ?>
                </span>
            </div>
        </div>

        <form method="POST">
            <h3>Datos de la cuenta</h3>

            <label>Nombre:</label>
            <input type="text" name="nombre" value="<?= $nombre ?>" required>

            <label>Email:</label>
            <input type="email" name="email" value="<?= $email ?>" required>

            <label>Contraseña:</label>
            <input type="password" value="********" disabled>
            <small style="display:block; margin-bottom:15px; color:#777;">
                La contraseña se gestiona desde la pantalla de login / recuperación.
            </small>

            <button type="submit" class="btn-add" style="margin-top:10px;">
                Guardar cambios
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
