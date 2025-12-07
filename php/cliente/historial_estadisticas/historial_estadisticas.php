<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\historial_estadisticas\historial_estadisticas.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

$userId   = (int)$_SESSION['usuario_id'];
$pageSize = 3;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $pageSize;

/* Total (sin duplicar): creador o participante aceptado */
$sql_count = "
    SELECT COUNT(*) AS total FROM (
        SELECT DISTINCT r.reserva_id
        FROM reservas r
        LEFT JOIN participaciones p
               ON p.reserva_id = r.reserva_id
              AND p.jugador_id = ?
              AND p.estado = 'aceptada'
        WHERE r.creador_id = ?
           OR p.jugador_id IS NOT NULL
    ) x
";
$stmtC = $conn->prepare($sql_count);
$stmtC->bind_param("ii", $userId, $userId);
$stmtC->execute();
$totalRows = (int)$stmtC->get_result()->fetch_assoc()['total'];
$stmtC->close();

$totalPages = max(1, (int)ceil($totalRows / $pageSize));

/* Página actual */
$sql_reservas = "
    SELECT 
        r.reserva_id,
        r.fecha,
        r.hora_inicio,
        r.hora_fin,
        r.estado,
        r.tipo_reserva,
        c.ubicacion,
        c.nombre       AS cancha_nombre,
        c.cancha_id,
        CASE WHEN r.creador_id = ? THEN 1 ELSE 0 END AS es_creador
    FROM reservas r
    INNER JOIN canchas c ON r.cancha_id = c.cancha_id
    LEFT JOIN participaciones p 
        ON p.reserva_id = r.reserva_id 
       AND p.jugador_id = ? 
       AND p.estado = 'aceptada'
    WHERE r.creador_id = ?
       OR p.jugador_id IS NOT NULL
    GROUP BY r.reserva_id
    ORDER BY r.fecha DESC, r.hora_inicio DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql_reservas);
$stmt->bind_param("iiiii", $userId, $userId, $userId, $pageSize, $offset);
$stmt->execute();
$result_reservas   = $stmt->get_result();
$ultimas_reservas  = $result_reservas->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Stats */
$sql_stats = "
    SELECT 
        r.partidos     AS partidos_jugados,
        r.victorias,
        r.derrotas,
        r.puntos,
        ROUND((r.victorias / NULLIF(r.partidos, 0)) * 100, 0) AS porcentaje_victorias
    FROM ranking r
    WHERE r.usuario_id = ?
    LIMIT 1
";
$stmt2 = $conn->prepare($sql_stats);
$stmt2->bind_param("i", $userId);
$stmt2->execute();
$estadisticas = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

if (!$estadisticas) {
    $estadisticas = [
        'partidos_jugados'     => 0,
        'victorias'            => 0,
        'derrotas'             => 0,
        'puntos'               => 0,
        'porcentaje_victorias' => 0
    ];
}

