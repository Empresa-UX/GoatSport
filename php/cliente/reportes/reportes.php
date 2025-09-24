<?php
include './../../config.php';
include './../includes/header.php';

$user_id = $_SESSION['usuario_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_reporte = $_POST['nombre_reporte'];
    $descripcion = $_POST['descripcion'];
    $fecha_reporte = date('Y-m-d');
    $estado = 'Pendiente';

    $stmt = $conn->prepare("INSERT INTO reportes (nombre_reporte, descripcion, usuario_id, fecha_reporte, estado) VALUES (?,?,?,?,?)");
    $stmt->bind_param("ssiss", $nombre_reporte, $descripcion, $user_id, $fecha_reporte, $estado);
    if ($stmt->execute()) {
        $mensaje = "<p class='success'>✅ Reporte creado con éxito.</p>";
    } else {
        $mensaje = "<p class='error'>⚠️ Error al crear el reporte: " . htmlspecialchars($stmt->error) . "</p>";
    }
    $stmt->close();
}
?>

<div class="page-wrap">
    <h1 class="page-title">Crear Reporte</h1>

    <?= $mensaje ?>

    <div class="card-white">
        <form method="POST" class="torneo-form">
            <label for="nombre_reporte">Nombre del reporte</label>
            <input 
                type="text" 
                id="nombre_reporte" 
                name="nombre_reporte" 
                placeholder="Ej: Falla en la iluminación" 
                required
            >

            <label for="descripcion">Descripción</label>
            <textarea 
                id="descripcion" 
                name="descripcion" 
                placeholder="Describe el problema o sugerencia con detalle..." 
                rows="6" 
                required
            ></textarea>

                <p class="info">Los deben estar bien enumerados para un mejor servicio</p>
            <button type="submit" class="btn-add">Enviar Reporte</button>
        </form>
    </div>
</div>

<?php include './../includes/footer.php'; ?>
