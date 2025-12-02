<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

// Obtener torneo
$torneo_id = $_GET['torneo_id'] ?? null;

if (!$torneo_id) {
    header("Location: torneos.php");
    exit();
}

// Verificar que el torneo pertenece al proveedor
$sql = "
    SELECT nombre, fecha_inicio, fecha_fin
    FROM torneos
    WHERE torneo_id = ? AND proveedor_id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $torneo_id, $proveedor_id);
$stmt->execute();
$torneo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$torneo) {
    header("Location: torneos.php");
    exit();
}

// Traer participantes
$sql = "
    SELECT 
        p.participacion_id,
        u.nombre AS jugador,
        u.email
    FROM participaciones p
    JOIN usuarios u ON p.jugador_id = u.user_id
    WHERE p.torneo_id = ?
    ORDER BY u.nombre ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$participantes = $stmt->get_result();
$stmt->close();
?>

<div class="section">
    <div class="section-header">
        <h2>Participantes del torneo: <?= htmlspecialchars($torneo['nombre']) ?></h2>
        <a href="torneos.php" class="btn-add" style="background:#555;">Volver</a>
    </div>

    <p><strong>Fecha:</strong> <?= htmlspecialchars($torneo['fecha_inicio']) ?> a <?= htmlspecialchars($torneo['fecha_fin']) ?></p>

    <table>
        <tr>
            <th>Jugador</th>
            <th>Email</th>
        </tr>

        <?php if ($participantes->num_rows > 0): ?>
            <?php while ($row = $participantes->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['jugador']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" style="text-align:center;">Aún no hay jugadores inscriptos</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
