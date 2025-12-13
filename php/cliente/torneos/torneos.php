<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\torneos\torneos.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if ($_SESSION['rol'] !== 'cliente') { header("Location: /php/login.php"); exit; }

$userId   = (int)$_SESSION['usuario_id'];
$pageSize = 4;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $pageSize;

/* Vista: activos vs historial */
$mostrarHistorial = isset($_GET['historial']) && $_GET['historial'] === '1';

/* Helpers (fechas y etiquetas) */
function fmt_md(string $d): string {
    if (!$d) return '—';
    [$y,$m,$day] = explode('-', $d);
    $mes = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][(int)$m] ?? '';
    return (int)$day.' '.$mes;
}
function tipo_label(?string $t): string {
    $t = strtolower((string)$t);
    return $t==='equipo' ? 'Equipo' : ($t==='individual' ? 'Individual' : ucfirst($t));
}

/* COUNT */
$sqlCount = "
  SELECT COUNT(*) AS total
  FROM torneos t
  WHERE ".($mostrarHistorial ? "t.fecha_inicio < CURDATE()" : "t.fecha_inicio >= CURDATE() AND t.estado IN ('abierto','cerrado')")."
";
$c = $conn->prepare($sqlCount);
$c->execute();
$total = (int)$c->get_result()->fetch_assoc()['total'];
$c->close();
$totalPages = max(1, (int)ceil($total / $pageSize));

/* LISTA (datos necesarios para acciones) */
$sql = "
  SELECT
    t.torneo_id, t.nombre, t.fecha_inicio, t.fecha_fin, t.estado, t.tipo, t.capacidad,
    COALESCE(prov.nombre,'—') AS club,
    " . (!$mostrarHistorial ? "(SELECT COUNT(*) FROM participaciones p WHERE p.torneo_id=t.torneo_id AND p.estado='aceptada') AS inscriptos," : "0 AS inscriptos,") . "
    DATEDIFF(t.fecha_inicio, CURDATE()) AS comienza_en
  FROM torneos t
  LEFT JOIN usuarios prov ON prov.user_id = t.proveedor_id
  WHERE ".($mostrarHistorial ? "t.fecha_inicio < CURDATE()" : "t.fecha_inicio >= CURDATE() AND t.estado IN ('abierto','cerrado')")."
  ORDER BY ".($mostrarHistorial ? "t.fecha_inicio DESC" : "t.estado='abierto' DESC, t.fecha_inicio ASC").", t.torneo_id DESC
  LIMIT ? OFFSET ?
";
$st = $conn->prepare($sql);
$st->bind_param("ii", $pageSize, $offset);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

/* Mis inscripciones (solo activos) */
$joined = [];
if (!$mostrarHistorial && !empty($rows)) {
    $ids = array_map('intval', array_column($rows, 'torneo_id'));
    if ($ids) {
        $inList = implode(',', $ids);
        $sqlJ = "SELECT torneo_id FROM participaciones WHERE jugador_id=? AND torneo_id IN ($inList)";
        $stJ  = $conn->prepare($sqlJ);
        $stJ->bind_param('i', $userId);
        $stJ->execute();
        $rJ = $stJ->get_result();
        while ($r = $rJ->fetch_assoc()) { $joined[(int)$r['torneo_id']] = true; }
        $stJ->close();
    }
}

/* mensajes */
$okMsg  = isset($_GET['ok'])  ? trim($_GET['ok'])  : '';
$errMsg = isset($_GET['err']) ? trim($_GET['err']) : '';
?>
<style>
/* ====== Estilo consistente con "Reservas" ====== */
.page-wrap{ padding:24px 16px 40px; }
.card-white{ max-width:1280px; margin:0 auto 24px auto; }
.table-wrap{ width:100%; overflow-x:auto; }