/* helper badge */
function estado_badge_class(string $estado): string {
    $cls = 'badge';
    if ($estado === 'confirmada') $cls .= ' badge--confirmada';
    elseif ($estado === 'pendiente') $cls .= ' badge--pendiente';
    elseif ($estado === 'cancelada') $cls .= ' badge--cancelada';
    elseif ($estado === 'no_show') $cls .= ' badge--no_show';
    return $cls;
}
?>
<!-- Estilos específicos (embebidos) para fila clickeable + paginación + badges -->
<style>
table tbody tr:hover{ background:#f7fafb; }
.badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid rgba(0,0,0,.08)}
.badge--pendiente{background:#fff6e5;color:#8a5a00;border-color:#f5d49a}
.badge--confirmada{background:#e6fff5;color:#0d6b4d;border-color:#a5e4c8}
.badge--cancelada{background:#ffecec;color:#8a1f1f;border-color:#f1a7a7}
.badge--no_show{background:#f2f4f7;color:#5b5b5b;border-color:#d8dde3}

.row-link{cursor:pointer}
.row-link:focus{outline:2px solid #1bab9d; outline-offset:2px}

.pagination{ display:flex; gap:8px; margin-top:14px; align-items:center; flex-wrap:wrap; }
.pagination a,.pagination span{
  padding:8px 12px; border:1px solid #e1ecec; border-radius:999px; text-decoration:none;
  font-size:14px; line-height:1; color:#2a4e51; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.06);
}
.pagination .active{ background:#1bab9d; color:#fff; border-color:transparent; }
.pagination .disabled{ color:#9ab3b5; background:#f3f7f7; }
</style>

<div class="page-wrap">
    <h1 class="page-title">Historial y estadísticas</h1>

    <div class="stats-container">
        <div>
            <h2 class="section-title">Últimas reservas</h2>
            <div class="card-white">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Club / Ubicación</th>
                            <th>Cancha</th>
                            <th>Estado</th>
                            <th>Tu rol</th>
                        </tr>
                    </thead>
                    <tbody id="reservas-tbody">
                        <?php if (!empty($ultimas_reservas)): ?>
                            <?php foreach ($ultimas_reservas as $reserva): 
                                $estado = (string)$reserva['estado'];
                                $clsEstado = estado_badge_class($estado);
                                $href = "/php/cliente/historial_estadisticas/detalle_reserva.php?reserva_id=".(int)$reserva['reserva_id'];
                            ?>
                                <tr class="row-link" tabindex="0" data-href="<?= htmlspecialchars($href) ?>">
                                    <td><?= htmlspecialchars($reserva['fecha']) ?></td>
                                    <td><?= htmlspecialchars(substr($reserva['hora_inicio'], 0, 5)) ?> - <?= htmlspecialchars(substr($reserva['hora_fin'], 0, 5)) ?></td>
                                    <td><?= nl2br(htmlspecialchars($reserva['ubicacion'])) ?></td>
                                    <td>#<?= (int)$reserva['cancha_id'] ?> — <?= htmlspecialchars($reserva['cancha_nombre']) ?></td>
                                    <td><span class="<?= $clsEstado ?>"><?= htmlspecialchars($estado) ?></span></td>
                                    <td><?= ((int)$reserva['es_creador'] === 1) ? 'Creador' : 'Invitado' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No tienes reservas aún</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php $prev = max(1, $page-1); $next = min($totalPages, $page+1); ?>
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $prev ?>">« Anterior</a>
                        <?php else: ?>
                            <span class="disabled">« Anterior</span>
                        <?php endif; ?>

                        <?php for ($p=1; $p <= $totalPages; $p++): ?>
                            <?php if ($p === $page): ?>
                                <span class="active"><?= $p ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $p ?>"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $next ?>">Siguiente »</a>
                        <?php else: ?>
                            <span class="disabled">Siguiente »</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h2 class="section-title">Estadísticas</h2>
            <div class="card-white">
                <table>
                    <tbody>
                        <tr><td class="label-stat">Partidos jugados</td><td class="value-stat"><?= (int)$estadisticas['partidos_jugados'] ?></td></tr>
                        <tr><td class="label-stat">Victorias</td><td class="value-stat"><?= (int)$estadisticas['victorias'] ?></td></tr>
                        <tr><td class="label-stat">Derrotas</td><td class="value-stat"><?= (int)$estadisticas['derrotas'] ?></td></tr>
                        <tr><td class="label-stat">% de Victorias</td><td class="value-stat"><?= (int)$estadisticas['porcentaje_victorias'] ?>%</td></tr>
                        <tr><td class="label-stat">Puntos</td><td class="value-stat"><?= (int)$estadisticas['puntos'] ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Por qué: UX accesible. Click y Enter navegan al detalle.
document.querySelectorAll('.row-link').forEach(function(row){
    row.addEventListener('click', function(){ window.location.href = this.dataset.href; });
    row.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ window.location.href = this.dataset.href; }});
});
</script>

<?php include './../includes/footer.php'; ?>
