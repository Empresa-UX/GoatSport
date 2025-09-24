<?php
include './../../config.php';
include './../includes/header.php';

$user_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT puntos FROM ranking WHERE usuario_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$ranking = $result->fetch_assoc();
$stmt->close();

$puntos_actuales = $ranking['puntos'] ?? 0;

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($puntos_actuales >= 200) {
        $nuevo_puntaje = $puntos_actuales - 200;
        $stmt = $conn->prepare("UPDATE ranking SET puntos=? WHERE usuario_id=?");
        $stmt->bind_param("ii", $nuevo_puntaje, $user_id);
        $stmt->execute();
        $stmt->close();


        $nombre = $_POST['nombre'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $estado = 'abierto';

        $stmt = $conn->prepare("INSERT INTO torneos (nombre, creador_id, fecha_inicio, fecha_fin, estado) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sisss", $nombre, $user_id, $fecha_inicio, $fecha_fin, $estado);
        $stmt->execute();
        $stmt->close();

        $mensaje = "<p class='success'>✅ Torneo creado con éxito. Se han descontado 200 puntos.</p>";
        $puntos_actuales = $nuevo_puntaje;
    } else {
        $mensaje = "<p class='error'>⚠️ No tienes suficientes puntos para crear un torneo (necesitas 200).</p>";
    }
}
?>

    <div class="page-wrap">
        <h1 class="page-title">Crear Torneo</h1>

        <?= $mensaje ?>

        <div class="card-white">
            <form method="POST" class="torneo-form">
                <label>Nombre del torneo:</label>
                <input type="text" name="nombre" required>

                <label>Fecha de inicio:</label>
                <input type="date" name="fecha_inicio" required>

                <label>Fecha de fin:</label>
                <input type="date" name="fecha_fin" required>

                <p class="info">Crear un torneo cuesta <strong>200 puntos</strong>. Puntos actuales:
                    <strong><?= $puntos_actuales ?></strong></p>

                <button type="submit" class="btn-add">Crear Torneo</button>
            </form>
        </div>
    </div>

<?php include './../includes/footer.php'; ?>