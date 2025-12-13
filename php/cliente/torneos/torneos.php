<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\torneos\torneos.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if ($_SESSION['rol'] !== 'cliente') { header("Location: /php/login.php"); exit; }

$userId   = (int)$_SESSION['usuario_id'];
$clubId   = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;
$pageSize = 4;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $pageSize;

// NUEVO: Variable para filtro de historial
$mostrarHistorial = isset($_GET['historial']) && $_GET['historial'] === '1';

/* helpers */
function fmt_md(string $d): string {
    if (!$d) return '—';
    [$y,$m,$day] = explode('-', $d);
    $mes = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][(int)$m] ?? '';
    return (int)$day.' '.$mes;
}

// FUNCIÓN IDÉNTICA A LA DE detalle_torneo.php
if (!function_exists('comienza_label')) {
    function comienza_label($n){
        if ($n === null) return '—';
        $n = (int)$n;
        if ($n > 1) return "Comienza en $n días";
        if ($n === 1) return "Comienza mañana";
        if ($n === 0) return "Comienza hoy";
        return "Terminado";
    }
}

/* Obtener todos los clubes para el dropdown */
$clubes = [];
$sqlClubes = "
    SELECT DISTINCT u.user_id, u.nombre 
    FROM usuarios u 
    INNER JOIN torneos t ON t.proveedor_id = u.user_id 
    WHERE u.rol = 'proveedor' 
    ORDER BY u.nombre ASC
";
$resultClubes = $conn->query($sqlClubes);
if ($resultClubes) {
    while ($club = $resultClubes->fetch_assoc()) {
        $clubes[] = $club;
    }
}

/* Count */
$sqlCount = "
  SELECT COUNT(*) AS total
  FROM torneos t
  LEFT JOIN usuarios prov ON prov.user_id = t.proveedor_id
  WHERE 1=1
  " . ($clubId > 0 ? " AND t.proveedor_id = ?" : "") . "
  " . ($mostrarHistorial ? " AND t.fecha_inicio < CURDATE()" : " AND t.fecha_inicio >= CURDATE() AND t.estado IN ('abierto', 'cerrado')") . "
";

$c = $conn->prepare($sqlCount);
if ($clubId > 0) {
    $c->bind_param("i", $clubId);
}
$c->execute();
$total = (int)$c->get_result()->fetch_assoc()['total'];
$c->close();
$totalPages = max(1, (int)ceil($total / $pageSize));

/* List */
$sql = "
  SELECT
    t.torneo_id, t.nombre, t.fecha_inicio, t.fecha_fin, t.estado,
    t.proveedor_id, COALESCE(prov.nombre,'—') AS club,
    " . (!$mostrarHistorial ? "(SELECT COUNT(*) FROM participaciones p WHERE p.torneo_id=t.torneo_id AND p.estado='aceptada') AS inscriptos," : "") . "
    DATEDIFF(t.fecha_inicio, CURDATE()) AS comienza_en
  FROM torneos t
  LEFT JOIN usuarios prov ON prov.user_id = t.proveedor_id
  WHERE 1=1
  " . ($clubId > 0 ? " AND t.proveedor_id = ?" : "") . "
  " . ($mostrarHistorial ? " AND t.fecha_inicio < CURDATE()" : " AND t.fecha_inicio >= CURDATE() AND t.estado IN ('abierto', 'cerrado')") . "
  ORDER BY " . ($mostrarHistorial ? "t.fecha_inicio DESC" : "t.estado='abierto' DESC, t.fecha_inicio ASC") . ", t.torneo_id DESC
  LIMIT ? OFFSET ?
";

$st = $conn->prepare($sql);
if ($clubId > 0) {
    $st->bind_param("iii", $clubId, $pageSize, $offset);
} else {
    $st->bind_param("ii", $pageSize, $offset);
}
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

/* Mis inscripciones (para pintar acción) — SOLO si NO estamos en historial */
$joined = [];
if (!$mostrarHistorial && !empty($rows)) {
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
.search-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
}
.search-form {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}
.search-bar select {
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #e1ecec;
    font-size: 14px;
    min-width: 240px;
    background: white;
    cursor: pointer;
}
.search-bar button {
    padding: 10px 14px;
    border: none;
    background: #07566b;
    color: #fff;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
}
.search-bar a.reset {
    padding: 9px 12px;
    border: 1px solid #1bab9d;
    color: #1bab9d;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
}
/* BOTÓN HISTORIAL */
.btn-historial {
    padding: 10px 14px;
    border: 1px solid #6c757d;
    background: #fff;
    color: #6c757d;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
}
.btn-historial:hover {
    background: rgba(108, 117, 125, 0.08);
    border-color: #5a6268;
}
.btn-historial.activo {
    background: #6c757d;
    color: #fff;
    border-color: #6c757d;
}
.btn-historial.activo:hover {
    background: #5a6268;
    border-color: #5a6268;
}

