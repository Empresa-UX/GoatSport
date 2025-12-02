<?php
// php/proveedor/notificaciones/notificaciones.php

include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];

// Filtro: todas | no_leidas | leidas
$filtro = $_GET['filtro'] ?? 'todas';
$extraWhere = '';

if ($filtro === 'no_leidas') {
    $extraWhere = ' AND leida = 0';
} elseif ($filtro === 'leidas') {
    $extraWhere = ' AND leida = 1';
}

// Contar no leídas (para el badge), SIEMPRE sin filtro
$sqlCount = "
    SELECT COUNT(*) AS cant
    FROM notificaciones
    WHERE usuario_id = ? AND leida = 0
";
$stmt = $conn->prepare($sqlCount);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$resCount = $stmt->get_result()->fetch_assoc();
$stmt->close();
$noLeidas = (int)($resCount['cant'] ?? 0);

// Traer notificaciones según filtro
$sql = "
    SELECT 
        notificacion_id,
        tipo,
        titulo,
        mensaje,
        creada_en,
        leida
    FROM notificaciones
    WHERE usuario_id = ?
    $extraWhere
    ORDER BY creada_en DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$notis = $stmt->get_result();
$stmt->close();
?>

<div class="section">
    <div class="section-header" style="align-items:center; gap:10px;">
        <h2 style="display:flex; align-items:center; gap:8px;">
            Notificaciones
            <?php if ($noLeidas > 0): ?>
                <span style="
                    font-size: 13px;
                    background:#e53935;
                    color:#fff;
                    border-radius:999px;
                    padding:2px 8px;
                ">
                    <?= $noLeidas ?> sin leer
                </span>
            <?php endif; ?>
        </h2>

        <!-- Filtros -->
        <div style="margin-left:auto; display:flex; gap:6px; align-items:center;">
            <a href="notificaciones.php?filtro=todas"
               style="padding:6px 10px; border-radius:999px; font-size:13px; text-decoration:none;
                      border:1px solid <?= $filtro==='todas' ? '#043b3d' : '#ccc' ?>;
                      background:<?= $filtro==='todas' ? '#043b3d' : '#fff' ?>;
                      color:<?= $filtro==='todas' ? '#fff' : '#333' ?>;">
                Todas
            </a>
            <a href="notificaciones.php?filtro=no_leidas"
               style="padding:6px 10px; border-radius:999px; font-size:13px; text-decoration:none;
                      border:1px solid <?= $filtro==='no_leidas' ? '#e53935' : '#ccc' ?>;
                      background:<?= $filtro==='no_leidas' ? '#e53935' : '#fff' ?>;
                      color:<?= $filtro==='no_leidas' ? '#fff' : '#333' ?>;">
                No leídas
            </a>
            <a href="notificaciones.php?filtro=leidas"
               style="padding:6px 10px; border-radius:999px; font-size:13px; text-decoration:none;
                      border:1px solid <?= $filtro==='leidas' ? '#2e7d32' : '#ccc' ?>;
                      background:<?= $filtro==='leidas' ? '#2e7d32' : '#fff' ?>;
                      color:<?= $filtro==='leidas' ? '#fff' : '#333' ?>;">
                Leídas
            </a>

            <?php if ($noLeidas > 0): ?>
                <form method="POST" action="notificacionesAction.php" style="margin-left:10px;">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn-add" style="background:#555; padding:6px 12px;">
                        Marcar todas como leídas
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .notif-leida-0 {
            background-color: #f5fbff;
            font-weight: 500;
        }
        .notif-leida-1 {
            background-color: #ffffff;
        }
        .notif-tipo-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .notif-tipo-reserva {
            background: #e0f7e9;
            color: #19733b;
        }
        .notif-tipo-torneo {
            background: #e3f2fd;
            color: #1a5fb4;
        }
        .notif-tipo-reporte {
            background: #fff3e0;
            color: #e65100;
        }
        .notif-tipo-sistema {
            background: #eeeeee;
            color: #424242;
        }
        .notif-estado span {
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 12px;
        }
        .notif-estado-leida {
            background:#e8f5e9;
            color:#2e7d32;
            border:1px solid #c8e6c9;
        }
        .notif-estado-no-leida {
            background:#ffebee;
            color:#c62828;
            border:1px solid #ffcdd2;
        }
        .btn-toggle-read {
            border:none;
            cursor:pointer;
            font-size:18px;
            background:transparent;
        }
    </style>

    <table>
        <tr>
            <th>Fecha</th>
            <th>Título</th>
            <th>Mensaje</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($notis->num_rows > 0): ?>
            <?php while ($n = $notis->fetch_assoc()): ?>
                <?php
                    $rowClass = $n['leida'] ? 'notif-leida-1' : 'notif-leida-0';
                    $tipo = $n['tipo'];

                    $pillClass = 'notif-tipo-pill notif-tipo-sistema';
                    if (strpos($tipo, 'reserva') === 0) {
                        $pillClass = 'notif-tipo-pill notif-tipo-reserva';
                    } elseif (strpos($tipo, 'torneo') === 0) {
                        $pillClass = 'notif-tipo-pill notif-tipo-torneo';
                    } elseif (strpos($tipo, 'reporte') === 0) {
                        $pillClass = 'notif-tipo-pill notif-tipo-reporte';
                    }

                    $estadoTexto = $n['leida'] ? 'Leído' : 'No leído';
                    $estadoClass = $n['leida'] ? 'notif-estado-leida' : 'notif-estado-no-leida';
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= htmlspecialchars($n['creada_en']) ?></td>
                    <td><?= htmlspecialchars($n['titulo']) ?></td>
                    <td><?= nl2br(htmlspecialchars($n['mensaje'])) ?></td>
                    <td><span class="<?= $pillClass ?>"><?= htmlspecialchars($tipo) ?></span></td>
                    <td class="notif-estado">
                        <span class="<?= $estadoClass ?>"><?= $estadoTexto ?></span>
                    </td>
                    <td>
                        <form method="POST" action="notificacionesAction.php" style="display:inline-block;">
                            <input type="hidden" name="notificacion_id" value="<?= $n['notificacion_id'] ?>">
                            <?php if ($n['leida']): ?>
                                <!-- Leída → checkbox verde, al click pasa a no leída -->
                                <input type="hidden" name="action" value="mark_unread">
                                <button type="submit" class="btn-toggle-read" title="Marcar como no leída">
                                    ✅
                                </button>
                            <?php else: ?>
                                <!-- No leída → cuadrado blanco, al click pasa a leída -->
                                <input type="hidden" name="action" value="mark_read">
                                <button type="submit" class="btn-toggle-read" title="Marcar como leída">
                                    ☐
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center;">No tienes notificaciones.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
