<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\historial_estadisticas\detalle_reserva.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

$userId    = (int)$_SESSION['usuario_id'];
$reservaId = isset($_GET['reserva_id']) ? (int)$_GET['reserva_id'] : 0;
if ($reservaId <= 0) {
    header("Location: /php/cliente/historial_estadisticas/historial_estadisticas.php");
    exit;
}

/* === Acciones POST === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'aceptar' || $accion === 'rechazar') {
        $nuevo = $accion === 'aceptar' ? 'aceptada' : 'rechazada';
        $q = $conn->prepare("UPDATE participaciones SET estado=? WHERE reserva_id=? AND jugador_id=? AND estado='pendiente'");
        $q->bind_param("sii", $nuevo, $reservaId, $userId);
        $q->execute();
        header("Location: /php/cliente/historial_estadisticas/detalle_reserva.php?reserva_id=".$reservaId);
        exit;
    }

    if ($accion === 'cancelar') {
        /* Cancelar solo si: soy creador, no está cancelada, y aún no pasó (según NOW() de MySQL) */
        $u = $conn->prepare("
            UPDATE reservas
               SET estado='cancelada'
             WHERE reserva_id=? AND creador_id=? AND estado<>'cancelada'
               AND CONCAT(fecha,' ',hora_inicio) > NOW()
            LIMIT 1
        ");
        $u->bind_param("ii", $reservaId, $userId);
        $u->execute();
        $ok = ($u->affected_rows === 1);
        $u->close();

        if ($ok) {
            $u2 = $conn->prepare("UPDATE pagos SET estado='cancelado' WHERE reserva_id=? AND estado='pendiente'");
            $u2->bind_param("i", $reservaId);
            $u2->execute();
            $u2->close();
        }
        header("Location: /php/cliente/historial_estadisticas/detalle_reserva.php?reserva_id=".$reservaId);
        exit;
    }
}

/* === Carga de datos === */
/* Ver y chequear acceso */
$sql_access = "
    SELECT 
        r.reserva_id, r.creador_id, r.fecha, r.hora_inicio, r.hora_fin, r.estado, r.tipo_reserva, r.precio_total,
        c.cancha_id, c.nombre AS cancha_nombre, c.ubicacion, c.tipo
    FROM reservas r
    JOIN canchas c ON c.cancha_id=r.cancha_id
    LEFT JOIN participaciones p ON p.reserva_id=r.reserva_id AND p.jugador_id=?
    WHERE r.reserva_id=? AND (r.creador_id=? OR p.jugador_id IS NOT NULL)
    LIMIT 1
";
$st = $conn->prepare($sql_access);
$st->bind_param("iii", $userId, $reservaId, $userId);
$st->execute();
$reserva = $st->get_result()->fetch_assoc();
$st->close();

if (!$reserva) {
    echo "<div class='page-wrap'><h2>No tienes acceso a esta reserva.</h2></div>";
    include './../includes/footer.php';
    exit;
}

$esCreador = ((int)$reserva['creador_id'] === $userId);

