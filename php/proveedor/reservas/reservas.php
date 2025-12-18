<?php
/* ========================================================================
 * file: php/proveedor/reservas/reservas.php
 * Listado SOLO de reservas de canchas del proveedor (solo lectura).
 * Columnas: Fecha (dd/mm), Hora (inicio-fin), Cancha, Tipo, Estado reserva,
 *           Método pago, Estado pago, Precio total
 * Filtros: Fecha exacta, Cancha, Estado de pago, Estado de reserva, Tipo
 * ======================================================================== */
include '../includes/header.php';
include '../includes/sidebar.php';
include './../includes/cards.php';
include '../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

/* ==== Helpers ==== */
function ddmm(?string $d): string {
  if (!$d) return '—';
  $t = strtotime($d);
  return $t ? date('d/m', $t) : '—';
}
function capFirst(?string $s): string {
  if(!$s) return '-';
  $s = mb_strtolower($s,'UTF-8');
  $first = mb_strtoupper(mb_substr($s,0,1,'UTF-8'),'UTF-8');
  return $first . mb_substr($s,1,null,'UTF-8');
}
function labelPago(?string $estadoPago, ?string $metodo, ?int $pagoId): string {
  if(!$pagoId) return 'Sin pago';
  if($estadoPago === 'pagado') return 'Pagado';
  if($estadoPago === 'pendiente' && $metodo === 'club') return 'Pendiente (presencial)';
  return ucfirst($estadoPago ?? '-');
}
function classPago(?string $estadoPago, ?string $metodo, ?int $pagoId): string {
  if(!$pagoId) return 'chip-neutral';
  if($estadoPago === 'pagado') return 'chip-pay-ok';
  if($estadoPago === 'pendiente' && $metodo === 'club') return 'chip-pay-warn';
  return 'chip-neutral';
}
function labelReserva(string $estado): string {
  return match ($estado) {
    'confirmada' => 'Confirmada',
    'pendiente'  => 'Pendiente',
    'cancelada'  => 'Cancelada',
    'no_show'    => 'No se presentó',
    default      => ucfirst($estado ?: '-'),
  };
}
function classReserva(string $estado): string {
  return match ($estado) {
    'confirmada' => 'chip-ok',
    'pendiente'  => 'chip-info',
    'cancelada'  => 'chip-bad',
    'no_show'    => 'chip-bad',
    default      => 'chip-neutral',
  };
}
function labelMetodo(?string $metodo): string {
  return match ($metodo) {
    'club'         => 'Presencial',
    'mercado_pago' => 'Mercado Pago',
    'tarjeta'      => 'Tarjeta de crédito',
    null, ''       => '-',
    default        => ucwords(str_replace('_',' ', (string)$metodo)),
  };
}
function classMetodo(?string $metodo): string {
  return match ($metodo) {
    'club'         => 'chip-method-club',
    'mercado_pago' => 'chip-method-mp',
    'tarjeta'      => 'chip-method-card',
    default        => 'chip-neutral',
  };
}

/* ==== Filtros ==== */
$fecha          = $_GET['fecha']          ?? date('Y-m-d');         // exacta
$cancha_filter  = isset($_GET['cancha_id']) ? (int)$_GET['cancha_id'] : 0;
$estado_pago    = $_GET['estado_pago']    ?? 'todos';               // todos|pagado|pendiente_tarjeta|pendiente_mp|pendiente_club
$estado_reserva = $_GET['estado_reserva'] ?? '';                    // pendiente|confirmada|cancelada|no_show|''
$tipo_reserva_f = $_GET['tipo_reserva']   ?? '';                    // individual|equipo|''

/* === Canchas DEL proveedor para el select === */
$canchas = [];
$cq = $conn->prepare("SELECT cancha_id, nombre FROM canchas WHERE proveedor_id = ? ORDER BY nombre ASC");
$cq->bind_param('i', $proveedor_id);
$cq->execute();
$cqres = $cq->get_result();
while ($r = $cqres->fetch_assoc()) $canchas[] = $r;
$cq->close();

/* ==== Reservas + último pago SOLO de sus canchas ==== */
$sql = "
SELECT 
  r.reserva_id, r.cancha_id, r.fecha, r.hora_inicio, r.hora_fin, r.estado,
  r.precio_total, r.tipo_reserva,
  c.nombre AS cancha_nombre,
  lp.pago_id, lp.metodo, lp.estado AS estado_pago
FROM reservas r
JOIN canchas c ON c.cancha_id = r.cancha_id
LEFT JOIN (
  SELECT p.*
  FROM pagos p
  JOIN (
    SELECT reserva_id, MAX(pago_id) AS max_id
    FROM pagos
    GROUP BY reserva_id
  ) t
    ON t.reserva_id = p.reserva_id
   AND t.max_id     = p.pago_id
) lp
  ON lp.reserva_id = r.reserva_id
