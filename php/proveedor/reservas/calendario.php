<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

$proveedor_id = $_SESSION['usuario_id'];

//
// 1) Cargar las canchas activas del proveedor
//
$sqlCanchas = "
    SELECT cancha_id, nombre 
    FROM canchas 
    WHERE proveedor_id = ? AND activa = 1 
    ORDER BY nombre
";

$stmt = $conn->prepare($sqlCanchas);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$canchas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Si no tiene canchas, mensaje y salir
if (empty($canchas)) {
    ?>
    <div class="section">
        <h2>Calendario de reservas</h2>
        <p>No tienes canchas activas. Crea una desde <strong>“Mis canchas”</strong>.</p>
    </div>
    <?php
    include '../includes/footer.php';
    exit();
}

//
// 2) Fecha seleccionada (GET o hoy)
//
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');

//
// 3) Cancha seleccionada (GET o primera)
//
$cancha_id_seleccionada = $_GET['cancha_id'] ?? $canchas[0]['cancha_id'];

//
// 4) Traer info de la cancha seleccionada
//
$sqlCancha = "
    SELECT nombre, hora_apertura, hora_cierre, duracion_turno
    FROM canchas
    WHERE cancha_id = ? AND proveedor_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sqlCancha);
$stmt->bind_param("ii", $cancha_id_seleccionada, $proveedor_id);
$stmt->execute();
$cancha = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cancha) {
    ?>
    <div class="section">
        <h2>Calendario de reservas</h2>
        <p>La cancha seleccionada no pertenece a tu club.</p>
    </div>
    <?php
    include '../includes/footer.php';
    exit();
}

//
// 5) Traer reservas para esa fecha (de esa cancha)
//
$sqlReservas = "
    SELECT hora_inicio, hora_fin, estado
    FROM reservas
    WHERE cancha_id = ? AND fecha = ?
";

$stmt = $conn->prepare($sqlReservas);
$stmt->bind_param("is", $cancha_id_seleccionada, $fecha_seleccionada);
$stmt->execute();
$reservasDB = $stmt->get_result();
$reservas = [];

while ($r = $reservasDB->fetch_assoc()) {
    $reservas[] = $r;
}
$stmt->close();

//
// 6) Traer eventos especiales (que cruzan ese día y esa cancha)
//
$sqlEventos = "
    SELECT titulo, fecha_inicio, fecha_fin, tipo
    FROM eventos_especiales
    WHERE cancha_id = ?
      AND DATE(fecha_inicio) <= ?
      AND DATE(fecha_fin)   >= ?
";

$stmt = $conn->prepare($sqlEventos);
$stmt->bind_param("iss", $cancha_id_seleccionada, $fecha_seleccionada, $fecha_seleccionada);
$stmt->execute();
$eventosDB = $stmt->get_result();
$eventos = [];

while ($e = $eventosDB->fetch_assoc()) {
    $eventos[] = $e;
}
$stmt->close();

//
// ==== FUNCIONES ===
//

// 6.1: comprobar si un slot (fecha + hora) está dentro del rango del evento
function dentroRangoEvento($fechaSeleccionada, $horaSlot, $evento) {
    // armamos la fecha-hora completa del slot
    $slotDateTime = strtotime($fechaSeleccionada . ' ' . $horaSlot);

    $inicio = strtotime($evento['fecha_inicio']);
    $fin    = strtotime($evento['fecha_fin']);

    return ($slotDateTime >= $inicio && $slotDateTime < $fin);
}

// 6.2: revisar eventos (tienen prioridad sobre reservas)
function getEstadoPorEvento($fechaSeleccionada, $horaSlot, $eventos) {
    // Si quisieras prioridad, acá podrías ordenar por tipo
    foreach ($eventos as $e) {
        if (dentroRangoEvento($fechaSeleccionada, $horaSlot, $e)) {
            return $e['tipo'];  // bloqueo / torneo / promocion / otro
        }
    }
    return null;
}

// 6.3: revisar reservas
function getEstadoPorReserva($horaSlot, $reservas) {
    foreach ($reservas as $r) {
        if ($r['hora_inicio'] <= $horaSlot && $horaSlot < $r['hora_fin']) {
            return $r['estado']; // pendiente / confirmada / cancelada / no_show
        }
    }
    return null;
}

