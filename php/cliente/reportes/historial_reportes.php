<?php
include './../../config.php';
include './../includes/header.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: /php/login.php"); exit;
}

$userId   = (int)$_SESSION['usuario_id'];
$pageSize = 6; // un poco más generoso
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $pageSize;

/* Conteo simple (sin buscador) */
$sqlCount = "SELECT COUNT(*) AS total FROM reportes WHERE usuario_id = ?";
$stC = $conn->prepare($sqlCount);
$stC->bind_param("i", $userId);
$stC->execute();
$totalRows = (int)$stC->get_result()->fetch_assoc()['total'];
$stC->close();
$totalPages = max(1, (int)ceil($totalRows / $pageSize));

/* Página */
$sql = "
  SELECT rep.id, rep.nombre_reporte, rep.descripcion, rep.fecha_reporte, rep.estado,
         rep.cancha_id, rep.tipo_falla,
         c.nombre AS cancha_nombre, u.nombre AS club_nombre
  FROM reportes rep
  LEFT JOIN canchas c ON c.cancha_id = rep.cancha_id
  LEFT JOIN usuarios u ON u.user_id = c.proveedor_id
  WHERE rep.usuario_id = ?
  ORDER BY rep.id DESC
  LIMIT ? OFFSET ?
";
$st = $conn->prepare($sql);
$st->bind_param("iii", $userId, $pageSize, $offset);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

/* helpers */
function fmt_dia_mes(string $ymd): string {
  $ts = strtotime($ymd);
  return $ts ? date('d/m', $ts) : $ymd;
}
?>
<style>
.page-wrap{ padding:24px 16px 40px; }
.card-white{ max-width:1200px; margin:0 auto 24px auto; }

/* Tabla estilo Reservas (no centrada) */
table{ width:100%; border-collapse:separate; border-spacing:0; }
thead th{ text-align:left; padding:12px 14px; color:#2a4e51; border-bottom:2px solid #e1ecec; font-weight:700; }
tbody td{ text-align:left; padding:12px 14px; border-bottom:1px solid #f0f5f5; }
tbody tr:hover{ background:#f7fafb; }

/* Paginación */
.pagination{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:center; margin-top:14px; }
.pagination a,.pagination span{ padding:8px 12px; border:1px solid #e1ecec; border-radius:999px; text-decoration:none; font-size:14px; line-height:1; color:#2a4e51; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.06) }
.pagination .active{ background:#1bab9d; color:#fff; border-color:transparent }
.pagination .disabled{ color:#9ab3b5; background:#f3f7f7 }

.row-link{ cursor:pointer; }
.row-link:focus{ outline:2px solid #1bab9d; outline-offset:2px; }
</style>

<div class="page-wrap">
  <h1 class="page-title">Mis reportes</h1>

  <div class="card-white">
    <table>
      <thead>
        <tr>
          <th>Título</th>
          <th>Razón</th>
          <th>Estado</th>
          <th>Club</th>
          <th>Cancha</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($rows): foreach ($rows as $r):
          $href = "/php/cliente/reportes/detalle_reporte.php?id=".(int)$r['id'];
          $razon = ($r['tipo_falla']==='sistema') ? 'Sistema' : 'Cancha';
        ?>
        <tr class="row-link" tabindex="0" data-href="<?= htmlspecialchars($href) ?>">
          <td><?= htmlspecialchars($r['nombre_reporte']) ?></td>
          <td><?= htmlspecialchars($razon) ?></td>
          <td><?= htmlspecialchars($r['estado']) ?></td>
          <td><?= htmlspecialchars($r['club_nombre'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['cancha_nombre'] ?? '—') ?></td>
          <td><?= fmt_dia_mes($r['fecha_reporte']) ?></td>
        </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" style="text-align:center;">No hay reportes</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php
          $prev = max(1, $page-1);
          $next = min($totalPages, $page+1);
          echo $page>1 ? '<a href="?page='.$prev.'">« Anterior</a>' : '<span class="disabled">« Anterior</span>';
          for ($p=1; $p<=$totalPages; $p++) {
            echo $p===$page ? '<span class="active">'.$p.'</span>' : '<a href="?page='.$p.'">'.$p.'</a>';
          }
          echo $page<$totalPages ? '<a href="?page='.$next.'">Siguiente »</a>' : '<span class="disabled">Siguiente »</span>';
        ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.querySelectorAll('.row-link').forEach(function(row){
  row.addEventListener('click', function(){ window.location.href = this.dataset.href; });
  row.addEventListener('keydown', function(e){ if(e.key==='Enter'){ window.location.href = this.dataset.href; }});
});
</script>

<?php include './../includes/footer.php'; ?>
