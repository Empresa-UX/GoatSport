<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\reportes\historial_reportes.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: /php/login.php"); exit;
}

$userId   = (int)$_SESSION['usuario_id'];
$pageSize = 3; // 3 por página
$q        = isset($_GET['q']) ? trim($_GET['q']) : '';
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $pageSize;

/* Conteo con filtro */
$sqlCount = "
  SELECT COUNT(*) AS total
  FROM reportes rep
  WHERE rep.usuario_id = ?
    AND (? = '' OR rep.nombre_reporte LIKE CONCAT('%', ?, '%'))
";
$stC = $conn->prepare($sqlCount);
$stC->bind_param("iss", $userId, $q, $q);
$stC->execute();
$totalRows = (int)$stC->get_result()->fetch_assoc()['total'];
$stC->close();
$totalPages = max(1, (int)ceil($totalRows / $pageSize));

/* Página con filtro */
$sql = "
  SELECT rep.id, rep.nombre_reporte, rep.descripcion, rep.fecha_reporte, rep.estado,
         rep.reserva_id, rep.cancha_id, rep.respuesta_proveedor,
         c.nombre AS cancha_nombre, u.nombre AS club_nombre
  FROM reportes rep
  LEFT JOIN canchas c ON c.cancha_id = rep.cancha_id
  LEFT JOIN usuarios u ON u.user_id = c.proveedor_id
  WHERE rep.usuario_id = ?
    AND (? = '' OR rep.nombre_reporte LIKE CONCAT('%', ?, '%'))
  ORDER BY rep.id DESC
  LIMIT ? OFFSET ?
";
$st = $conn->prepare($sql);
$st->bind_param("issii", $userId, $q, $q, $pageSize, $offset);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

/* helpers */
function badge_estado($estado){
  $base='display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid;';
  if ($estado === 'Pendiente') return '<span style="'.$base.'background:#fff6e5;color:#8a5a00;border-color:#f5d49a">Pendiente</span>';
  return '<span style="'.$base.'background:#e6fff5;color:#0d6b4d;border-color:#a5e4c8">Resuelto</span>';
}
?>
<style>
table tbody tr:hover{background:#f7fafb}
.pagination{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.pagination a,.pagination span{padding:8px 12px;border:1px solid #e1ecec;border-radius:999px;text-decoration:none;font-size:14px;line-height:1;color:#2a4e51;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.pagination .active{background:#1bab9d;color:#fff;border-color:transparent}
.pagination .disabled{color:#9ab3b5;background:#f3f7f7}
.row-link{cursor:pointer}
.row-link:focus{outline:2px solid #1bab9d; outline-offset:2px}
.small{color:#5a6b6c;font-size:13px}
/* Botón secundario igual que en crear */
.btn-secondary{
  text-decoration:none;display:inline-block;padding:12px 14px;border-radius:10px;
  border:1px solid #1bab9d;color:#1bab9d;background:#fff;font-weight:700;font-size:1rem;
  transition:background .2s, transform .1s;
}
.btn-secondary:hover{ background:rgba(27,171,157,.08); transform:translateY(-1px); }
/* Barra de búsqueda */
.search-bar{display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap}
.search-bar input{padding:10px 12px;border-radius:10px;border:1px solid #e1ecec;font-size:14px;min-width:240px;background:#fff;color:#043b3d}
.search-bar button{padding:10px 14px;border:none;background:#07566b;color:#fff;border-radius:10px;cursor:pointer;font-weight:700}
.search-bar a.reset{padding:9px 12px;border:1px solid #1bab9d;color:#1bab9d;border-radius:10px;text-decoration:none;font-weight:700}
.pager-wrap{display:flex;justify-content:space-between;align-items:center;margin-top:14px;gap:12px;flex-wrap:wrap}
</style>

<div class="page-wrap">
  <h1 class="page-title">Mis reportes</h1>

  <div class="card-white">
    <!-- Filtro de búsqueda -->
    <form class="search-bar" method="get">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por título del reporte">
      <button type="submit">Buscar</button>
      <?php if ($q !== ''): ?>
        <a class="reset" href="?">Limpiar</a>
      <?php endif; ?>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Título</th>
          <th>Estado</th>
          <th>Club</th>
          <th>Cancha</th>
          <th>Reserva</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($rows): foreach ($rows as $r):
          $href = "/php/cliente/reportes/detalle_reporte.php?id=".(int)$r['id'];
        ?>
        <tr class="row-link" tabindex="0" data-href="<?= htmlspecialchars($href) ?>">
          <td>#<?= (int)$r['id'] ?></td>
          <td>
            <?= htmlspecialchars($r['nombre_reporte']) ?><br>
            <?php if (!empty($r['respuesta_proveedor'])): ?>
              <span class="small">Respuesta: <?= htmlspecialchars($r['respuesta_proveedor']) ?></span>
            <?php else: ?>
              <span class="small">Sin respuesta aún</span>
            <?php endif; ?>
          </td>
          <td><?= badge_estado($r['estado']) ?></td>
          <td><?= htmlspecialchars($r['club_nombre'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['cancha_nombre'] ?? '—') ?></td>
          <td><?= $r['reserva_id'] ? '#'.(int)$r['reserva_id'] : '—' ?></td>
          <td><?= htmlspecialchars($r['fecha_reporte']) ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" style="text-align:center;">No hay resultados</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Paginación + botón Crear (a la derecha) -->
    <div class="pager-wrap">
      <div class="pagination">
        <?php
          $qp = $q !== '' ? '&q='.urlencode($q) : '';
          $prev = max(1, $page-1);
          $next = min($totalPages, $page+1);
          echo $page>1 ? '<a href="?page='.$prev.$qp.'">« Anterior</a>' : '<span class="disabled">« Anterior</span>';
          for ($p=1; $p<=$totalPages; $p++) {
            echo $p===$page ? '<span class="active">'.$p.'</span>' : '<a href="?page='.$p.$qp.'">'.$p.'</a>';
          }
          echo $page<$totalPages ? '<a href="?page='.$next.$qp.'">Siguiente »</a>' : '<span class="disabled">Siguiente »</span>';
        ?>
      </div>
      <a class="btn-secondary" href="/php/cliente/reportes/reportes.php">Crear nuevo reporte</a>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.row-link').forEach(function(row){
  row.addEventListener('click', function(){ window.location.href = this.dataset.href; });
  row.addEventListener('keydown', function(e){ if(e.key==='Enter'){ window.location.href = this.dataset.href; }});
});
</script>

<?php include './../includes/footer.php'; ?>
