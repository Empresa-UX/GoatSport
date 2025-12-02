<?php
// php/proveedor/reportes/reportesForm.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];

$reporte_id = $_GET['id'] ?? null;
if (!$reporte_id) {
    header("Location: reportes.php");
    exit();
}

$sql = "
    SELECT 
        r.id,
        r.nombre_reporte,
        r.descripcion,
        r.fecha_reporte,
        r.estado,
        r.respuesta_proveedor,
        r.usuario_id,
        u.nombre AS usuario,
        c.nombre AS cancha_directa,
        c2.nombre AS cancha_reserva,
        res.reserva_id,
        res.fecha AS fecha_reserva,
        res.hora_inicio,
        res.hora_fin
    FROM reportes r
    INNER JOIN usuarios u ON r.usuario_id = u.user_id
    LEFT JOIN canchas c ON r.cancha_id = c.cancha_id
    LEFT JOIN reservas res ON r.reserva_id = res.reserva_id
    LEFT JOIN canchas c2 ON res.cancha_id = c2.cancha_id
    WHERE r.id = ?
      AND (c.proveedor_id = ? OR c2.proveedor_id = ?)
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $reporte_id, $proveedor_id, $proveedor_id);
$stmt->execute();
$result = $stmt->get_result();
$reporte = $result->fetch_assoc();
$stmt->close();

if (!$reporte) {
    header("Location: reportes.php");
    exit();
}

$canchaNombre = $reporte['cancha_directa'] ?: $reporte['cancha_reserva'];

$infoReserva = '';
if ($reporte['reserva_id']) {
    $infoReserva = 'Reserva #'.$reporte['reserva_id'];
    if ($reporte['fecha_reserva']) {
        $infoReserva .= ' - ' . $reporte['fecha_reserva'];
    }
    if ($reporte['hora_inicio'] && $reporte['hora_fin']) {
        $infoReserva .= ' (' . substr($reporte['hora_inicio'],0,5) . ' - ' . substr($reporte['hora_fin'],0,5) . ')';
    }
}
?>

<div class="form-container" style="max-width:650px;">
    <h2>Detalle del reporte #<?= $reporte['id'] ?></h2>

    <form method="POST" action="reportesAction.php">
        <input type="hidden" name="action" value="update_estado">
        <input type="hidden" name="id" value="<?= $reporte['id'] ?>">

        <label>Jugador:</label>
        <input type="text" value="<?= htmlspecialchars($reporte['usuario']) ?>" disabled>

        <label>Fecha del reporte:</label>
        <input type="text" value="<?= htmlspecialchars($reporte['fecha_reporte']) ?>" disabled>

        <label>Cancha:</label>
        <input type="text" value="<?= htmlspecialchars($canchaNombre ?: 'N/A') ?>" disabled>

        <label>Reserva:</label>
        <input type="text" value="<?= htmlspecialchars($infoReserva ?: 'N/A') ?>" disabled>

        <label>Título del reporte:</label>
        <input type="text" value="<?= htmlspecialchars($reporte['nombre_reporte']) ?>" disabled>

        <label>Descripción del jugador:</label>
        <textarea rows="4" disabled><?= htmlspecialchars($reporte['descripcion']) ?></textarea>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="Pendiente" <?= $reporte['estado'] === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="Resuelto"  <?= $reporte['estado'] === 'Resuelto'  ? 'selected' : '' ?>>Resuelto</option>
        </select>

        <label>Respuesta del proveedor (opcional):</label>
        <textarea name="respuesta_proveedor" rows="4"
            placeholder="Ej: Revisamos la cancha y ya se solucionó el problema. Gracias por el aviso.">
<?= htmlspecialchars($reporte['respuesta_proveedor'] ?? '') ?></textarea>

        <button type="submit" class="btn-add" style="margin-top:15px;">Guardar cambios</button>
        <a href="reportes.php" style="margin-left:10px; font-size:14px; text-decoration:none;">Volver</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