/* Elegibilidad de cancelación (en MySQL para evitar problemas de timezone) */
$canQ = $conn->prepare("
    SELECT 
      (creador_id = ?) AS es_creador,
      estado,
      (CONCAT(fecha,' ',hora_inicio) > NOW()) AS en_futuro
    FROM reservas
    WHERE reserva_id=? LIMIT 1
");
$canQ->bind_param("ii", $userId, $reservaId);
$canQ->execute();
$can = $canQ->get_result()->fetch_assoc();
$canQ->close();
$puedeCancelar = $can && (int)$can['es_creador'] === 1 && $can['estado'] !== 'cancelada' && (int)$can['en_futuro'] === 1;

/* Jugadores */
$stJ = $conn->prepare("
    SELECT p.jugador_id, u.nombre, p.estado, p.es_creador
    FROM participaciones p
    JOIN usuarios u ON u.user_id=p.jugador_id
    WHERE p.reserva_id=?
    ORDER BY p.es_creador DESC, u.nombre ASC
");
$stJ->bind_param("i", $reservaId);
$stJ->execute();
$jugadores = $stJ->get_result()->fetch_all(MYSQLI_ASSOC);
$stJ->close();

/* Pagos */
$stP = $conn->prepare("
    SELECT pago_id, jugador_id, monto, metodo, estado, COALESCE(DATE_FORMAT(fecha_pago, '%Y-%m-%d %H:%i'), '') AS fecha_pago
    FROM pagos WHERE reserva_id=? ORDER BY pago_id ASC
");
$stP->bind_param("i", $reservaId);
$stP->execute();
$pagosRaw = $stP->get_result()->fetch_all(MYSQLI_ASSOC);
$stP->close();

/* Mi participación */
$stM = $conn->prepare("SELECT estado FROM participaciones WHERE reserva_id=? AND jugador_id=?");
$stM->bind_param("ii", $reservaId, $userId);
$stM->execute();
$miPart = $stM->get_result()->fetch_assoc();
$stM->close();

/* Pagos agregados por jugador (suma) */
$pagosPorJugador = [];
foreach ($pagosRaw as $p) {
    $jid = (int)$p['jugador_id'];
    if (!isset($pagosPorJugador[$jid])) {
        $pagosPorJugador[$jid] = [
            'monto'  => 0.0,
            'metodo' => $p['metodo'],
            'estado' => $p['estado'],
            'fecha'  => $p['fecha_pago']
        ];
    }
    $pagosPorJugador[$jid]['monto'] += (float)$p['monto'];
    if ($p['estado'] === 'pagado') {
        $pagosPorJugador[$jid]['estado'] = 'pagado';
    } elseif ($pagosPorJugador[$jid]['estado'] !== 'pagado' && $p['estado'] === 'pendiente') {
        $pagosPorJugador[$jid]['estado'] = 'pendiente';
    }
    if (!empty($p['fecha_pago'])) $pagosPorJugador[$jid]['fecha'] = $p['fecha_pago'];
    $pagosPorJugador[$jid]['metodo'] = $p['metodo'];
}

/* helpers */
function estado_badge_class(string $estado): string {
    $cls = 'badge';
    if ($estado === 'confirmada') $cls .= ' badge--confirmada';
    elseif ($estado === 'pendiente') $cls .= ' badge--pendiente';
    elseif ($estado === 'cancelada') $cls .= ' badge--cancelada';
    elseif ($estado === 'no_show') $cls .= ' badge--no_show';
    return $cls;
}
function money_fmt(float $n): string { return number_format($n, 2, ',', '.'); }
?>
<style>
table tbody tr:hover{ background:#f7fafb; }
.badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid rgba(0,0,0,.08)}
.badge--pendiente{background:#fff6e5;color:#8a5a00;border-color:#f5d49a}
.badge--confirmada{background:#e6fff5;color:#0d6b4d;border-color:#a5e4c8}
.badge--cancelada{background:#ffecec;color:#8a1f1f;border-color:#f1a7a7}
.badge--no_show{background:#f2f4f7;color:#5b5b5b;border-color:#d8dde3}

/* 2 columnas como historial */
.detail-2col{ display:grid; grid-template-columns: 1.3fr 0.7fr; gap:40px; }
@media (max-width:900px){ .detail-2col{ grid-template-columns:1fr; } }
.card-white h3{ margin:0 0 8px; }
.card-white .subtle{ color:#5a6b6c; font-size:14px; }

/* Botonera general centrada fuera de las tarjetas */
.cta-wrap{
  margin-top:18px; text-align:center; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;
}
.btn{ padding:10px 16px; border:none; background:#07566b; color:#fff; border-radius:10px; cursor:pointer; font-weight:700; }
.btn-outline{
  padding:10px 16px; border:1.5px solid #1bab9d; background:#fff; color:#1bab9d;
  border-radius:10px; cursor:pointer; font-weight:700; text-decoration:none; display:inline-block;
}
.btn-outline:hover{ background:rgba(27,171,157,.08); }
.btn-danger{
  padding:10px 16px; border:1.5px solid #c92a2a; background:#fff; color:#c92a2a;
  border-radius:10px; cursor:pointer; font-weight:700;
}
.btn-danger:hover{ background:#fff1f1; }
</style>

<div class="page-wrap">
    <h1 class="page-title" style="text-align:center;">Detalle de reserva #<?= (int)$reserva['reserva_id'] ?></h1>

    <div class="detail-2col">
        <!-- IZQUIERDA: Resumen -->
        <div>
            <h2 class="section-title">Resumen de la reserva</h2>
            <div class="card-white">
                <table>
                    <tbody>
                        <tr><td class="label-stat">Fecha</td><td class="value-stat"><?= htmlspecialchars($reserva['fecha']) ?></td></tr>
                        <tr><td class="label-stat">Horario</td><td class="value-stat"><?= htmlspecialchars(substr($reserva['hora_inicio'],0,5)) ?> - <?= htmlspecialchars(substr($reserva['hora_fin'],0,5)) ?></td></tr>
                        <tr>
                            <td class="label-stat">Estado</td>
                            <td class="value-stat">
                                <?php $cls = estado_badge_class((string)$reserva['estado']); ?>
                                <span class="<?= $cls ?>"><?= htmlspecialchars($reserva['estado']) ?></span>
                            </td>
                        </tr>
                        <tr><td class="label-stat">Tipo</td><td class="value-stat"><?= htmlspecialchars($reserva['tipo_reserva']) ?></td></tr>
                        <tr><td class="label-stat">Club / Ubicación</td><td class="value-stat"><?= nl2br(htmlspecialchars($reserva['ubicacion'])) ?></td></tr>
                        <tr><td class="label-stat">Cancha</td><td class="value-stat">#<?= (int)$reserva['cancha_id'] ?> — <?= htmlspecialchars($reserva['cancha_nombre']) ?></td></tr>
                        <tr><td class="label-stat">Tipo cancha</td><td class="value-stat"><?= htmlspecialchars($reserva['tipo']) ?></td></tr>
                        <tr><td class="label-stat">Precio total</td><td class="value-stat">$ <?= money_fmt((float)$reserva['precio_total']) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- DERECHA: Participantes y pagos -->
        <div>
            <h2 class="section-title">Participantes y pagos</h2>
            <div class="card-white">
                <table>
                    <thead>
                        <tr>
                            <th>Jugador</th>
                            <th>Rol</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rowsRendered = 0;

                        if (!empty($jugadores)) {
                            foreach ($jugadores as $j) {
                                $jid   = (int)$j['jugador_id'];
                                $pago  = $pagosPorJugador[$jid] ?? null;
                                $monto = $pago ? '$ '.money_fmt((float)$pago['monto']) : '—';
                                $met   = $pago['metodo'] ?? '—';
                                $est   = $pago['estado'] ?? '—';
                                $fec   = $pago['fecha']  ?? '—';
                                $rowsRendered++;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($j['nombre']) ?><?= ($jid===$userId) ? " (tú)" : "" ?></td>
                                    <td><?= ((int)$j['es_creador'] === 1) ? 'Creador' : 'Invitado' ?></td>
                                    <td><?= $monto ?></td>
                                    <td><?= htmlspecialchars($met) ?></td>
                                    <td><?= htmlspecialchars($est) ?></td>
                                    <td><?= htmlspecialchars($fec) ?></td>
                                </tr>
                                <?php
                            }
                        }

                        if ($rowsRendered === 0 && !empty($pagosRaw)) {
                            foreach ($pagosRaw as $p) {
                                $jid   = (int)$p['jugador_id'];
                                $nombre = 'User '.$jid;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($nombre) ?><?= ($jid===$userId) ? " (tú)" : "" ?></td>
                                    <td>—</td>
                                    <td>$ <?= money_fmt((float)$p['monto']) ?></td>
                                    <td><?= htmlspecialchars($p['metodo']) ?></td>
                                    <td><?= htmlspecialchars($p['estado']) ?></td>
                                    <td><?= htmlspecialchars($p['fecha_pago']) ?: '—' ?></td>
                                </tr>
                                <?php
                                $rowsRendered++;
                            }
                        }

                        if ($rowsRendered === 0) {
                            echo '<tr><td colspan="6" style="text-align:center;">Sin jugadores registrados</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>

                <?php if ($miPart && $miPart['estado'] === 'pendiente'): ?>
                    <form method="POST" class="actions">
                        <button class="btn" name="accion" value="aceptar" type="submit">Aceptar invitación</button>
                        <button class="btn-outline" name="accion" value="rechazar" type="submit">Rechazar invitación</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Botones centrados, fuera de las tarjetas -->
    <div class="cta-wrap">
        <?php if ($puedeCancelar): ?>
            <form method="POST" onsubmit="return confirm('¿Seguro que deseas cancelar esta reserva?');">
                <button class="btn-danger" name="accion" value="cancelar" type="submit">Cancelar reserva</button>
            </form>
        <?php endif; ?>
        <a class="btn-outline" href="/php/cliente/historial_estadisticas/historial_estadisticas.php">Volver a las reservas</a>
    </div>
</div>

<?php include './../includes/footer.php'; ?>