/* TABLA CENTRADA */
table {
    width: 100%;
    border-collapse: collapse;
}
table th {
    text-align: center;
    padding: 12px 8px;
    font-weight: 700;
    color: #2a4e51;
    border-bottom: 2px solid #e1ecec;
}
table td {
    text-align: center;
    padding: 12px 8px;
    border-bottom: 1px solid #f0f5f5;
}
table tbody tr:hover { background: #f7fafb; }

/* Acciones alineadas al centro */
.actions {
    display: flex;
    gap: 8px;
    align-items: center;
    justify-content: center;
}
.btn-sm {
    padding: 8px 12px;
    border: 1px solid #1bab9d;
    color: #1bab9d;
    background: #fff;
    border-radius: 10px;
    text-decoration: none;
    cursor: pointer;
}
.btn-sm:hover { background: rgba(27,171,157,.08); }

/* Paginación */
.pagination {
    display: flex;
    gap: 8px;
    margin-top: 14px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: center;
}
.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #e1ecec;
    border-radius: 999px;
    text-decoration: none;
    font-size: 14px;
    line-height: 1;
    color: #2a4e51;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
.pagination .active {
    background: #1bab9d;
    color: #fff;
    border-color: transparent;
}
.pagination .disabled { color: #9ab3b5; background: #f3f7f7; }
.row-link { cursor: pointer; }
.row-link:focus { outline: 2px solid #1bab9d; outline-offset: 2px; }
</style>

<div class="page-wrap">
  <h1 class="page-title">Torneos</h1>

  <div class="card-white">
    <div class="search-bar">
      <!-- Formulario de filtro por club -->
      <form class="search-form" method="get">
        <!-- Dropdown de clubes -->
        <select name="club_id" onchange="this.form.submit()">
          <option value="0">Todos los clubes</option>
          <?php foreach ($clubes as $club): ?>
            <option value="<?= (int)$club['user_id'] ?>" 
                <?= ($clubId === (int)$club['user_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($club['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <?php if ($clubId > 0): ?>
          <a class="reset" href="?<?= $mostrarHistorial ? 'historial=1' : '' ?>">Limpiar filtro</a>
        <?php endif; ?>
      </form>
      
      <!-- Botón de historial -->
      <?php
        $activo = $mostrarHistorial;
        $hrefParams = [];
        if ($clubId > 0) $hrefParams['club_id'] = $clubId;
        $hrefParams['historial'] = $activo ? '' : '1';
        $href = '?' . http_build_query(array_filter($hrefParams));
      ?>
      <a href="<?= htmlspecialchars($href) ?>" class="btn-historial <?= $activo ? 'activo' : '' ?>">
        <?= $activo ? 'Ver torneos activos' : 'Historial de torneos' ?>
      </a>
    </div>

    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Club</th>
          <th>Inicio</th>
          <th>Fin</th>
          <th>Estado</th>
          <?php if (!$mostrarHistorial): ?>
            <th>Inscriptos</th>
            <th>Acciones</th>
          <?php endif; ?>
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
            <?php if (!$mostrarHistorial): ?>
              <td><?= (int)($t['inscriptos'] ?? 0) ?></td>
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
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; else: ?>
          <tr>
            <td colspan="<?= $mostrarHistorial ? '5' : '7' ?>" style="text-align:center;">
              <?php
                if ($mostrarHistorial) {
                    echo $clubId > 0 ? 'No hay torneos terminados para este club' : 'No hay torneos en el historial';
                } else {
                    echo $clubId > 0 ? 'No hay torneos activos para este club' : 'No hay torneos activos disponibles';
                }
              ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php 
          $prev = max(1, $page - 1);
          $next = min($totalPages, $page + 1);
          
          // Construir parámetros para enlaces de paginación
          $pagParams = [];
          if ($clubId > 0) $pagParams['club_id'] = $clubId;
          if ($mostrarHistorial) $pagParams['historial'] = '1';
        ?>
        
        <?php if ($page > 1): ?>
          <?php $pagParams['page'] = $prev; ?>
          <a href="?<?= http_build_query($pagParams) ?>">« Anterior</a>
          <?php unset($pagParams['page']); ?>
        <?php else: ?>
          <span class="disabled">« Anterior</span>
        <?php endif; ?>
        
        <?php for($p = 1; $p <= $totalPages; $p++): ?>
          <?php $pagParams['page'] = $p; ?>
          <?php if ($p === $page): ?>
            <span class="active"><?= $p ?></span>
          <?php else: ?>
            <a href="?<?= http_build_query($pagParams) ?>"><?= $p ?></a>
          <?php endif; ?>
          <?php unset($pagParams['page']); ?>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
          <?php $pagParams['page'] = $next; ?>
          <a href="?<?= http_build_query($pagParams) ?>">Siguiente »</a>
        <?php else: ?>
          <span class="disabled">Siguiente »</span>
        <?php endif; ?>
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
