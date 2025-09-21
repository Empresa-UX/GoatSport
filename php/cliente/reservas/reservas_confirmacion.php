<?php
include './../../config.php';
include './../includes/header.php';

// Recuperar los datos de la reserva
$reserva_sess = $_SESSION['reserva'] ?? [];
$canchaId = $reserva_sess['cancha_id'] ?? null;
$fecha = $reserva_sess['fecha'] ?? null;
$horaInicio = $reserva_sess['hora_inicio'] ?? null;
$horaFin = $reserva_sess['hora_fin'] ?? null;

// Método de pago enviado desde POST
$metodo = $_POST['metodo'] ?? null;

// Validación mínima
if (!$canchaId || !$fecha || !$horaInicio) {
    echo "<div class='page-wrap'><p>Error: faltan datos de la reserva (cancha, fecha u hora).</p></div>";
    include './../includes/footer.php';
    exit();
}

// Funciones auxiliares
function parseHoraToDateTime(?string $hora)
{
    if (!$hora)
        return false;
    $formats = ['H:i:s', 'H:i'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, trim($hora));
        if ($dt)
            return $dt;
    }
    try {
        return new DateTime($hora);
    } catch (Exception $e) {
        return false;
    }
}
function formatTimeSQL(DateTime $dt)
{
    return $dt->format('H:i:s');
}

$dtInicio = parseHoraToDateTime($horaInicio);
if (!$dtInicio) {
    echo "<div class='page-wrap'><p>Error: hora inválida.</p></div>";
    include './../includes/footer.php';
    exit();
}

if (!$horaFin) {
    $dtFin = (clone $dtInicio)->add(new DateInterval('PT90M')); // 90 minutos por defecto
    $horaFin = formatTimeSQL($dtFin);
} else {
    $dtFin = parseHoraToDateTime($horaFin);
    if (!$dtFin)
        $dtFin = (clone $dtInicio)->add(new DateInterval('PT90M'));
}

// Formato SQL
$hora_inicio_sql = formatTimeSQL($dtInicio);
$hora_fin_sql = formatTimeSQL($dtFin);

// Datos de la cancha
$canchaNombre = "Cancha #{$canchaId}";
$canchaPrecio = null;
if ($conn) {
    $stmt = $conn->prepare("SELECT nombre, precio FROM canchas WHERE cancha_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $canchaId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $canchaNombre = $row['nombre'];
            $canchaPrecio = $row['precio'];
        }
        $stmt->close();
    }
}

// Insertar reserva con validación de solapamiento
$usuarioId = intval($_SESSION['usuario_id']);
$estado = ($metodo === 'efectivo') ? 'pendiente' : 'confirmada';
$insertedId = null;
$errorMsg = null;

if (!$conn) {
    $errorMsg = "Error de conexión a la base de datos.";
} else {
    try {
        $conn->begin_transaction();
        $checkSql = "SELECT COUNT(*) AS cnt FROM reservas WHERE cancha_id = ? AND fecha = ? AND estado != 'cancelada' AND NOT (hora_fin <= ? OR hora_inicio >= ?)";
        $chk = $conn->prepare($checkSql);
        if ($chk === false)
            throw new Exception($conn->error);
        $chk->bind_param("isss", $canchaId, $fecha, $hora_inicio_sql, $hora_fin_sql);
        $chk->execute();
        $resChk = $chk->get_result();
        $conflictos = intval($resChk->fetch_assoc()['cnt'] ?? 0);
        $chk->close();

        if ($conflictos > 0) {
            $conn->rollback();
            $errorMsg = "Lo sentimos, el horario seleccionado ya fue reservado por otro usuario.";
        } else {
            $insSql = "INSERT INTO reservas (cancha_id, creador_id, fecha, hora_inicio, hora_fin, estado) VALUES (?, ?, ?, ?, ?, ?)";
            $ins = $conn->prepare($insSql);
            if ($ins === false)
                throw new Exception($conn->error);
            $ins->bind_param("iissss", $canchaId, $usuarioId, $fecha, $hora_inicio_sql, $hora_fin_sql, $estado);
            if (!$ins->execute())
                throw new Exception($ins->error);
            $insertedId = $ins->insert_id;
            $ins->close();
            $conn->commit();
            unset($_SESSION['reserva']); // limpiar sesión
        }
    } catch (Exception $e) {
        if ($conn)
            $conn->rollback();
        $errorMsg = "Error interno: " . $e->getMessage();
    }
}
?>

<div class="page-wrap" style="max-width:900px; margin:30px auto;">
    <div class="flow-header">
        <h1>Flujo de Reserva</h1>
        <div class="steps-row">
            <div class="step"><span class="circle">1</span><span class="label">Selección del horario</span></div>
            <div class="step"><span class="circle">2</span><span class="label">Abono</span></div>
            <div class="step active"><span class="circle">3</span><span class="label">Confirmación</span></div>
        </div>
    </div>

    <div class="confirmation-container">
        <?php if ($errorMsg): ?>
            <div class="confirmation-title error">No fue posible completar la reserva</div>
            <div class="summary">
                <div><strong>Motivo:</strong> <span><?= htmlspecialchars($errorMsg) ?></span></div>
            </div>
            <div style="text-align:center; margin-top:20px;">
                <a href="reservas.php?cancha=<?= intval($canchaId) ?>" class="btn back">Volver a elegir horario</a>
            </div>
        <?php else: ?>
            <div class="confirmation-title">Reserva confirmada</div>
            <div class="summary">
                <div><strong>ID reserva:</strong> <?= htmlspecialchars($insertedId) ?></div>
                <div><strong>Cancha:</strong> <?= htmlspecialchars($canchaNombre) ?></div>
                <div><strong>Fecha:</strong> <?= htmlspecialchars($fecha) ?></div>
                <div><strong>Hora:</strong> <?= htmlspecialchars($hora_inicio_sql) ?> -
                    <?= htmlspecialchars($hora_fin_sql) ?></div>
                <div><strong>Método de pago:</strong> <?= htmlspecialchars(ucfirst($metodo ?? 'No elegido')) ?></div>
                <div><strong>Estado:</strong> <?= htmlspecialchars(ucfirst($estado)) ?></div>
                <?php if ($canchaPrecio !== null): ?>
                    <div><strong>Precio:</strong> $ <?= number_format($canchaPrecio, 2, ',', '.') ?></div>
                <?php endif; ?>
            </div>
            <div style="display:flex; gap:10px; justify-content:space-between; margin-top:20px;">
                <a href="mis_reservas.php" class="btn back">Ver mis reservas</a>
                <a href="home_cliente.php" class="btn confirm">Volver al inicio</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include './../includes/footer.php'; ?>