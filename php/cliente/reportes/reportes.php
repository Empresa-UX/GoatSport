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
                <input type="text" id="nombre_reporte" name="nombre_reporte" placeholder="Ej: Falla en la iluminación" required>

                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" placeholder="Describe el problema o sugerencia..." required></textarea>

                <button type="submit" class="btn-add">Enviar Reporte</button>
            </form>
        </div>
    </div>

<style>
/* Hereda el look & feel de torneos.php */
.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 30px;
    text-align: center;
    color: #222;
}

.card-white {
    background: #fff;
    border-radius: 16px;
    padding: 30px 40px;
    box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.08);
    max-width: 600px;
    margin: 0 auto;
    transition: transform 0.2s ease;
}
.card-white:hover {
    transform: translateY(-2px);
}

.torneo-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.torneo-form label {
    font-weight: 600;
    font-size: 1rem;
    color: #333;
}

.torneo-form input,
.torneo-form textarea {
    width: 100%;
    padding: 12px 14px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 10px;
    background-color: #f9f9f9;
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    font-family: inherit;
    color: #333;
    box-sizing: border-box;
}

.torneo-form input:focus,
.torneo-form textarea:focus {
    border-color: var(--teal-500);
    background-color: #fff;
    box-shadow: 0 0 0 2px rgba(27, 171, 157, 0.2);
    outline: none;
}

.torneo-form textarea {
    resize: vertical;
    min-height: 120px;
    line-height: 1.5;
}

.btn-add {
    background-color: var(--teal-500);
    color: white;
    border: none;
    padding: 14px;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.1s ease;
}

.btn-add:hover {
    background-color: #139488;
    transform: scale(1.02);
}

.success,
.error {
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 600;
    text-align: center;
}

.success {
    background: #e6ffed;
    color: #1d7a2e;
    border: 1px solid #1d7a2e;
}

.error {
    background: #ffe6e6;
    color: #a94442;
    border: 1px solid #a94442;
}
</style>

<?php include './../includes/footer.php'; ?>
