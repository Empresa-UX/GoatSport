<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\ranking\ranking.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

$userId   = (int)$_SESSION['usuario_id'];
$pageSize = 10;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $pageSize;
$q        = isset($_GET['q']) ? trim($_GET['q']) : '';

/* --- OBTENER NOMBRE DEL USUARIO desde Cliente_detalle --- */
$nombreUsuario = '';
if ($userId > 0) {
    $stmtNombre = $conn->prepare("
        SELECT u.nombre 
        FROM usuarios u
        INNER JOIN Cliente_detalle cd ON u.user_id = cd.cliente_id
        WHERE cd.cliente_id = ?
    ");
    $stmtNombre->bind_param("i", $userId);
    $stmtNombre->execute();
    $resultNombre = $stmtNombre->get_result();
    if ($rowNombre = $resultNombre->fetch_assoc()) {
        $nombreUsuario = htmlspecialchars($rowNombre['nombre']);
    }
    $stmtNombre->close();
}

/* --- COUNT (filtrado por nombre, si aplica) --- */
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM Cliente_detalle cd
    INNER JOIN usuarios u ON cd.cliente_id = u.user_id
    LEFT JOIN ranking r ON cd.cliente_id = r.usuario_id
    WHERE (? = '' OR u.nombre LIKE CONCAT('%', ?, '%'))
";
$stmtC = $conn->prepare($sqlCount);
$stmtC->bind_param("ss", $q, $q);
$stmtC->execute();
$totalRows = (int)$stmtC->get_result()->fetch_assoc()['total'];
$stmtC->close();

$totalPages = max(1, (int)ceil($totalRows / $pageSize));

/* --- LIST (misma condición, orden + paginación) --- */
$sql = "
    SELECT 
        cd.cliente_id AS usuario_id,
        u.nombre,
        COALESCE(r.puntos, 0) AS puntos,
        COALESCE(r.partidos, 0) AS partidos,
        COALESCE(r.victorias, 0) AS victorias,
        CASE 
            WHEN COALESCE(r.partidos, 0) = 0 THEN 0
            ELSE ROUND((COALESCE(r.victorias, 0) / r.partidos) * 100, 0)
        END AS porcentaje_victorias
    FROM Cliente_detalle cd
    INNER JOIN usuarios u ON cd.cliente_id = u.user_id
    LEFT JOIN ranking r ON cd.cliente_id = r.usuario_id
    WHERE (? = '' OR u.nombre LIKE CONCAT('%', ?, '%'))
    ORDER BY 
        puntos DESC,
        victorias DESC,
        partidos DESC,
        usuario_id ASC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $q, $q, $pageSize, $offset);
$stmt->execute();
$result = $stmt->get_result();
$rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

