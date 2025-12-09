<?php
/* =========================================================================
 * file: php/recepcionista/reservas/reservas.php   (REEMPLAZAR COMPLETO)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';

$recepcionista_id = (int)$_SESSION['usuario_id'];
$proveedor_id     = (int)($_SESSION['proveedor_id'] ?? 0);

// Filtros
$fecha          = $_GET['fecha']      ?? date('Y-m-d');
$cancha_filter  = isset($_GET['cancha_id']) ? (int)$_GET['cancha_id'] : 0;
$estado_pago    = $_GET['estado_pago']?? 'todos';
$tipo_reserva_f = $_GET['tipo_reserva'] ?? '';
$hora_desde     = $_GET['hora_desde']   ?? '';
$hora_hasta     = $_GET['hora_hasta']   ?? '';
$focus_id       = isset($_GET['focus']) ? (int)$_GET['focus'] : 0; // highlight de fila

$reTime = '/^\d{2}:\d{2}$/';
if ($hora_desde && !preg_match($reTime, $hora_desde)) $hora_desde = '';
if ($hora_hasta && !preg_match($reTime, $hora_hasta)) $hora_hasta = '';

// Canchas (SOLO ACTIVAS)
$canchas = [];
$stmt = $conn->prepare("SELECT cancha_id, nombre FROM canchas WHERE proveedor_id = ? AND activa = 1 ORDER BY nombre");
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $canchas[] = $r;
$stmt->close();

/* reservas + último pago */
$sql = "
SELECT 
  r.reserva_id, r.cancha_id, r.fecha, r.hora_inicio, r.hora_fin, r.estado,
  r.precio_total, r.tipo_reserva, c.nombre AS cancha_nombre,
  lp.pago_id, lp.metodo, lp.estado AS estado_pago
FROM reservas r
JOIN canchas c 
  ON c.cancha_id = r.cancha_id 
 AND c.proveedor_id = ?
LEFT JOIN (
  SELECT p.*
  FROM pagos p
  JOIN (SELECT reserva_id, MAX(pago_id) AS max_id FROM pagos GROUP BY reserva_id) t
    ON t.reserva_id = p.reserva_id AND t.max_id = p.pago_id
) lp
  ON lp.reserva_id = r.reserva_id
WHERE r.fecha = ?
";
$params = [$proveedor_id, $fecha]; $types  = "is";

if ($cancha_filter > 0) { $sql .= " AND r.cancha_id = ? "; $params[]=$cancha_filter; $types.="i"; }

// === Filtros de estado de pago (incluye presencial) ===
if ($estado_pago === 'pagado') {
  $sql .= " AND lp.estado = 'pagado' ";
} elseif ($estado_pago === 'pendiente_tarjeta') {
  $sql .= " AND lp.metodo = 'tarjeta' AND lp.estado = 'pendiente' ";
} elseif ($estado_pago === 'pendiente_mp') {
  $sql .= " AND lp.metodo = 'mercado_pago' AND lp.estado = 'pendiente' ";
} elseif ($estado_pago === 'pendiente_club') {
  $sql .= " AND lp.metodo = 'club' AND lp.estado = 'pendiente' ";
}
// =======================================================

if ($tipo_reserva_f === 'individual' || $tipo_reserva_f === 'equipo') { $sql.=" AND r.tipo_reserva = ? "; $params[]=$tipo_reserva_f; $types.="s"; }
if ($hora_desde && $hora_hasta) { $sql.=" AND NOT (r.hora_fin <= ? OR r.hora_inicio >= ?) "; $params[]=$hora_desde; $params[]=$hora_hasta; $types.="ss"; }
elseif ($hora_desde) { $sql.=" AND r.hora_fin > ? "; $params[]=$hora_desde; $types.="s"; }
elseif ($hora_hasta) { $sql.=" AND r.hora_inicio < ? "; $params[]=$hora_hasta; $types.="s"; }
$sql .= " ORDER BY r.hora_inicio ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reservas = $stmt->get_result();
$stmt->close();