WHERE c.proveedor_id = ?
";
$params = [$proveedor_id];
$types  = "i";

/* Filtro fecha exacta */
if ($fecha) {
  $sql    .= " AND r.fecha = ? ";
  $params[] = $fecha;
  $types   .= "s";
}

/* Filtro por cancha del proveedor */
if ($cancha_filter > 0) {
  $sql    .= " AND r.cancha_id = ? ";
  $params[] = $cancha_filter;
  $types   .= "i";
}

/* Filtros de estado de pago */
if ($estado_pago === 'pagado') {
  $sql .= " AND lp.estado = 'pagado' ";
} elseif ($estado_pago === 'pendiente_tarjeta') {
  $sql .= " AND lp.metodo = 'tarjeta' AND lp.estado = 'pendiente' ";
} elseif ($estado_pago === 'pendiente_mp') {
  $sql .= " AND lp.metodo = 'mercado_pago' AND lp.estado = 'pendiente' ";
} elseif ($estado_pago === 'pendiente_club') {
  $sql .= " AND lp.metodo = 'club' AND lp.estado = 'pendiente' ";
}

/* Filtro tipo de reserva */
if ($tipo_reserva_f === 'individual' || $tipo_reserva_f === 'equipo') {
  $sql    .= " AND r.tipo_reserva = ? ";
  $params[] = $tipo_reserva_f;
  $types   .= "s";
}

/* Filtro estado de reserva */
if (in_array($estado_reserva, ['pendiente','confirmada','cancelada','no_show'], true)) {
  $sql    .= " AND r.estado = ? ";
  $params[] = $estado_reserva;
  $types   .= "s";
}

