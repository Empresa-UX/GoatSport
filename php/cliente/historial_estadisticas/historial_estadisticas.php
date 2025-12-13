<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\historial_estadisticas\historial_estadisticas.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

$userId     = (int)$_SESSION['usuario_id'];
$view       = isset($_GET['view']) ? (string)$_GET['view'] : '';
$isHist     = ($view === 'historial');
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize   = 6; // no tocar
$offset     = ($page - 1) * $pageSize;

/* ===== Helpers ===== */
function money_fmt(float $n): string { return number_format($n, 2, ',', '.'); }
function fmt_dia_mes(string $ymd): string {
    $ts = strtotime($ymd);
    return $ts ? date('d/m', $ts) : $ymd;
}
function label_metodo(?string $m): string {
    if ($m === null || $m === '—' || $m === '') return '—';
    $m = strtolower($m);
    if ($m === 'club') return 'Presencial';
    if ($m === 'tarjeta') return 'Tarjeta';
    if ($m === 'mercado_pago') return 'Mercado Pago';
    return ucfirst($m);
}
function label_condicion(?string $s): string {
    if ($s === null || $s === '') return '—';
    $map = ['pagado'=>'Pagado','pendiente'=>'Pendiente','cancelado'=>'Cancelado','parcial'=>'Parcial','sin registro'=>'Sin registro'];
    $s = strtolower($s);
    return $map[$s] ?? ucfirst($s);
}

/* ===== Subquery pagos por jugador (estado agregado + método reciente) ===== */
$paySub = "
    SELECT 
        reserva_id,
        SUM(monto)                                          AS monto_total,
        MAX(CASE WHEN estado='pagado' THEN 1 ELSE 0 END)    AS any_pagado,
        MAX(CASE WHEN estado='pendiente' THEN 1 ELSE 0 END) AS any_pendiente,
        MAX(CASE WHEN estado='cancelado' THEN 1 ELSE 0 END) AS any_cancelado,
        SUBSTRING_INDEX(
            TRIM(BOTH ',' FROM GROUP_CONCAT(metodo ORDER BY COALESCE(fecha_pago, '0000-00-00 00:00:00') DESC SEPARATOR ',')),
            ',', 1
        ) AS metodo_ult
    FROM pagos
    WHERE jugador_id = ?
    GROUP BY reserva_id
";

/* Estado de pago derivado */
$estadoPagoCase = "
    CASE 
        WHEN pay.any_pagado=1 AND pay.any_pendiente=0 THEN 'pagado'
        WHEN pay.any_pagado=1 AND pay.any_pendiente=1 THEN 'parcial'
        WHEN pay.any_pendiente=1 AND pay.any_pagado=0 THEN 'pendiente'
        WHEN pay.any_cancelado=1 AND pay.any_pagado=0 AND pay.any_pendiente=0 THEN 'cancelado'
        ELSE 'sin registro'
    END
";

/* ===== COUNT según vista ===== */
$sql_count = "
    SELECT COUNT(*) AS total FROM (
        SELECT DISTINCT r.reserva_id
        FROM reservas r
        LEFT JOIN participaciones p
            ON p.reserva_id=r.reserva_id AND p.jugador_id=? AND p.estado='aceptada'
        WHERE (r.creador_id=? OR p.jugador_id IS NOT NULL)
          AND CONCAT(r.fecha,' ',r.hora_inicio) ".($isHist ? "<=" : ">")." NOW()
    ) t
";
$stC = $conn->prepare($sql_count);
$stC->bind_param("ii", $userId, $userId);
$stC->execute();
$totalRows = (int)$stC->get_result()->fetch_assoc()['total'];
$stC->close();
$totalPages = max(1, (int)ceil($totalRows / $pageSize));

/* ===== SELECT página =====
 * Precio mostrado: si r.precio_total=0 usamos c.precio (fallback).
 */
$sql_rows = "
    SELECT 
        r.reserva_id, r.fecha, r.hora_inicio, r.hora_fin, r.estado, r.tipo_reserva,
        COALESCE(NULLIF(r.precio_total, 0.00), c.precio) AS precio_mostrar,
        c.cancha_id, c.nombre AS cancha_nombre, c.ubicacion, c.proveedor_id,
        COALESCE(pd.nombre_club, CONCAT('Club #', c.proveedor_id)) AS club_nombre,
        $estadoPagoCase AS estado_pago,
        COALESCE(pay.metodo_ult, '—') AS metodo_pago
    FROM reservas r
    JOIN canchas c                      ON c.cancha_id = r.cancha_id
    LEFT JOIN proveedores_detalle pd    ON pd.proveedor_id = c.proveedor_id
    LEFT JOIN participaciones p
        ON p.reserva_id = r.reserva_id AND p.jugador_id = ? AND p.estado='aceptada'
    LEFT JOIN ( $paySub ) pay           ON pay.reserva_id = r.reserva_id
    WHERE (r.creador_id = ? OR p.jugador_id IS NOT NULL)
      AND CONCAT(r.fecha,' ',r.hora_inicio) ".($isHist ? "<=" : ">")." NOW()
    GROUP BY r.reserva_id
    ORDER BY r.fecha ".($isHist ? "DESC" : "ASC").", r.hora_inicio ".($isHist ? "DESC" : "ASC")."
    LIMIT ? OFFSET ?