/* Helpers UI */
function labelPago(?string $estadoPago, ?string $metodo, ?int $pagoId): string {
  if(!$pagoId) return 'Sin pago';
  return $estadoPago==='pagado'?'Pagado':(($metodo==='club')?'Pendiente (presencial)':ucfirst($estadoPago ?? '-'));
}
function classPago(?string $estadoPago, ?string $metodo, ?int $pagoId): string {
  if(!$pagoId) return 'chip-neutral';
  if($estadoPago==='pagado') return 'chip-pay-ok';
  if($estadoPago==='pendiente' && $metodo==='club') return 'chip-pay-warn';
  return 'chip-neutral';
}
function labelReserva(string $estado): string {
  return match ($estado) {
    'confirmada'=>'Confirmada','pendiente'=>'Pendiente','cancelada'=>'Cancelada', default=>ucfirst($estado ?: '-'),
  };
}
function classReserva(string $estado): string {
  return match ($estado) {
    'confirmada'=>'chip-ok','pendiente'=>'chip-info','cancelada'=>'chip-bad', default=>'chip-neutral',
  };
}
function labelMetodo(?string $metodo): string {
  return match ($metodo) {
    'club'=>'Presencial','mercado_pago'=>'Mercado Pago','tarjeta'=>'Tarjeta de crédito',
    null, '' => '-', default=>ucwords(str_replace('_',' ', $metodo)),
  };
}
function classMetodo(?string $metodo): string {
  return match ($metodo) {
    'club'=>'chip-method-club','mercado_pago'=>'chip-method-mp','tarjeta'=>'chip-method-card', default=>'chip-neutral',
  };
}
function capFirst(?string $s): string {
  if(!$s) return '-'; $s=mb_strtolower($s,'UTF-8'); $first=mb_strtoupper(mb_substr($s,0,1,'UTF-8'),'UTF-8'); return $first.mb_substr($s,1,null,'UTF-8');
}
?>
<main>
  <?php if (isset($_GET['ok'])): ?>
    <script> alert('<?= addslashes($_GET["ok"]) ?>'); if(history.replaceState){const u=new URL(location.href);u.search='';history.replaceState(null,'',u.toString());} </script>
  <?php elseif (isset($_GET['err'])): ?>
    <script> alert('<?= addslashes($_GET["err"]) ?>'); if(history.replaceState){const u=new URL(location.href);u.search='';history.replaceState(null,'',u.toString());} </script>
  <?php endif; ?>

  <style>
    .filterbar{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin:14px 0 16px}
    /* Ajustes para que entren los 6 filtros en una fila */
    .f-field{display:flex;flex-direction:column;gap:6px;min-width:160px;flex:1 1 160px}
    .f-field.tiny{min-width:110px;max-width:120px;flex:0 0 120px} /* Desde / Hasta compactos */
    .f-label{font-size:12px;color:#586168;font-weight:600;letter-spacing:.3px}
    .f-input,.f-select,.f-date,.f-time{width:100%;padding:8px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-shadow:0 1px 0 rgba(0,0,0,.03)}
    .f-input:focus,.f-select:focus,.f-date:focus,.f-time:focus{border-color:#1bab9d;box-shadow:0 0 0 3px rgba(27,171,157,.12)}
    .f-actions{margin-left:auto;display:flex;gap:10px}.f-actions .btn-add{display:none;}
    .chip{padding:2px 10px;border-radius:999px;font-size:12px;border:1px solid transparent;display:inline-flex;align-items:center;gap:6px;min-width:108px;justify-content:center}
    .chip-pay-ok{background:#e6f7f4;color:#0b6158;border-color:#b7e6de}
    .chip-pay-warn{background:#fff8e1;color:#9a6700;border-color:#ffe082}
    .chip-ok{background:#e8f5e9;color:#2e7d32;border-color:#c8e6c9}
    .chip-info{background:#e3f2fd;color:#1a5fb4;border-color:#bbdefb}
    .chip-bad{background:#ffebee;color:#c62828;border-color:#ffcdd2}
    .chip-neutral{background:#eef2f7;color:#415a77;border-color:#d8e0ea}
    .chip-method-club{background:#e6f7f4;color:#0b6158;border-color:#b7e6de}
    .chip-method-mp{background:#e3f2fd;color:#1a5fb4;border-color:#bbdefb}
    .chip-method-card{background:#ede7f6;color:#5e35b1;border-color:#d1c4e9}
    table td{vertical-align:top}
    .row-new{background:#f7fbfd}
    .actions .btn-add{background:#1bab9d;color:#fff;border:0;border-radius:10px;padding:6px 10px;font-weight:600}
    .actions .btn-add:hover{filter:brightness(.97)}
    .td-center{text-align:center}

    /* Highlight por focus */
    .row-highlight{ animation: hiBlink 1.2s ease-in-out 3; }
    @keyframes hiBlink { 0%,100%{background-color:inherit} 50%{background-color:#fffbe6} }
  </style>

  <div class="section">
    <div class="section-header">
      <h2>Reservas</h2>
      <button onclick="location.href='reservasForm.php'" class="btn-add">Registrar reserva sin cita previa</button>
    </div>

    <form method="GET" class="filterbar" id="filtersForm">
      <div class="f-field">
        <label class="f-label">Fecha</label>
        <input class="f-date" type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
      </div>

      <div class="f-field">
        <label class="f-label">Cancha</label>
        <select class="f-select" name="cancha_id">
          <option value="0">Todas</option>
          <?php foreach ($canchas as $c): ?>
            <option value="<?= (int)$c['cancha_id'] ?>" <?= $cancha_filter===(int)$c['cancha_id']?'selected':'' ?>>
              <?= htmlspecialchars($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="f-field">
        <label class="f-label">Estado de pago</label>
        <select class="f-select" name="estado_pago">
          <option value="todos" <?= $estado_pago==='todos'?'selected':'' ?>>Todos</option>
          <option value="pagado" <?= $estado_pago==='pagado'?'selected':'' ?>>Pagado</option>
          <option value="pendiente_tarjeta" <?= $estado_pago==='pendiente_tarjeta'?'selected':'' ?>>Pendiente (Tarjeta)</option>
          <option value="pendiente_mp" <?= $estado_pago==='pendiente_mp'?'selected':'' ?>>Pendiente (Mercado pago)</option>
          <option value="pendiente_club" <?= $estado_pago==='pendiente_club'?'selected':'' ?>>Pendiente (Presencial)</option>
        </select>
      </div>

      <div class="f-field">
        <label class="f-label">Tipo de reserva</label>
        <select class="f-select" name="tipo_reserva">
          <option value="" <?= $tipo_reserva_f===''?'selected':'' ?>>Todos</option>
          <option value="individual" <?= $tipo_reserva_f==='individual'?'selected':'' ?>>Individual</option>
          <option value="equipo" <?= $tipo_reserva_f==='equipo'?'selected':'' ?>>Equipo</option>
        </select>
      </div>

      <!-- Compactos -->
      <div class="f-field tiny">
        <label class="f-label">Desde</label>
        <input class="f-time" type="time" name="hora_desde" value="<?= htmlspecialchars($hora_desde) ?>">
      </div>

      <div class="f-field tiny">
        <label class="f-label">Hasta</label>
        <input class="f-time" type="time" name="hora_hasta" value="<?= htmlspecialchars($hora_hasta) ?>">
      </div>

      <div class="f-actions"><button class="btn-add" type="submit">Aplicar filtros</button></div>
    </form>

    <table>
      <tr><th>#</th><th>Cancha</th><th>Hora</th><th>Tipo</th><th>Estado reserva</th><th>Método pago</th><th>Estado pago</th><th>Acciones</th></tr>
      <?php if ($reservas->num_rows): while($r=$reservas->fetch_assoc()):
        $hora = substr($r['hora_inicio'],0,5) . '–' . substr($r['hora_fin'],0,5);
        $pagoLbl = labelPago($r['estado_pago'] ?? null, $r['metodo'] ?? null, $r['pago_id'] ?? null);
        $pagoCls = classPago($r['estado_pago'] ?? null, $r['metodo'] ?? null, $r['pago_id'] ?? null);
        $resLbl  = labelReserva((string)$r['estado']); $resCls  = classReserva((string)$r['estado']);
        $metLbl  = labelMetodo($r['metodo'] ?? null);  $metCls  = classMetodo($r['metodo'] ?? null);
        $rowClass = ($r['estado'] === 'pendiente' || (($r['estado_pago'] ?? null) === 'pendiente')) ? 'row-new' : '';
        $isFocus  = ($focus_id > 0 && (int)$r['reserva_id'] === $focus_id);
      ?>
        <tr id="reserva-<?= (int)$r['reserva_id'] ?>" class="<?= $rowClass ?> <?= $isFocus ? 'row-highlight' : '' ?>">
          <td><?= (int)$r['reserva_id'] ?></td>
          <td><?= htmlspecialchars($r['cancha_nombre']) ?></td>
          <td><?= htmlspecialchars($hora) ?></td>
          <td><?= htmlspecialchars(capFirst($r['tipo_reserva'])) ?></td>
          <td><span class="chip <?= $resCls ?>"><?= $resLbl ?></span></td>
          <td class="td-center"><span class="chip <?= $metCls ?>"><?= $metLbl ?></span></td>
          <td><span class="chip <?= $pagoCls ?>"><?= $pagoLbl ?></span></td>
          <td class="actions">
            <?php if (($r['estado_pago'] ?? '') !== 'pagado' && !empty($r['pago_id'])): ?>
              <form action="reservasAction.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Confirmar pago de la reserva #<?= (int)$r['reserva_id'] ?>?');">
                <input type="hidden" name="action" value="mark_paid">
                <input type="hidden" name="pago_id" value="<?= (int)$r['pago_id'] ?>">
                <button class="btn-add" type="submit">Marcar pagado</button>
              </form>
            <?php else: ?> — <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="8" style="text-align:center;">Sin reservas para los filtros seleccionados.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</main>

<script>
(function(){
  const form=document.getElementById('filtersForm'); if(!form) return;
  const submit=()=>{ if(form.requestSubmit) form.requestSubmit(); else form.submit(); };
  form.querySelectorAll('select').forEach(el=>el.addEventListener('change',submit));
  ['input[type="date"]','input[type="time"]'].forEach(sel=>{ form.querySelectorAll(sel).forEach(el=>{
    el.addEventListener('change',submit); el.addEventListener('input',()=>{ if(el.value) submit(); });
  });});

  // Focus + scroll suave si viene ?focus=<id>
  const sp = new URL(location.href).searchParams;
  const focus = parseInt(sp.get('focus')||'0',10);
  if (focus>0) {
    const row = document.getElementById('reserva-'+focus);
    if (row) {
      row.scrollIntoView({behavior:'smooth', block:'center'});
      row.classList.add('row-highlight');
      setTimeout(()=>row.classList.remove('row-highlight'), 4000);
    }
  }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
