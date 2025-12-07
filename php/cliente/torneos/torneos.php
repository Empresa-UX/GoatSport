<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\torneos\torneos.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if ($_SESSION['rol'] !== 'cliente') { header("Location: /php/login.php"); exit; }

$userId   = (int)$_SESSION['usuario_id'];
$q        = isset($_GET['q']) ? trim($_GET['q']) : '';
$pageSize = 4;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $pageSize;

/* helpers */
function fmt_md(string $d): string {
    if (!$d) return '—';
    [$y,$m,$day] = explode('-', $d);
    $mes = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][(int)$m] ?? '';
    return (int)$day.' '.$mes;
}
function comienza_label($n){
  if ($n === null) return '—';
  $n = (int)$n;
  if ($n > 1) return "En $n días";
  if ($n === 1) return "Mañana";
  if ($n === 0) return "Hoy";
  if ($n === -1) return "Ayer";
  return "Hace ".abs($n)." días";
}

/* Count */
$sqlCount = "
  SELECT COUNT(*) AS total
  FROM torneos t
  LEFT JOIN usuarios prov ON prov.user_id = t.proveedor_id
  WHERE (? = '' OR t.nombre LIKE CONCAT('%', ?, '%') OR COALESCE(prov.nombre,'') LIKE CONCAT('%', ?, '%'))
";
$c = $conn->prepare($sqlCount);
$c->bind_param("sss", $q, $q, $q);
$c->execute();
$total = (int)$c->get_result()->fetch_assoc()['total'];
$c->close();
$totalPages = max(1, (int)ceil($total / $pageSize));

/* List */
$sql = "
  SELECT
    t.torneo_id, t.nombre, t.fecha_inicio, t.fecha_fin, t.estado,
    t.proveedor_id, COALESCE(prov.nombre,'—') AS club,
    (SELECT COUNT(*) FROM participaciones p WHERE p.torneo_id=t.torneo_id AND p.estado='aceptada') AS inscriptos,
    DATEDIFF(t.fecha_inicio, CURDATE()) AS comienza_en
  FROM torneos t
  LEFT JOIN usuarios prov ON prov.user_id = t.proveedor_id
  WHERE (? = '' OR t.nombre LIKE CONCAT('%', ?, '%') OR COALESCE(prov.nombre,'') LIKE CONCAT('%', ?, '%'))
  ORDER BY t.estado='abierto' DESC, t.fecha_inicio ASC, t.torneo_id DESC
  LIMIT ? OFFSET ?
";
$st = $conn->prepare($sql);
$st->bind_param("sssii", $q, $q, $q, $pageSize, $offset);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

/* Mis inscripciones (para pintar acción) — SIN bind param variádico */
$joined = [];
if (!empty($rows)) {
    $ids = array_map('intval', array_column($rows, 'torneo_id'));
    $inList = implode(',', $ids);
    if ($inList !== '') {
        $sqlJ = "SELECT torneo_id FROM participaciones WHERE jugador_id=? AND torneo_id IN ($inList)";
        $stJ  = $conn->prepare($sqlJ);
        $stJ->bind_param('i', $userId);
        $stJ->execute();
        $rJ = $stJ->get_result();
        while ($r = $rJ->fetch_assoc()) { $joined[(int)$r['torneo_id']] = true; }
        $stJ->close();
    }
}