/* --- Mis datos (si existe en ranking, sino valores por defecto) --- */
$me = null;
if ($userId > 0) {
    $stmMe = $conn->prepare("
        SELECT 
            cd.cliente_id AS usuario_id,
            u.nombre,
            COALESCE(r.puntos, 0) AS puntos,
            COALESCE(r.partidos, 0) AS partidos,
            COALESCE(r.victorias, 0) AS victorias,
            CASE 
                WHEN COALESCE(r.partidos, 0) = 0 THEN 0
                ELSE ROUND((COALESCE(r.victorias, 0) / r.partidos) * 100, 0)
            END AS porcentaje_victorias
        FROM Cliente_detalle cd
        INNER JOIN usuarios u ON cd.cliente_id = u.user_id
        LEFT JOIN ranking r ON cd.cliente_id = r.usuario_id
        WHERE cd.cliente_id = ?
        LIMIT 1
    ");
    $stmMe->bind_param("i", $userId);
    $stmMe->execute();
    $me = $stmMe->get_result()->fetch_assoc();
    $stmMe->close();
}

/* helpers */
function pct($v){ return is_null($v) ? 0 : (int)$v; }
?>
<!-- Estilos específicos para buscador, paginación y resaltado -->
<style>
.search-bar{
  display:flex; gap:8px; align-items:center; margin-bottom:12px; flex-wrap:wrap;
}
.search-bar input{
  padding:10px 12px; border-radius:10px; border:1px solid #e1ecec; font-size:14px; min-width:240px;
}
.search-bar button{
  padding:10px 14px; border:none; background:#07566b; color:#fff; border-radius:10px; cursor:pointer; font-weight:700;
}
.search-bar a.reset{
  padding:9px 12px; border:1px solid #1bab9d; color:#1bab9d; border-radius:10px; text-decoration:none; font-weight:700;
}

table tbody tr:hover{ background:#f7fafb; }
.tr-me{ background:#e9fbf7 !important; } /* tu fila */
.badge-me{
  display:inline-block; padding:2px 8px; font-size:12px; border-radius:999px;
  background:#1bab9d; color:#fff; margin-left:6px;
}

.pagination{ display:flex; gap:8px; margin-top:14px; align-items:center; flex-wrap:wrap; }
.pagination a,.pagination span{
  padding:8px 12px; border:1px solid #e1ecec; border-radius:999px; text-decoration:none;
  font-size:14px; line-height:1; color:#2a4e51; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.pagination .active{ background:#1bab9d; color:#fff; border-color:transparent; }
.pagination .disabled{ color:#9ab3b5; background:#f3f7f7; }

.me-card{ margin-bottom:12px; }
.me-card table td{ border:none; padding:6px 8px; }

.ranking-layout{
    display: grid;
    grid-template-columns: 0.6fr 1.4fr;
    gap: 40px;
    align-items: start;
}

@media (max-width: 900px){
    .ranking-layout{
        grid-template-columns: 1fr;
    }
}
.stats-title{
    color: #000;
}

</style>

<div class="page-wrap">
    <h1 class="page-title">Ranking</h1>

    <div class="ranking-layout">

        <!-- COLUMNA IZQUIERDA: MIS DATOS - SIEMPRE VISIBLE -->
        <div class="ranking-left">
            <div class="card-white me-card">
                <h2 class="stats-title">Estadísticas personales</h2>
                <table>
                    <tbody>
                        <tr>
                            <td><strong>Nombre</strong></td>
                            <td>
                                <?= $nombreUsuario ?: 'Usuario' ?>
                                <?php if ($userId > 0): ?><span class="badge-me">tú</span><?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Puntos</strong></td>
                            <td><?= $me ? (int)$me['puntos'] : '0' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Partidos</strong></td>
                            <td><?= $me ? (int)$me['partidos'] : '0' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Victorias</strong></td>
                            <td><?= $me ? (int)$me['victorias'] : '0' ?> (<?= $me ? pct($me['porcentaje_victorias']) : '0' ?>%)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COLUMNA DERECHA: RANKING GENERAL -->
        <div class="ranking-right">
            <div class="card-white">
                <form class="search-bar" method="get">
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar jugador por nombre">
                    <button type="submit">Buscar</button>
                    <?php if ($q !== ''): ?>
                        <a class="reset" href="?">Limpiar</a>
                    <?php endif; ?>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Posición</th>
                            <th>Nombre</th>
                            <th>Puntos</th>
                            <th>Partidos</th>
                            <th>Victorias</th>
                            <th>% Victorias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php $pos = $offset + 1; ?>
                            <?php foreach ($rows as $row): 
                                $isMe = ((int)$row['usuario_id'] === $userId);
                            ?>
                                <tr class="<?= $isMe ? 'tr-me' : '' ?>">
                                    <td><?= $pos ?></td>
                                    <td>
                                        <?= htmlspecialchars($row['nombre']) ?>
                                        <?= $isMe ? '<span class="badge-me">tú</span>' : '' ?>
                                    </td>
                                    <td><?= (int)$row['puntos'] ?></td>
                                    <td><?= (int)$row['partidos'] ?></td>
                                    <td><?= (int)$row['victorias'] ?></td>
                                    <td><?= pct($row['porcentaje_victorias']) ?>%</td>
                                </tr>
                                <?php $pos++; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">No hay datos de ranking</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php 
                        $prev = max(1, $page-1); 
                        $next = min($totalPages, $page+1); 
                        $qParam = $q !== '' ? '&q='.urlencode($q) : ''; 
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $prev . $qParam ?>">« Anterior</a>
                    <?php else: ?>
                        <span class="disabled">« Anterior</span>
                    <?php endif; ?>

                    <?php for ($p=1; $p <= $totalPages; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="active"><?= $p ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $p . $qParam ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $next . $qParam ?>">Siguiente »</a>
                    <?php else: ?>
                        <span class="disabled">Siguiente »</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include './../includes/footer.php'; ?>