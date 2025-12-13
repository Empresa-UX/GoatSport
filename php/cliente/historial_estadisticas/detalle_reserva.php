<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\historial_estadisticas\detalle_reserva.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

$userId    = (int)$_SESSION['usuario_id'];
$reservaId = isset($_GET['reserva_id']) ? (int)$_GET['reserva_id'] : 0;
if ($reservaId <= 0) { header("Location: /php/cliente/historial_estadisticas/historial_estadisticas.php"); exit; }

/* ===== Helpers ===== */
function money_fmt(float $n): string { return number_format($n, 2, ',', '.'); }
function fmt_dia_mes(string $ymd): string {
    $ts = strtotime($ymd);
    return $ts ? date('d/m', $ts) : $ymd;
}
function label_estado_reserva(string $e): string {
    $e = strtolower($e);
    if ($e==='pendiente') return 'Pendiente';
    if ($e==='confirmada') return 'Confirmada';
    if ($e==='cancelada') return 'Cancelada';
    if ($e==='no_show') return 'No show';
    return ucfirst($e);
}
function label_tipo_reserva(string $t): string {
    $t = strtolower($t);
    if ($t==='equipo') return 'Equipo';
    if ($t==='individual') return 'Individual';
    return ucfirst($t);
}
function label_tipo_cancha(string $t): string {
    $t = strtolower($t);
    if ($t==='clasica' || $t==='clásica') return 'Clásica';
    if ($t==='panoramica' || $t==='panorámica') return 'Panorámica';
    if ($t==='cubierta') return 'Cubierta';
    return ucfirst($t);
}
function label_metodo(?string $m): string {
    if ($m === null || $m === '') return '—';
    $m = strtolower($m);
    if ($m==='club') return 'Presencial';
    if ($m==='tarjeta') return 'Tarjeta';
    if ($m==='mercado_pago') return 'Mercado Pago';
    return ucfirst($m);
}
function label_condicion(?string $s): string {
    if ($s === null || $s === '') return '—';
    $map = ['pagado'=>'Pagado','pendiente'=>'Pendiente','cancelado'=>'Cancelado','parcial'=>'Parcial','sin registro'=>'Sin registro'];
    $s = strtolower($s);
    return $map[$s] ?? ucfirst($s);
}
function fmt_ddmm_from_dt(?string $dt): string {
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date('d/m', $ts) : '—';
}

/* ===== Acciones POST ===== */
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
        $u = $conn->prepare("
            UPDATE reservas SET estado='cancelada'
            WHERE reserva_id=? AND creador_id=? AND estado<>'cancelada'
              AND CONCAT(fecha,' ',hora_inicio) > NOW() LIMIT 1
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

/* ===== Acceso + datos =====
 * Precio mostrado: fallback a c.precio si r.precio_total=0
 */
$sql_access = "
    SELECT 
        r.reserva_id, r.creador_id, r.fecha, r.hora_inicio, r.hora_fin, r.estado, r.tipo_reserva,
        COALESCE(NULLIF(r.precio_total, 0.00), c.precio) AS precio_mostrar,
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

/* Cancelación permitida */
$canQ = $conn->prepare("
    SELECT (creador_id = ?) AS es_creador, estado, (CONCAT(fecha,' ',hora_inicio) > NOW()) AS en_futuro
    FROM reservas WHERE reserva_id=? LIMIT 1
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

/* Agregación por jugador */
$pagosPorJugador = [];
foreach ($pagosRaw as $p) {
    $jid = (int)$p['jugador_id'];
    if (!isset($pagosPorJugador[$jid])) {
        $pagosPorJugador[$jid] = ['monto'=>0.0,'metodo'=>$p['metodo'],'estado'=>$p['estado'],'fecha'=>$p['fecha_pago']];
    }
    $pagosPorJugador[$jid]['monto'] += (float)$p['monto'];
    if ($p['estado'] === 'pagado') $pagosPorJugador[$jid]['estado'] = 'pagado';
    elseif ($pagosPorJugador[$jid]['estado'] !== 'pagado' && $p['estado'] === 'pendiente') $pagosPorJugador[$jid]['estado'] = 'pendiente';
    if (!empty($p['fecha_pago'])) $pagosPorJugador[$jid]['fecha'] = $p['fecha_pago'];
    $pagosPorJugador[$jid]['metodo'] = $p['metodo'];
}
?>
<style>
table tbody tr:hover{ background:#f7fafb; }
.detail-2col{ display:grid; grid-template-columns: 1.3fr 0.7fr; gap:40px; }
@media (max-width:900px){ .detail-2col{ grid-template-columns:1fr; } }
.card-white h3{ margin:0 0 8px; }
.card-white .subtle{ color:#5a6b6c; font-size:14px; }

.cta-wrap{ margin-top:18px; text-align:center; display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
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
            <tr><td class="label-stat">Fecha</td><td class="value-stat"><?= fmt_dia_mes($reserva['fecha']) ?></td></tr>
            <tr><td class="label-stat">Horario</td><td class="value-stat"><?= htmlspecialchars(substr($reserva['hora_inicio'],0,5)) ?> - <?= htmlspecialchars(substr($reserva['hora_fin'],0,5)) ?></td></tr>
            <tr><td class="label-stat">Estado</td><td class="value-stat"><?= htmlspecialchars(label_estado_reserva($reserva['estado'])) ?></td></tr>
            <tr><td class="label-stat">Tipo</td><td class="value-stat"><?= htmlspecialchars(label_tipo_reserva($reserva['tipo_reserva'])) ?></td></tr>
            <tr><td class="label-stat">Ubicación</td><td class="value-stat"><?= nl2br(htmlspecialchars($reserva['ubicacion'])) ?></td></tr>
            <tr><td class="label-stat">Cancha</td><td class="value-stat"><?= htmlspecialchars($reserva['cancha_nombre']) ?></td></tr>
            <tr><td class="label-stat">Tipo de cancha</td><td class="value-stat"><?= htmlspecialchars(label_tipo_cancha($reserva['tipo'])) ?></td></tr>
            <tr><td class="label-stat">Precio total</td><td class="value-stat">$ <?= money_fmt((float)$reserva['precio_mostrar']) ?></td></tr>
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
                  $met   = label_metodo($pago['metodo'] ?? null);
                  $est   = label_condicion($pago['estado'] ?? null);
                  $fec   = fmt_ddmm_from_dt($pago['fecha'] ?? null);
                  $rowsRendered++;
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($j['nombre']) ?><?= ($jid===$userId) ? " (tú)" : "" ?></td>
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
                    <td>$ <?= money_fmt((float)$p['monto']) ?></td>
                    <td><?= htmlspecialchars(label_metodo($p['metodo'])) ?></td>
                    <td><?= htmlspecialchars(label_condicion($p['estado'])) ?></td>
                    <td><?= htmlspecialchars(fmt_ddmm_from_dt($p['fecha_pago'])) ?></td>
                  </tr>
                  <?php
                  $rowsRendered++;
              }
          }
          if ($rowsRendered === 0) {
              echo '<tr><td colspan="5" style="text-align:center;">Sin jugadores registrados</td></tr>';
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