";
$stR = $conn->prepare($sql_rows);
$stR->bind_param("iiiii", $userId, $userId, $userId, $pageSize, $offset);
$stR->execute();
$rows = $stR->get_result()->fetch_all(MYSQLI_ASSOC);
$stR->close();
?>
<style>
/* Mantengo tu layout/paginación (no toco tus anchos si ya los editaste) */
.page-wrap{ padding: 24px 16px 40px; }
.card-white{ max-width:1280px; margin: 0 auto 24px auto; }
.toolbar{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px; flex-wrap:wrap; }
.toolbar-left{ display:flex; align-items:center; gap:10px; }
/* Añade/ajusta estas reglas a tu CSS existente */
.toolbar-left{
  display:flex; align-items:center; gap:10px;
  flex: 1;               /* ocupa todo el ancho disponible */
  min-width: 0;          /* evita empujes raros */
}
.push-right{
  margin-left:auto;      /* manda el botón a la derecha */
}

.card-white .section-title{ font-size:26px; font-weight:700; color:var(--text-dark); margin:0; }
    .btn-add {
      display:inline-flex; align-items:center; gap:8px; padding:8px 12px;
      text-decoration:none; font-weight:600; font-size:14px; transition:filter .15s ease, transform .03s ease; white-space:nowrap;
    }
    .btn-add:hover { background:#139488; }
.table-wrap{ width:100%; overflow-x:auto; }
table tbody tr:hover{ background:#f7fafb; }
.row-link{cursor:pointer}
.row-link:focus{outline:2px solid #1bab9d; outline-offset:2px}
.pagination{ display:flex; gap:8px; margin-top:14px; align-items:center; flex-wrap:wrap; }
.pagination a,.pagination span{ padding:8px 12px; border:1px solid #e1ecec; border-radius:999px; text-decoration:none; font-size:14px; line-height:1; color:#2a4e51; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.pagination .active{ background:#1bab9d; color:#fff; border-color:transparent; }
.pagination .disabled{ color:#9ab3b5; background:#f3f7f7; }
</style>

<div class="page-wrap">
  <h1 class="page-title">Gestión de reservas</h1>

  <div class="card-white">
    <!-- Cambia solo esta parte del toolbar -->
    <div class="toolbar">
    <div class="toolbar-left">
        <h2 class="section-title"><?= $isHist ? 'Historial de mis reservas' : 'Mis reservas' ?></h2>
        <?php if ($isHist): ?>
        <a class="btn-add push-right" href="./historial_estadisticas.php">Ver próximas reservas</a>
        <?php else: ?>
        <a class="btn-add push-right" href="?view=historial">Ver historial de reservas</a>
        <?php endif; ?>
    </div>
    </div>


    <div class="table-wrap">
      <table class="table-fixed">
        <thead>
          <tr>
            <th class="col-fecha">Fecha</th>
            <th class="col-hora">Horario</th>
            <th class="col-club">Club</th>
            <th class="col-cancha">Cancha</th>
            <th class="col-metodo">Abono</th>
            <th class="col-estadop">Condición</th>
            <th class="col-precio">Precio</th>
            <?php /* sin Tipo */ ?>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $r):
              $href       = "/php/cliente/historial_estadisticas/detalle_reserva.php?reserva_id=".(int)$r['reserva_id'];
              $precio     = (float)$r['precio_mostrar'];
              $metodo     = label_metodo($r['metodo_pago']);      // AHORA SIEMPRE MAPEADO
              $estadoPago = label_condicion($r['estado_pago']);   // AHORA SIEMPRE MAPEADO
            ?>
              <tr class="row-link" tabindex="0" data-href="<?= htmlspecialchars($href) ?>">
                <td class="col-fecha" title="<?= htmlspecialchars($r['fecha']) ?>"><?= fmt_dia_mes($r['fecha']) ?></td>
                <td class="col-hora" title="<?= htmlspecialchars($r['hora_inicio'].' - '.$r['hora_fin']) ?>">
                  <?= htmlspecialchars(substr($r['hora_inicio'],0,5)) ?> - <?= htmlspecialchars(substr($r['hora_fin'],0,5)) ?>
                </td>
                <td class="col-club" title="<?= htmlspecialchars($r['club_nombre']) ?>"><?= htmlspecialchars($r['club_nombre']) ?></td>
                <td class="col-cancha" title="<?= htmlspecialchars($r['cancha_nombre']) ?>"><?= htmlspecialchars($r['cancha_nombre']) ?></td>
                <td class="col-metodo" title="<?= htmlspecialchars($metodo) ?>"><?= htmlspecialchars($metodo) ?></td>
                <td class="col-estadop" title="<?= htmlspecialchars($estadoPago) ?>"><?= htmlspecialchars($estadoPago) ?></td>
                <td class="col-precio" title="<?= '$ '.money_fmt($precio) ?>">$ <?= money_fmt($precio) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align:center;">
                <?= $isHist ? 'Sin historial' : 'No tienes reservas futuras' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php
          $prev = max(1, $page-1);
          $next = min($totalPages, $page+1);
          $base = $isHist ? '?view=historial&' : '?';
        ?>
        <?= $page>1 ? '<a href="'.$base.'page='.$prev.'">« Anterior</a>' : '<span class="disabled">« Anterior</span>' ?>
        <?php for ($p=1; $p <= $totalPages; $p++): ?>
          <?= $p===$page ? '<span class="active">'.$p.'</span>' : '<a href="'.$base.'page='.$p.'">'.$p.'</a>' ?>
        <?php endfor; ?>
        <?= $page<$totalPages ? '<a href="'.$base.'page='.$next.'">Siguiente »</a>' : '<span class="disabled">Siguiente »</span>' ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.querySelectorAll('.row-link').forEach(function(row){
  row.addEventListener('click', function(){ window.location.href = this.dataset.href; });
  row.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ window.location.href = this.dataset.href; }});
});
</script>

<?php include './../includes/footer.php'; ?>
