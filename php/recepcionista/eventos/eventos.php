<?php
/* =========================================================================
 * file: php/recepcionista/eventos/index.php   (AJUSTADO A TU BD)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';

$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);
if ($proveedor_id <= 0) { echo "<main><div class='section'><p>Sesión inválida.</p></div></main>"; include __DIR__ . '/../includes/footer.php'; exit; }

/* Canchas del proveedor (para filtros) */
$sqlC = "SELECT cancha_id, nombre FROM canchas WHERE proveedor_id=? AND activa=1 ORDER BY nombre";
$stmt = $conn->prepare($sqlC); $stmt->bind_param("i",$proveedor_id); $stmt->execute();
$canchas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

/* Filtros */
$hoy = date('Y-m-d');
$cancha_id = (int)($_GET['cancha_id'] ?? 0);
$desde     = $_GET['desde'] ?? $hoy;
$hasta     = $_GET['hasta'] ?? date('Y-m-d', strtotime($hoy.' +14 days'));
$tipo      = $_GET['tipo']  ?? 'todos'; // bloqueo|torneo|otro|todos
$estado    = $_GET['estado']?? 'vigentes'; // vigentes|futuros|pasados|todos

/* Query eventos (NO promociones aquí) */
$sql = "
  SELECT e.evento_id, e.titulo, e.descripcion, e.fecha_inicio, e.fecha_fin, e.tipo,
         e.color, e.cancha_id, c.nombre AS cancha_nombre
  FROM eventos_especiales e
  LEFT JOIN canchas c ON c.cancha_id = e.cancha_id
  WHERE e.proveedor_id = ?
    AND DATE(e.fecha_fin)   >= ?
    AND DATE(e.fecha_inicio)<= ?
    AND e.tipo <> 'promocion'
";
$params = [$proveedor_id, $desde, $hasta]; $types = "iss";

if ($cancha_id > 0) { $sql .= " AND e.cancha_id = ?"; $params[] = $cancha_id; $types .= "i"; }
if ($tipo !== 'todos') { $sql .= " AND e.tipo = ?"; $params[] = $tipo; $types .= "s"; }

$sql .= " ORDER BY e.fecha_inicio ASC, e.fecha_fin ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Estado por fechas (día) */
function estadoEventoFila(array $e, string $hoy): string {
  $ini = substr($e['fecha_inicio'],0,10); $fin = substr($e['fecha_fin'],0,10);
  if ($ini <= $hoy && $hoy < $fin) return 'Vigente hoy';
  if ($ini >  $hoy) return 'Próximo';
  return 'Finalizado';
}
?>
<main>
  <div class="section">
    <div class="section-header"><h2>Eventos especiales</h2></div>

    <style>
      .fbar{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;background:#fff;padding:14px 16px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);margin-bottom:16px}
      .f{display:flex;flex-direction:column;gap:6px;min-width:200px}
      .f label{font-size:12px;color:#586168;font-weight:700}
      .f select,.f input[type="date"]{padding:10px 12px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none}
      .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;white-space:nowrap;border:1px solid transparent}
      .p-ok{background:#e6f7f4;border-color:#c8efe8;color:#0f766e}
      .p-pend{background:#fff7e6;border-color:#ffe1b5;color:#995c00}
      .p-bad{background:#fde8e8;border-color:#f8c9c9;color:#7f1d1d}
      table tr td:nth-child(2){min-width:170px}
      table tr td:nth-child(3){min-width:210px}
      table tr td:nth-child(4){min-width:110px}
      table tr td:nth-child(5){min-width:120px}
    </style>

    <form class="fbar" method="GET" id="fEventos">
      <div class="f">
        <label>Cancha</label>
        <select name="cancha_id" onchange="this.form.submit()">
          <option value="0" <?= $cancha_id===0?'selected':'' ?>>Todas</option>
          <?php foreach($canchas as $c): ?>
            <option value="<?= (int)$c['cancha_id'] ?>" <?= $c['cancha_id']===$cancha_id?'selected':'' ?>>
              <?= htmlspecialchars($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Desde</label>
        <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>" onchange="this.form.submit()">
      </div>
      <div class="f">
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>" onchange="this.form.submit()">
      </div>
      <div class="f">
        <label>Tipo</label>
        <select name="tipo" onchange="this.form.submit()">
          <?php foreach (['todos'=>'Todos','bloqueo'=>'Bloqueo','torneo'=>'Torneo','otro'=>'Otro'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $tipo===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Estado</label>
        <select name="estado" onchange="this.form.submit()">
          <?php foreach (['vigentes'=>'Vigentes hoy','futuros'=>'Próximos','pasados'=>'Finalizados','todos'=>'Todos'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $estado===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <table>
      <tr>
        <th>Título</th>
        <th>Cancha</th>
        <th>Inicio — Fin</th>
        <th>Tipo</th>
        <th>Estado</th>
      </tr>
      <?php
      $count=0;
      foreach ($rows as $e):
        $estadoTxt = estadoEventoFila($e, $hoy);
        if ($estado==='vigentes' && $estadoTxt!=='Vigente hoy') continue;
        if ($estado==='futuros'  && $estadoTxt!=='Próximo') continue;
        if ($estado==='pasados'  && $estadoTxt!=='Finalizado') continue;

        $count++;
        $ini = date('d/m/Y H:i', strtotime($e['fecha_inicio']));
        $fin = date('d/m/Y H:i', strtotime($e['fecha_fin']));
        $pillType = ['bloqueo'=>'p-bad','torneo'=>'p-pend','otro'=>'p-pend'][$e['tipo']] ?? 'p-pend';
        $pillEstado = ['Vigente hoy'=>'p-ok','Próximo'=>'p-pend','Finalizado'=>'p-bad'][$estadoTxt] ?? 'p-pend';
      ?>
        <tr>
          <td><?= htmlspecialchars($e['titulo']) ?></td>
          <td><?= htmlspecialchars($e['cancha_nombre'] ?? '-') ?></td>
          <td><?= $ini ?> — <?= $fin ?></td>
          <td><span class="pill <?= $pillType ?>"><?= htmlspecialchars(ucfirst($e['tipo'])) ?></span></td>
          <td><span class="pill <?= $pillEstado ?>"><?= $estadoTxt ?></span></td>
        </tr>
      <?php endforeach; if ($count===0): ?>
        <tr><td colspan="5" style="text-align:center;">Sin eventos con esos filtros.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
