<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\notificaciones\notificaciones.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: /php/login.php"); exit;
}

$userId   = (int)$_SESSION['usuario_id'];
$pageSize = 6; // cuántas por página
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $pageSize;

/* ===== Helpers ===== */
function fmt_dia_mes(string $ymd_or_dt): string {
    $ts = strtotime($ymd_or_dt);
    return $ts ? date('d/m', $ts) : $ymd_or_dt;
}
function label_origen(string $o): string {
    // valores posibles según tu BD: 'sistema','app','recepcion','proveedor','cliente'
    $map = [
        'sistema'   => 'Sistema',
        'app'       => 'App',
        'recepcion' => 'Recepción',
        'proveedor' => 'Club',
        'cliente'   => 'Cliente',
    ];
    return $map[strtolower($o)] ?? ucfirst($o);
}
function label_tipo(string $t): string {
    // libre (torneo, reserva, etc.)
    return ucfirst($t);
}

/* ===== Conteo ===== */
$sqlCount = "SELECT COUNT(*) AS total FROM notificaciones WHERE usuario_id = ?";
$stC = $conn->prepare($sqlCount);
$stC->bind_param("i", $userId);
$stC->execute();
$totalRows = (int)$stC->get_result()->fetch_assoc()['total'];
$stC->close();
$totalPages = max(1, (int)ceil($totalRows / $pageSize));

/* ===== Página ===== */
$sql = "
  SELECT notificacion_id, tipo, origen, titulo, mensaje, creada_en, leida
  FROM notificaciones
  WHERE usuario_id = ?
  ORDER BY notificacion_id DESC
  LIMIT ? OFFSET ?
";
$st = $conn->prepare($sql);
$st->bind_param("iii", $userId, $pageSize, $offset);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
?>
<style>
.page-wrap{ padding:24px 16px 40px; }
.card-white{ max-width:1200px; margin:0 auto 24px auto; }

/* Tabla consistente con Reservas/Reportes (alineación izquierda) */
table{ width:100%; border-collapse:separate; border-spacing:0; }
thead th{
  text-align:left; padding:12px 14px; color:#2a4e51; border-bottom:2px solid #e1ecec; font-weight:700;
}
tbody td{
  text-align:left; padding:12px 14px; border-bottom:1px solid #f0f5f5; vertical-align:top;
}
tbody tr:hover{ background:#f7fafb; }
.row{ cursor:default; }

/* Cols mínimas para que respire */
.col-flag{ width:28px; }
.col-titulo{ min-width:260px; }
.col-mensaje{ min-width:360px; }

/* Estilo de no leída */
.unread .cell-title{ font-weight:700; }
.dot{
  display:inline-block; width:10px; height:10px; border-radius:50%;
  background:#1bab9d; margin-top:6px;
}

/* Mensaje con mejor lectura y salto de línea */
.msg{
  white-space:pre-wrap; word-break:break-word; line-height:1.45;
  color:#043b3d;
}

/* Paginación tipo chips */
.pagination{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:center; margin-top:14px; }
.pagination a,.pagination span{
  padding:8px 12px; border:1px solid #e1ecec; border-radius:999px; text-decoration:none;
  font-size:14px; line-height:1; color:#2a4e51; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.pagination .active{ background:#1bab9d; color:#fff; border-color:transparent; }
.pagination .disabled{ color:#9ab3b5; background:#f3f7f7; }

/* Chips de metadatos */
.meta{
  display:inline-flex; gap:8px; flex-wrap:wrap; font-size:13px; color:#5a6b6c;
}
.meta .chip{
  display:inline-block; padding:4px 8px; border:1px solid #e1ecec; border-radius:999px; background:#fff;
}
.date{ white-space:nowrap; color:#415b5d; }
</style>

<div class="page-wrap">
  <h1 class="page-title">Notificaciones</h1>

  <div class="card-white">
    <table>
      <thead>
        <tr>
          <th class="col-flag"></th>
          <th class="col-titulo">Título</th>
          <th>Tipo</th>
          <th>Origen</th>
          <th class="col-mensaje">Mensaje</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $n):
            $isUnread = ((int)$n['leida'] === 0);
          ?>
            <tr class="row <?= $isUnread ? 'unread' : '' ?>">
              <td class="col-flag"><?= $isUnread ? '<span class="dot" title="No leída"></span>' : '' ?></td>
              <td class="cell-title"><?= htmlspecialchars($n['titulo']) ?></td>
              <td><?= htmlspecialchars(label_tipo($n['tipo'])) ?></td>
              <td><?= htmlspecialchars(label_origen($n['origen'])) ?></td>
              <td><div class="msg"><?= htmlspecialchars($n['mensaje']) ?></div></td>
              <td class="date"><?= fmt_dia_mes($n['creada_en']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" style="text-align:center;">No tienes notificaciones</td></tr>
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

<?php include './../includes/footer.php'; ?>