/* Toolbar (mismo patrón: título izq + botón a la derecha) */
.toolbar{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px; flex-wrap:wrap; }
.toolbar-left{ display:flex; align-items:center; gap:10px; flex:1; min-width:0; }
.push-right{ margin-left:auto; }
.card-white .section-title{ font-size:26px; font-weight:700; color:var(--text-dark); margin:0; }

    .btn-add {
      display:inline-flex; align-items:center; gap:8px; padding:8px 12px;
      text-decoration:none; font-weight:600; font-size:14px; transition:filter .15s ease, transform .03s ease; white-space:nowrap;
    }
    .btn-add:hover { background:#139488; }

/* Tabla: texto alineado a la IZQUIERDA como en Reservas */
.table-fixed{ table-layout:auto; width:100%; border-collapse:separate; border-spacing:0; }
.table-fixed th,.table-fixed td{
  text-align:left; padding:12px 14px; vertical-align:middle;
  white-space:normal; overflow:visible; text-overflow:unset;
}
table thead th{ color:#2a4e51; border-bottom:2px solid #e1ecec; font-weight:700; }
table tbody td{ border-bottom:1px solid #f0f5f5; }
table tbody tr:hover{ background:#f7fafb; }
.row-link{ cursor:pointer; }
.row-link:focus{ outline:2px solid #1bab9d; outline-offset:2px; }

/* Acciones */
.actions{ display:flex; gap:8px; align-items:center; }
.btn-sm{
  padding:8px 12px; border:1px solid #1bab9d; color:#1bab9d; background:#fff;
  border-radius:10px; text-decoration:none; cursor:pointer; font-weight:700;
}
.btn-sm:hover{ background:rgba(27,171,157,.08); }
.btn-sm[disabled]{ opacity:.45; cursor:not-allowed; }

/* Paginación (igual que Reservas) */
.pagination{ display:flex; gap:8px; margin-top:14px; align-items:center; flex-wrap:wrap; justify-content:center; }
.pagination a,.pagination span{
  padding:8px 12px; border:1px solid #e1ecec; border-radius:999px; text-decoration:none;
  font-size:14px; line-height:1; color:#2a4e51; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.pagination .active{ background:#1bab9d; color:#fff; border-color:transparent; }
.pagination .disabled{ color:#9ab3b5; background:#f3f7f7; }

/* Anchos mínimos para que “respire” como en Reservas */
.col-nombre{ min-width:220px; }
.col-club{ min-width:200px; }
.col-inicio,.col-fin{ min-width:110px; }
.col-tipo{ min-width:120px; }
.col-cap{ min-width:110px; }
.col-acciones{ min-width:160px; }
</style>

<div class="page-wrap">
  <h1 class="page-title">Torneos</h1>

  <div class="card-white">
    <div class="toolbar">
      <div class="toolbar-left">
        <h2 class="section-title"><?= $mostrarHistorial ? 'Historial de torneos' : 'Torneos activos' ?></h2>
        <a class="btn-add push-right" href="<?= $mostrarHistorial ? '?' : '?historial=1' ?>">
          <?= $mostrarHistorial ? 'Ver torneos activos' : 'Ver historial de torneos' ?>
        </a>
      </div>
    </div>

    <div class="table-wrap">
      <table class="table-fixed">
        <thead>
          <tr>
            <th class="col-nombre">Nombre</th>
            <th class="col-club">Club</th>
            <th class="col-inicio">Fecha inicio</th>
            <th class="col-fin">Fecha fin</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-cap">Capacidad</th>
            <?php if (!$mostrarHistorial): ?><th class="col-acciones">Acciones</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php if ($rows): foreach ($rows as $t):
            $href = "/php/cliente/torneos/detalle_torneo.php?torneo_id=".(int)$t['torneo_id'];
            $capacidad = (int)$t['capacidad'];
            $insc      = (int)($t['inscriptos'] ?? 0);
            $rest      = max(0, $capacidad - $insc);
            $comenzo   = (int)$t['comienza_en'] < 0;
            $noAbierto = ($t['estado'] !== 'abierto');
            $full      = ($capacidad > 0 && $rest <= 0);
            $isJoined  = !$mostrarHistorial && !empty($joined[(int)$t['torneo_id']]);
            $canJoin   = (!$mostrarHistorial) && !$isJoined && !$noAbierto && !$comenzo && !$full;
        ?>
          <tr class="row-link" tabindex="0" data-href="<?= htmlspecialchars($href) ?>">
            <td class="col-nombre"><?= htmlspecialchars($t['nombre']) ?></td>
            <td class="col-club"><?= htmlspecialchars($t['club']) ?></td>
            <td class="col-inicio"><?= fmt_md($t['fecha_inicio']) ?></td>
            <td class="col-fin"><?= fmt_md($t['fecha_fin']) ?></td>
            <td class="col-tipo"><?= tipo_label($t['tipo']) ?></td>
            <td class="col-cap"><?= $capacidad > 0 ? (int)$capacidad : '—' ?></td>

            <?php if (!$mostrarHistorial): ?>
              <td class="actions col-acciones" onclick="event.stopPropagation();">
                <?php if ($isJoined): ?>
                  <form method="post" action="/php/cliente/torneos/salirTorneo.php" onsubmit="return confirm('¿Salir del torneo?');">
                    <input type="hidden" name="torneo_id" value="<?= (int)$t['torneo_id'] ?>">
                    <button type="submit" class="btn-sm">Salir</button>
                  </form>
                <?php elseif ($canJoin): ?>
                  <form method="post" action="/php/cliente/torneos/unirseTorneo.php">
                    <input type="hidden" name="torneo_id" value="<?= (int)$t['torneo_id'] ?>">
                    <input type="hidden" name="return" value="/php/cliente/torneos/torneos.php">
                    <button type="submit" class="btn-sm">Unirme</button>
                  </form>
                <?php else: ?>
                  <button class="btn-sm" disabled>
                    <?php
                      if ($noAbierto) echo 'Cerrado';
                      elseif ($comenzo) echo 'Iniciado';
                      elseif ($full) echo 'Sin cupo';
                      else echo '—';
                    ?>
                  </button>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; else: ?>
          <tr>
            <td colspan="<?= $mostrarHistorial ? '6' : '7' ?>" style="text-align:center;">
              <?= $mostrarHistorial ? 'No hay torneos en el historial' : 'No hay torneos activos disponibles' ?>
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php 
          $prev = max(1, $page - 1);
          $next = min($totalPages, $page + 1);
          $base = $mostrarHistorial ? '?historial=1&' : '?';
        ?>
        <?= $page>1 ? '<a href="'.$base.'page='.$prev.'">« Anterior</a>' : '<span class="disabled">« Anterior</span>' ?>
        <?php for($p = 1; $p <= $totalPages; $p++): ?>
          <?= $p===$page ? '<span class="active">'.$p.'</span>' : '<a href="'.$base.'page='.$p.'">'.$p.'</a>' ?>
        <?php endfor; ?>
        <?= $page<$totalPages ? '<a href="'.$base.'page='.$next.'">Siguiente »</a>' : '<span class="disabled">Siguiente »</span>' ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Fila clickeable (ignora botones/form)
document.querySelectorAll('.row-link').forEach(function(row){
  row.addEventListener('click', function(e){
    if (e.target.closest('form') || e.target.closest('a') || e.target.closest('button')) return;
    window.location.href = this.dataset.href;
  });
  row.addEventListener('keydown', function(e){ if(e.key==='Enter'){ window.location.href = this.dataset.href; }});
});

// alert() por ok/err y limpiar query (opcional)
<?php
$cleaner = "history.replaceState({}, '', window.location.pathname + window.location.search.replace(/(\\?|&)PLACE=[^&]*/, '').replace(/\\?&/,'?').replace(/\\?$/,''));";
if ($okMsg): ?> alert(<?= json_encode($okMsg) ?>); <?= str_replace('PLACE','ok',$cleaner) ?> <?php endif; ?>
<?php if ($errMsg): ?> alert(<?= json_encode($errMsg) ?>); <?= str_replace('PLACE','err',$cleaner) ?> <?php endif; ?>
</script>

<?php include './../includes/footer.php'; ?>
