<?php
/* =========================================================================
 * file: php/proveedor/eventos/eventos.php
 * Lista de eventos especiales del PROVEEDOR
 * - UI igual a recepción
 * - Botón "Crear evento especial"
 * - Columna "Acciones" con Eliminar
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') {
  header('Location: ../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

/* Canchas del proveedor (para filtros) */
$sqlC = "SELECT cancha_id, nombre FROM canchas WHERE proveedor_id=? AND activa=1 ORDER BY nombre";
$stmt = $conn->prepare($sqlC);
$stmt->bind_param("i",$proveedor_id);
$stmt->execute();
$canchas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Filtros */
$hoy       = date('Y-m-d');
$cancha_id = (int)($_GET['cancha_id'] ?? 0);
$desde     = $_GET['desde'] ?? $hoy;
$hasta     = $_GET['hasta'] ?? date('Y-m-d', strtotime($hoy.' +14 days'));
$tipo      = $_GET['tipo']  ?? 'todos';     // bloqueo|torneo|otro|todos
$estado    = $_GET['estado']?? 'vigentes';  // vigentes|futuros|pasados|todos

/* Query eventos (excluye tipo 'promocion' para esta vista) */
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
$params = [$proveedor_id, $desde, $hasta];
$types  = "iss";

if ($cancha_id > 0) {
  $sql .= " AND e.cancha_id = ?";
  $params[] = $cancha_id; $types .= "i";
}
if ($tipo !== 'todos') {
  $sql .= " AND e.tipo = ?";
  $params[] = $tipo; $types .= "s";
}
$sql .= " ORDER BY e.fecha_inicio ASC, e.fecha_fin ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Helpers */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ddmmyyyy(?string $d): string { if(!$d) return '—'; $t=strtotime($d); return $t?date('d/m/Y',$t):'—'; }
function estadoEventoFila(array $e, string $hoy): string {
  $ini = substr($e['fecha_inicio'],0,10);
  $fin = substr($e['fecha_fin'],0,10);
  if ($ini <= $hoy && $hoy < $fin) return 'Vigente hoy';
  if ($ini >  $hoy)               return 'Próximo';
  return 'Finalizado';
}
?>
<main>
  <div class="section">
    <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <h2 style="margin:0;">Eventos especiales</h2>
      <a class="btn-add" href="eventosForm.php">Crear evento especial</a>
    </div>

    <style>
      /* ===== anchos manipulables ===== */
      :root{
        --col-titulo:   200px;
        --col-desc:     300px;
        --col-fini:     90px;
        --col-ffin:     90px;
        --col-hini:     90px;
        --col-hfin:     90px;
        --col-tipo:     70px;
        --col-estado:   100px;
        --col-acc:      110px;
      }

      /* Filtros */
      .fbar{
        display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;
        background:#fff; padding:14px 16px; border-radius:12px;
        box-shadow:0 4px 12px rgba(0,0,0,.08); margin-bottom:16px
      }
      .f{display:flex; flex-direction:column; gap:6px; min-width:190px}
      .f label{font-size:12px; color:#586168; font-weight:700}
      .f select,.f input[type="date"]{
        padding:10px 12px; border:1px solid #d6dadd; border-radius:10px; background:#fff; outline:none
      }

      /* Tabla estilo reportes */
      table{ width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:12px; overflow:hidden; table-layout:fixed; }
      thead th{ position:sticky; top:0; background:#f8fafc; z-index:1; text-align:left; font-weight:700; padding:10px 12px; font-size:13px; color:#334155; border-bottom:1px solid #e5e7eb; }
      tbody td{ padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
      tbody tr:hover{ background:#f7fbfd; }

      th.col-titulo, td.col-titulo   { width:var(--col-titulo); }
      th.col-desc,   td.col-desc     { width:var(--col-desc); }
      th.col-fini,   td.col-fini     { width:var(--col-fini);  text-wrap:nowrap; }
      th.col-ffin,   td.col-ffin     { width:var(--col-ffin);  text-wrap:nowrap; }
      th.col-hini,   td.col-hini     { width:var(--col-hini);  text-wrap:nowrap; }
      th.col-hfin,   td.col-hfin     { width:var(--col-hfin);  text-wrap:nowrap; }
      th.col-tipo,   td.col-tipo     { width:var(--col-tipo);  }
      th.col-estado, td.col-estado   { width:var(--col-estado); text-align:center; }
      th.col-acc,    td.col-acc      { width:var(--col-acc);    text-align:center; }

      /* Título y Descripción (como reportes) */
      .title-text{ white-space:normal; word-wrap:break-word; font-size:14px; color:#0f172a; }
      .desc-text { white-space:normal; word-wrap:break-word; font-size:12px; color:#64748b; }

      /* Pills */
      .pill{ display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; white-space:nowrap; border:1px solid transparent }
      .p-ok{   background:#e6f7f4; border-color:#c8efe8; color:#0f766e; }
      .p-warn{ background:#fff7e6; border-color:#ffe1b5; color:#92400e; }
      .p-bad{  background:#fde8e8; border-color:#f8c9c9; color:#7f1d1d; }

      .btn-action.delete{
        appearance:none; border:none; border-radius:8px; padding:6px 10px;
        cursor:pointer; font-weight:700; background:#fde8e8; border:1px solid #f8c9c9; color:#7f1d1d;
      }
    .btn-add{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;text-decoration:none;font-weight:500;font-size:14px;transition:filter .15s ease,transform .03s ease;white-space:nowrap;}
    .btn-add:hover{background:#139488;}
    </style>

    <form class="fbar" method="GET" id="fEventos">
      <div class="f">
        <label>Cancha</label>
        <select name="cancha_id" onchange="this.form.submit()">
          <option value="0" <?= $cancha_id===0?'selected':'' ?>>Todas</option>
          <?php foreach($canchas as $c): ?>
            <option value="<?= (int)$c['cancha_id'] ?>" <?= (int)$c['cancha_id']===$cancha_id?'selected':'' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f">
        <label>Desde</label>
        <input type="date" name="desde" value="<?= h($desde) ?>" onchange="this.form.submit()">
      </div>
      <div class="f">
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?= h($hasta) ?>" onchange="this.form.submit()">
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
      <thead>
        <tr>
          <th class="col-titulo">Título</th>
          <th class="col-desc">Descripción</th>
          <th class="col-fini">Fecha inicio</th>
          <th class="col-ffin">Fecha fin</th>
          <th class="col-hini">Hora inicio</th>
          <th class="col-hfin">Hora fin</th>
          <th class="col-tipo">Tipo</th>
          <th class="col-estado">Estado</th>
          <th class="col-acc">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $count = 0;
        foreach ($rows as $e):
          $estadoTxt = estadoEventoFila($e, $hoy);
          if ($estado==='vigentes' && $estadoTxt!=='Vigente hoy') continue;
          if ($estado==='futuros'  && $estadoTxt!=='Próximo')     continue;
          if ($estado==='pasados'  && $estadoTxt!=='Finalizado')  continue;

          $count++;
          $iniDT = strtotime($e['fecha_inicio']);
          $finDT = strtotime($e['fecha_fin']);

          $fIni = ddmmyyyy($e['fecha_inicio']);
          $fFin = ddmmyyyy($e['fecha_fin']);
          $hIni = $iniDT ? date('H:i', $iniDT) : '—';
          $hFin = $finDT ? date('H:i', $finDT) : '—';

          $pillTipo   = ($e['tipo']==='bloqueo') ? 'p-bad' : 'p-warn';
          $pillEstado = ['Vigente hoy'=>'p-ok','Próximo'=>'p-warn','Finalizado'=>'p-bad'][$estadoTxt] ?? 'p-warn';
        ?>
          <tr>
            <td class="col-titulo">
              <div class="title-text"><strong><?= h($e['titulo']) ?></strong></div>
              <?php if (!empty($e['cancha_nombre'])): ?>
                <div class="desc-text">Cancha: <?= h($e['cancha_nombre']) ?></div>
              <?php endif; ?>
            </td>
            <td class="col-desc">
              <?php if (!empty($e['descripcion'])): ?>
                <div class="desc-text"><?= nl2br(h($e['descripcion'])) ?></div>
              <?php else: ?>
                <span class="desc-text" style="opacity:.7">—</span>
              <?php endif; ?>
            </td>
            <td class="col-fini"><?= h($fIni) ?></td>
            <td class="col-ffin"><?= h($fFin) ?></td>
            <td class="col-hini"><?= h($hIni) ?></td>
            <td class="col-hfin"><?= h($hFin) ?></td>
            <td class="col-tipo"><span class="pill <?= $pillTipo ?>"><?= h(ucfirst($e['tipo'])) ?></span></td>
            <td class="col-estado"><span class="pill <?= $pillEstado ?>"><?= h($estadoTxt) ?></span></td>
            <td class="col-acc">
              <form method="POST" action="eventosAction.php" onsubmit="return confirm('¿Eliminar este evento?');" style="display:inline-block">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="evento_id" value="<?= (int)$e['evento_id'] ?>">
                <button type="submit" class="btn-action delete">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; if ($count===0): ?>
          <tr><td colspan="9" style="text-align:center;">Sin eventos con esos filtros.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