//
// 7) Generar slots
//
$slots = [];

$horaActual = $cancha['hora_apertura'] ?: '08:00:00';
$horaFinDia = $cancha['hora_cierre']   ?: '23:00:00';
$duracion   = (int)($cancha['duracion_turno'] ?: 60);

$tsInicio = strtotime($horaActual);
$tsFin    = strtotime($horaFinDia);

while ($tsInicio < $tsFin) {
    $inicio = date('H:i:s', $tsInicio);
    $fin    = date('H:i:s', $tsInicio + ($duracion * 60));

    // Primero revisamos eventos especiales
    $estado = getEstadoPorEvento($fecha_seleccionada, $inicio, $eventos);

    // Si no hay evento, revisamos reservas
    if ($estado === null) {
        $estado = getEstadoPorReserva($inicio, $reservas);
    }

    $slots[] = [
        'inicio' => $inicio,
        'fin'    => $fin,
        'estado' => $estado
    ];

    $tsInicio += ($duracion * 60);
}
?>

<!-- ============================ HTML / ESTILOS =================================== -->

<div class="section">
    <div class="section-header">
        <h2>Calendario de reservas</h2>
    </div>

    <style>
        .filtros-box {
            display: flex;
            gap: 20px;
            align-items: center;
            background: #fff;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filtros-box label {
            font-weight: bold;
            color: #043b3d;
            font-size: 14px;
        }

        .filtros-box select,
        .filtros-box input[type="date"] {
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            background: #fafafa;
            transition: .2s;
            margin-top: 4px;
        }

        .filtros-box select:focus,
        .filtros-box input[type="date"]:focus {
            border-color: #043b3d;
            background: #fff;
            outline: none;
        }

        /* columnas fijas para que no salten */
        table th:nth-child(1),
        table td:nth-child(1) {
            width: 160px;
        }

        table th:nth-child(2),
        table td:nth-child(2) {
            width: 220px;
        }
    </style>

    <!-- Filtros -->
    <form method="GET" id="filtrosForm" class="filtros-box">
        <label>
            🏟️ Cancha<br>
            <select name="cancha_id" onchange="document.getElementById('filtrosForm').submit()">
                <?php foreach ($canchas as $c): ?>
                    <option 
                        value="<?= $c['cancha_id'] ?>"
                        <?= ($c['cancha_id'] == $cancha_id_seleccionada) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            📅 Fecha<br>
            <input type="date"
                   name="fecha"
                   value="<?= htmlspecialchars($fecha_seleccionada) ?>"
                   onchange="document.getElementById('filtrosForm').submit()">
        </label>
    </form>

    <p style="margin-bottom:10px;">
        Cancha: <strong><?= htmlspecialchars($cancha['nombre']) ?></strong> ·
        Duración turno: <strong><?= (int)$duracion ?> min</strong>
    </p>

    <table>
        <tr>
            <th>Horario</th>
            <th>Estado</th>
        </tr>

        <?php if (!empty($slots)): ?>
            <?php foreach ($slots as $s): ?>
                <?php
                $txt   = 'Disponible';
                $class = 'status-available';

                switch ($s['estado']) {
                    case 'bloqueo':
                        $txt = 'Bloqueado (mantenimiento / evento)';
                        $class = 'status-unavailable';
                        break;
                    case 'torneo':
                        $txt = 'Torneo';
                        $class = 'status-pending';
                        break;
                    case 'promocion':
                        $txt = 'Promo activa';
                        $class = 'status-available';
                        break;
                    case 'otro':
                        $txt = 'Evento especial';
                        $class = 'status-pending';
                        break;
                    case 'confirmada':
                        $txt = 'Reservado (confirmado)';
                        $class = 'status-available';
                        break;
                    case 'pendiente':
                        $txt = 'Reservado (pendiente)';
                        $class = 'status-pending';
                        break;
                    case 'cancelada':
                    case 'no_show':
                        $txt = 'No disponible';
                        $class = 'status-unavailable';
                        break;
                }
                ?>
                <tr>
                    <td><?= substr($s['inicio'], 0, 5) ?> - <?= substr($s['fin'], 0, 5) ?></td>
                    <td><span class="status-pill <?= $class ?>"><?= $txt ?></span></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" style="text-align:center;">No hay horarios para esta cancha.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
