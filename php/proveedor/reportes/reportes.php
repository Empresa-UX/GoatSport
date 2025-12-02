<?php
// php/proveedor/reportes/reportes.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];

// Filtro por estado
$filtro_estado = $_GET['estado'] ?? 'todos';
$extraEstado = '';
$types = "ii";
$params = [$proveedor_id, $proveedor_id];

if ($filtro_estado === 'Pendiente' || $filtro_estado === 'Resuelto') {
    $extraEstado = " AND r.estado = ? ";
    $types .= "s";
    $params[] = $filtro_estado;
}

// Query
$sql = "
    SELECT 
        r.id,
        r.nombre_reporte,
        r.descripcion,
        r.fecha_reporte,
        r.estado,
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
    WHERE 
        (c.proveedor_id = ? OR c2.proveedor_id = ?)
        $extraEstado
    ORDER BY r.fecha_reporte DESC, r.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section">
    <div class="section-header">
        <h2>Reportes de jugadores</h2>
    </div>

    <style>
        .filtro-reportes {
            margin-bottom:15px; 
            display:flex; 
            gap:10px; 
            align-items:center; 
            flex-wrap:wrap;
            background:#fff;
            padding:10px 15px;
            border-radius:10px;
            box-shadow:0 3px 8px rgba(0,0,0,0.05);
        }

        .filtro-reportes label {
            font-size:14px;
            color:#043b3d;
            font-weight:bold;
        }
        .filtro-reportes select {
            margin-left:6px;
            padding:5px 10px;
            border-radius:8px;
            border:1px solid #ccc;
            font-size:14px;
            background:#fafafa;
        }
        .filtro-reportes select:focus {
            outline:none;
            border-color:#043b3d;
            background:#fff;
        }

        .estado-select-inline select {
            font-size:13px;
            padding:4px 10px;
            border-radius:999px;
            border:1px solid #ccc;
            cursor:pointer;
            font-weight:600;
        }

        /* Colores dinámicos */
        .select-pendiente {
            background:#f9e49c;
            border-color:#e2c766;
            color:#6c5911;
        }

        .select-resuelto {
            background:#b7e9b0;
            border-color:#89c784;
            color:#1f5c1f;
        }
    </style>

    <!-- Filtro -->
    <form method="GET" class="filtro-reportes">
        <label>
            Estado:
            <select name="estado" onchange="this.form.submit()">
                <option value="todos"     <?= $filtro_estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="Pendiente" <?= $filtro_estado === 'Pendiente' ? 'selected' : '' ?>>Pendientes</option>
                <option value="Resuelto"  <?= $filtro_estado === 'Resuelto' ? 'selected' : '' ?>>Resueltos</option>
            </select>
        </label>
    </form>

    <table>
        <tr>
            <th>Fecha</th>
            <th>Jugador</th>
            <th>Cancha / Reserva</th>
            <th>Título</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>

                <?php
                $canchaNombre = $row['cancha_directa'] ?: $row['cancha_reserva'];

                $infoReserva = '';
                if ($row['reserva_id']) {
                    $infoReserva = 'Reserva #'.$row['reserva_id'];
                    if ($row['fecha_reserva']) $infoReserva .= ' - '.$row['fecha_reserva'];
                    if ($row['hora_inicio'])   $infoReserva .= ' ('.substr($row['hora_inicio'],0,5).' - '.substr($row['hora_fin'],0,5).')';
                }

                // clase para el color del select
                $selectClass = $row['estado'] === 'Resuelto' ? 'select-resuelto' : 'select-pendiente';
                ?>

                <tr>
                    <td><?= htmlspecialchars($row['fecha_reporte']) ?></td>
                    <td><?= htmlspecialchars($row['usuario']) ?></td>
                    <td>
                        <?php if ($canchaNombre): ?>
                            <strong><?= htmlspecialchars($canchaNombre) ?></strong><br>
                        <?php endif; ?>
                        <?php if ($infoReserva): ?>
                            <small style="color:#555;"><?= htmlspecialchars($infoReserva) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['nombre_reporte']) ?></td>

                    <td>
                        <form method="POST" action="reportesAction.php" class="estado-select-inline">
                            <input type="hidden" name="action" value="update_estado">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">

                            <select name="estado"
                                    class="<?= $selectClass ?>"
                                    onchange="this.className = (this.value=='Resuelto') ? 'select-resuelto' : 'select-pendiente'; this.form.submit();">
                                <option value="Pendiente" <?= $row['estado']==='Pendiente'?'selected':'' ?>>Pendiente</option>
                                <option value="Resuelto"  <?= $row['estado']==='Resuelto'?'selected':'' ?>>Resuelto</option>
                            </select>
                        </form>
                    </td>

                    <td>
                        <button class="btn-action edit"
                            onclick="location.href='reportesForm.php?id=<?= $row['id'] ?>'">✏️</button>
                    </td>
                </tr>

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center;">No hay reportes para tus canchas.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