$sql .= " ORDER BY r.fecha DESC, r.hora_inicio DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reservas = $stmt->get_result();
$stmt->close();
?>
<div class="section">
  <div class="section-header">
    <h2>Reservas de mis canchas</h2>
  </div>

  <style>
    .filterbar{ display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin:14px 0 16px; }
    .f-field{ display:flex; flex-direction:column; gap:6px; min-width:160px; flex:1 1 160px; }
    .f-label{ font-size:12px; color:#586168; font-weight:700; letter-spacing:.3px; }
    .f-input,.f-select,.f-date{ width:100%; padding:8px 10px; border:1px solid #d6dadd; border-radius:10px; background:#fff; outline:none; transition:border-color .2s,box-shadow .2s; box-shadow:0 1px 0 rgba(0,0,0,.03); }
    .f-input:focus,.f-select:focus,.f-date:focus{ border-color:#1bab9d; box-shadow:0 0 0 3px rgba(27,171,157,.12); }
    .f-actions{ margin-left:auto; display:flex; gap:10px; }
    .f-actions .btn-add{ display:none; }

    /* Chips */
    .chip{ padding:2px 10px; border-radius:999px; font-size:12px; border:1px solid transparent; display:inline-flex; align-items:center; gap:6px; min-width:108px; justify-content:center; }
    .chip-pay-ok{background:#e6f7f4;color:#0b6158;border-color:#b7e6de}
    .chip-pay-warn{background:#fff8e1;color:#9a6700;border-color:#ffe082; text-align: center;}
    .chip-ok{background:#e8f5e9;color:#2e7d32;border-color:#c8e6c9}
    .chip-info{background:#e3f2fd;color:#1a5fb4;border-color:#bbdefb}
    .chip-bad{background:#ffebee;color:#c62828;border-color:#ffcdd2}
    .chip-neutral{background:#eef2f7;color:#415a77;border-color:#d8e0ea}

    table{ width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:12px; overflow:hidden; table-layout:fixed; }
    thead th{ position:sticky; top:0; background:#f8fafc; z-index:1; text-align:left; font-weight:700; padding:10px 12px; font-size:13px; color:#334155; border-bottom:1px solid #e5e7eb; }
    tbody td{ padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
    tbody tr:hover{background:#f7fbfd}
    .truncate{ display:block; max-width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .td-center{text-align:center}
    .money{white-space:nowrap}

    /* Anchos */
    .col-fecha   { width:70px; }
    .col-hora    { width:120px; }
    .col-cancha  { width:200px; }
    .col-tipo    { width:90px; text-align:center; }
    .col-eres    { width:140px; text-align:center; }
    .col-met     { width:130px; text-align:center; }
    .col-epago   { width:130px; text-align:center; }
    .col-precio  { width:110px; text-align:right; }
  </style>

  <!-- Filtros -->
  <form method="GET" class="filterbar" id="filtersForm">
    <div class="f-field">
      <label class="f-label">Fecha exacta</label>
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
        <option value="todos"             <?= $estado_pago==='todos'?'selected':'' ?>>Todos</option>
        <option value="pagado"            <?= $estado_pago==='pagado'?'selected':'' ?>>Pagado</option>
        <option value="pendiente_tarjeta" <?= $estado_pago==='pendiente_tarjeta'?'selected':'' ?>>Pendiente (Tarjeta)</option>
        <option value="pendiente_mp"      <?= $estado_pago==='pendiente_mp'?'selected':'' ?>>Pendiente (Mercado Pago)</option>
        <option value="pendiente_club"    <?= $estado_pago==='pendiente_club'?'selected':'' ?>>Pendiente (Presencial)</option>
      </select>
    </div>

    <div class="f-field">
      <label class="f-label">Estado de reserva</label>
      <select class="f-select" name="estado_reserva">
        <option value=""           <?= $estado_reserva===''?'selected':'' ?>>Todos</option>
        <option value="pendiente"  <?= $estado_reserva==='pendiente'?'selected':'' ?>>Pendiente</option>
        <option value="confirmada" <?= $estado_reserva==='confirmada'?'selected':'' ?>>Confirmada</option>
        <option value="cancelada"  <?= $estado_reserva==='cancelada'?'selected':'' ?>>Cancelada</option>
        <option value="no_show"    <?= $estado_reserva==='no_show'?'selected':'' ?>>No se presentó</option>
      </select>
    </div>

    <div class="f-field">
      <label class="f-label">Tipo de reserva</label>
      <select class="f-select" name="tipo_reserva">
        <option value=""           <?= $tipo_reserva_f===''?'selected':'' ?>>Todos</option>
        <option value="individual" <?= $tipo_reserva_f==='individual'?'selected':'' ?>>Individual</option>
        <option value="equipo"     <?= $tipo_reserva_f==='equipo'?'selected':'' ?>>Equipo</option>
      </select>
    </div>

    <div class="f-actions">
      <button class="btn-add" type="submit">Aplicar filtros</button>
    </div>
  </form>

  <!-- Tabla -->
  <table>
    <thead>
      <tr>
        <th class="col-fecha">Fecha</th>
        <th class="col-hora">Hora</th>
        <th class="col-cancha">Cancha</th>
        <th class="col-tipo">Tipo</th>
        <th class="col-eres">Estado reserva</th>
        <th class="col-met">Método pago</th>
        <th class="col-epago">Estado pago</th>
        <th class="col-precio">Precio total</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($reservas && $reservas->num_rows): ?>
        <?php while($r = $reservas->fetch_assoc()):
          $hora    = substr($r['hora_inicio'],0,5) . '–' . substr($r['hora_fin'],0,5);
          $pagoLbl = labelPago($r['estado_pago'] ?? null, $r['metodo'] ?? null, $r['pago_id'] ?? null);
          $pagoCls = classPago($r['estado_pago'] ?? null, $r['metodo'] ?? null, $r['pago_id'] ?? null);
          $resLbl  = labelReserva((string)$r['estado']);
          $resCls  = classReserva((string)$r['estado']);
          $metLbl  = labelMetodo($r['metodo'] ?? null);
          $metCls  = classMetodo($r['metodo'] ?? null);
        ?>
          <tr>
            <td class="col-fecha"><?= htmlspecialchars(ddmm($r['fecha'])) ?></td>
            <td class="col-hora"><?= htmlspecialchars($hora) ?></td>
            <td class="col-cancha"><span class="truncate"><?= htmlspecialchars($r['cancha_nombre']) ?></span></td>
            <td class="col-tipo td-center"><?= htmlspecialchars(capFirst($r['tipo_reserva'])) ?></td>
            <td class="col-eres td-center"><span class="chip <?= $resCls ?>"><?= $resLbl ?></span></td>
            <td class="col-met td-center"><span class="chip <?= $metCls ?>"><?= $metLbl ?></span></td>
            <td class="col-epago td-center"><span class="chip <?= $pagoCls ?>"><?= $pagoLbl ?></span></td>
            <td class="col-precio money" style="text-align:right;">$<?= number_format((float)$r['precio_total'], 2, ',', '.') ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">Sin reservas para los filtros seleccionados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
/* Autosubmit de filtros */
(function(){
  const form = document.getElementById('filtersForm');
  if(!form) return;
  const submit = () => { if(form.requestSubmit) form.requestSubmit(); else form.submit(); };

  form.querySelectorAll('select').forEach(el => el.addEventListener('change', submit));
  form.querySelectorAll('input[type="date"]').forEach(el => {
    el.addEventListener('change', submit);
    el.addEventListener('input', () => { if(el.value) submit(); });
  });
})();
</script>

<?php include '../includes/footer.php'; ?>