/* mensajes -> alert() */
$okMsg  = isset($_GET['ok'])  ? trim($_GET['ok'])  : '';
$errMsg = isset($_GET['err']) ? trim($_GET['err']) : '';
?>
<style>
.search-bar{display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap}
.search-bar input{padding:10px 12px;border-radius:10px;border:1px solid #e1ecec;font-size:14px;min-width:240px}
.search-bar button{padding:10px 14px;border:none;background:#07566b;color:#fff;border-radius:10px;cursor:pointer;font-weight:700}
.search-bar a.reset{padding:9px 12px;border:1px solid #1bab9d;color:#1bab9d;border-radius:10px;text-decoration:none;font-weight:700}
table tbody tr:hover{background:#f7fafb}
.btn-sm{padding:8px 12px;border:1px solid #1bab9d;color:#1bab9d;background:#fff;border-radius:10px;text-decoration:none}
.btn-sm:hover{background:rgba(27,171,157,.08)}
.actions{display:flex;gap:8px;align-items:center}
.pagination{display:flex;gap:8px;margin-top:14px;align-items:center;flex-wrap:wrap}
.pagination a,.pagination span{padding:8px 12px;border:1px solid #e1ecec;border-radius:999px;text-decoration:none;font-size:14px;line-height:1;color:#2a4e51;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.pagination .active{background:#1bab9d;color:#fff;border-color:transparent}
.pagination .disabled{color:#9ab3b5;background:#f3f7f7}
.row-link{cursor:pointer}
.row-link:focus{outline:2px solid #1bab9d; outline-offset:2px}
</style>

<div class="page-wrap">
  <h1 class="page-title">Torneos</h1>

  <div class="card-white">
    <form class="search-bar" method="get">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre o club">
      <button type="submit">Buscar</button>
      <?php if ($q !== ''): ?><a class="reset" href="?">Limpiar</a><?php endif; ?>
    </form>

    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Club</th>
          <th>Inicio</th>
          <th>Fin</th>
          <th>Comienza</th>
          <th>Inscriptos</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($rows): foreach ($rows as $t): 
            $isJoined = !empty($joined[(int)$t['torneo_id']]);
            $href = "/php/cliente/torneos/detalle_torneo.php?torneo_id=".(int)$t['torneo_id'];
        ?>
          <tr class="row-link" tabindex="0" data-href="<?= htmlspecialchars($href) ?>">
            <td><?= htmlspecialchars($t['nombre']) ?></td>
            <td><?= htmlspecialchars($t['club']) ?></td>
            <td><?= fmt_md($t['fecha_inicio']) ?></td>
            <td><?= fmt_md($t['fecha_fin']) ?></td>
            <td><?= comienza_label($t['comienza_en']) ?></td>
            <td><?= (int)$t['inscriptos'] ?></td>
            <td class="actions">
              <?php if ($isJoined): ?>
                <form method="post" action="/php/cliente/torneos/salirTorneo.php" style="display:inline" onsubmit="event.stopPropagation(); return confirm('¿Salir del torneo?');">
                  <input type="hidden" name="torneo_id" value="<?= (int)$t['torneo_id'] ?>">
                  <button type="submit" class="btn-sm">Salir</button>
                </form>
              <?php elseif ($t['estado']==='abierto'): ?>
                <form method="post" action="/php/cliente/torneos/unirseTorneo.php" style="display:inline" onsubmit="event.stopPropagation();">
                  <input type="hidden" name="torneo_id" value="<?= (int)$t['torneo_id'] ?>">
                  <input type="hidden" name="return" value="/php/cliente/torneos/torneos.php">
                  <button type="submit" class="btn-sm">Unirme</button>
                </form>
              <?php endif; ?>
              <!-- Quitamos el link “Ver detalle” porque la fila ya es clickeable -->
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="7" style="text-align:center;">No hay torneos</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php $prev=max(1,$page-1); $next=min($totalPages,$page+1); $qp=$q!==''?'&q='.urlencode($q):''; ?>
        <?= $page>1 ? '<a href="?page='.$prev.$qp.'">« Anterior</a>' : '<span class="disabled">« Anterior</span>' ?>
        <?php for($p=1;$p<=$totalPages;$p++): ?>
          <?= $p===$page ? '<span class="active">'.$p.'</span>' : '<a href="?page='.$p.$qp.'">'.$p.'</a>' ?>
        <?php endfor; ?>
        <?= $page<$totalPages ? '<a href="?page='.$next.$qp.'">Siguiente »</a>' : '<span class="disabled">Siguiente »</span>' ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Fila clickeable + teclado
document.querySelectorAll('.row-link').forEach(function(row){
  row.addEventListener('click', function(e){
    if (e.target.closest('form') || e.target.closest('a')) return; // no navegar si click interno
    window.location.href = this.dataset.href;
  });
  row.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ window.location.href = this.dataset.href; }});
});

// Mostrar alert() si viene ?ok o ?err y limpiar URL
<?php
$cleaner = "history.replaceState({}, '', window.location.pathname + window.location.search.replace(/(\\?|&)PLACE=[^&]*/, '').replace(/\\?&/,'?').replace(/\\?$/,''));";
if ($okMsg): ?> alert(<?= json_encode($okMsg) ?>); <?= str_replace('PLACE','ok',$cleaner) ?> <?php endif; ?>
<?php if ($errMsg): ?> alert(<?= json_encode($errMsg) ?>); <?= str_replace('PLACE','err',$cleaner) ?> <?php endif; ?>
</script>

<?php include './../includes/footer.php'; ?>
