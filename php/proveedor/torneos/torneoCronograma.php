<?php
/* =========================================================================
 * Cronograma de partidos (Proveedor)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') { header('Location: ../login.php'); exit; }

$proveedor_id = (int)$_SESSION['usuario_id'];
$torneo_id = (int)($_GET['torneo_id'] ?? 0);
if ($torneo_id<=0) { header('Location: torneos.php'); exit; }

/* Verificación de propiedad */
$ts = $conn->prepare("SELECT t.*, COALESCE(pd.nombre_club, pu.nombre) AS club FROM torneos t LEFT JOIN usuarios pu ON pu.user_id=t.proveedor_id LEFT JOIN proveedores_detalle pd ON pd.proveedor_id=t.proveedor_id WHERE t.torneo_id=? AND t.proveedor_id=?");
$ts->bind_param("ii",$torneo_id,$proveedor_id);
$ts->execute(); $tres=$ts->get_result(); $torneo=$tres->fetch_assoc(); $ts->close();
if (!$torneo) { header('Location: torneos.php'); exit; }

/* Partidos + reservas + jugadores + cancha */
$sql = "
  SELECT 
    p.partido_id, p.torneo_id, p.jugador1_id, p.jugador2_id, p.fecha, p.resultado, p.ganador_id,
    p.ronda, p.idx_ronda, p.next_partido_id, p.next_pos,
    r.reserva_id, r.fecha AS fecha_res, r.hora_inicio, r.hora_fin, r.cancha_id,
    c.nombre AS cancha_nombre,
    u1.nombre AS j1_nombre, u2.nombre AS j2_nombre
  FROM partidos p
  INNER JOIN reservas r ON r.reserva_id = p.reserva_id
  INNER JOIN canchas  c ON c.cancha_id = r.cancha_id
  LEFT  JOIN usuarios u1 ON u1.user_id = p.jugador1_id
  LEFT  JOIN usuarios u2 ON u2.user_id = p.jugador2_id
  WHERE p.torneo_id = ?
  ORDER BY r.fecha ASC, r.hora_inicio ASC, p.ronda ASC, p.idx_ronda ASC, p.partido_id ASC
";

$st = $conn->prepare($sql);
$st->bind_param("i",$torneo_id);
$st->execute(); $res=$st->get_result();
$rows = $res? $res->fetch_all(MYSQLI_ASSOC):[];
$st->close();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ddmm($ymd){ $t=strtotime($ymd); return $t?date('d/m',$t):'—'; }
?>
<div class="section">
  <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <h2 style="margin:0;">Cronograma — <?= h($torneo['nombre']) ?></h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a class="btn-add" href="torneoParticipantes.php?torneo_id=<?= (int)$torneo_id ?>">Ver bracket</a>
      <a class="btn-add" href="torneos.php">Volver</a>
    </div>
  </div>

  <style>
    :root{
      --col-fecha:90px; --col-hora:110px; --col-cancha:160px; --col-jug:200px; --col-est:120px; --col-acc:140px;
    }
    table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border-radius:12px;overflow:hidden;table-layout:fixed}
    thead th{position:sticky;top:0;background:#f8fafc;z-index:1;text-align:left;font-weight:700;padding:10px 12px;font-size:13px;color:#334155;border-bottom:1px solid #e5e7eb}
    tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top}
    .col-fecha{width:var(--col-fecha)} .col-hora{width:var(--col-hora)} .col-cancha{width:var(--col-cancha)}
    .col-jug{width:var(--col-jug)} .col-est{width:var(--col-est); text-align:center} .col-acc{width:var(--col-acc)}
    .pill{display:inline-block;padding:4px 9px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid transparent;white-space:nowrap}
    .st-pend{background:#fff7e6;border-color:#ffe1b5;color:#92400e}
    .st-played{background:#e6f7f4;border-color:#c8efe8;color:#0f766e}
    .btn-add{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;text-decoration:none;font-weight:700;font-size:14px;border-radius:10px;border:1px solid #bfd7ff;background:#e0ecff;color:#1e40af}
    .btn-action{appearance:none;border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-weight:700;background:#e6f7f4;border:1px solid #c8efe8;color:#0f766e}
    .mute{color:#64748b}
  </style>

  <table>
    <thead>
      <tr>
        <th class="col-fecha">Fecha</th>
        <th class="col-hora">Hora</th>
        <th class="col-cancha">Cancha</th>
        <th class="col-jug">Jugadores</th>
        <th class="col-est">Estado</th>
        <th class="col-acc">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" style="text-align:center;">Aún no hay partidos programados.</td></tr>
      <?php else: foreach($rows as $r):
        $hora = substr($r['hora_inicio'],0,5).'–'.substr($r['hora_fin'],0,5);
        $estado = $r['ganador_id'] ? 'Jugado' : 'Pendiente';
        $stCls = $r['ganador_id'] ? 'st-played' : 'st-pend';
        $js = trim(($r['j1_nombre']?:'—').' vs '.($r['j2_nombre']?:'—'));
        $puedeCargar = $r['jugador1_id'] && $r['jugador2_id'] && !$r['ganador_id'];
      ?>
      <tr>
        <td class="col-fecha"><?= h(ddmm($r['fecha_res'])) ?></td>
        <td class="col-hora"><?= h($hora) ?></td>
        <td class="col-cancha"><?= h($r['cancha_nombre']) ?></td>
        <td class="col-jug"><?= h($js) ?><?= $r['resultado'] ? " <span class='mute'>( {$r['resultado']} )</span>":"" ?></td>
        <td class="col-est"><span class="pill <?= $stCls ?>"><?= h($estado) ?></span></td>
        <td class="col-acc">
          <?php if ($puedeCargar): ?>
            <button class="btn-action" onclick="location.href='partidoResultado.php?partido_id=<?= (int)$r['partido_id'] ?>'">Cargar resultado</button>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
