<?php
/* =========================================================================
 * file: php/proveedor/pagos/pagos.php
 * Pagos (SOLO LECTURA) para PROVEEDOR
 * - Columnas: Fecha, Cancha, Método, Estado, Monto
 * - Filtros: Cancha, Método, Estado, Fecha (Día), Fecha (Mes)
 * - Mismo estilo que admin
 * ========================================================================= */
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

/* === Helpers === */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ddmm(?string $dt): string {
  if (!$dt) return '—';
  $t = strtotime($dt);
  return $t ? date('d/m', $t) : '—';
}

/* === Catálogo de canchas del proveedor (para filtro) === */
$canchas = [];
$qc = $conn->prepare("SELECT cancha_id, nombre FROM canchas WHERE proveedor_id=? ORDER BY nombre ASC");
$qc->bind_param('i', $proveedor_id);
$qc->execute();
$rc = $qc->get_result();
if ($rc) while ($row = $rc->fetch_assoc()) $canchas[] = $row;
$qc->close();

/* === DATA: pagos de reservas de sus canchas === */
$sql = "
  SELECT 
    p.pago_id,
    p.reserva_id,
    p.monto,
    p.metodo,
    p.estado,
    p.fecha_pago,
    c.cancha_id,
    c.nombre AS cancha_nombre
  FROM pagos p
  INNER JOIN reservas r ON p.reserva_id = r.reserva_id
  INNER JOIN canchas  c ON r.cancha_id = c.cancha_id
  WHERE c.proveedor_id = ?
  ORDER BY p.fecha_pago DESC, p.pago_id DESC
";
$st = $conn->prepare($sql);
$st->bind_param('i', $proveedor_id);
$st->execute();
$res = $st->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$st->close();
?>
<div class="section">
  <div class="section-header">
    <h2 style="margin:0;">Pagos</h2>
  </div>

  <style>
    :root{ --brand:#0f766e; }

    /* ---- Filtros ---- */
    .fbar{
      display:grid;
      grid-template-columns:
        minmax(220px,260px)  /* Cancha */
        minmax(160px,180px)  /* Método */
        minmax(160px,180px)  /* Estado */
        minmax(110px,130px)  /* Día */
        minmax(110px,130px); /* Mes */
      gap:12px; align-items:end; background:#fff; padding:14px 16px;
      border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08); margin-bottom:12px;
    }
    @media (max-width:980px){ .fbar{ grid-template-columns: repeat(2,minmax(220px,1fr)); } }
    @media (max-width:560px){ .fbar{ grid-template-columns: 1fr; } }
    .f{ display:flex; flex-direction:column; gap:6px; }
    .f label{ font-size:12px; color:#586168; font-weight:700; }
    .f select{ padding:9px 10px; border:1px solid #d6dadd; border-radius:10px; background:#fff; outline:none; }

    /* ---- Tabla ---- */
    table{ width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:12px; overflow:hidden; table-layout:fixed; }
    thead th{ position:sticky; top:0; background:#f8fafc; z-index:1; text-align:left; font-weight:700; padding:10px 12px; font-size:13px; color:#334155; border-bottom:1px solid #e5e7eb; }
    tbody td{ padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
    tbody tr:hover{ background:#f7fbfd; }
    .truncate{ display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .money{ white-space:nowrap; }

    /* ---- Anchos de columnas ---- */
    .col-fecha  { width:80px; }
    .col-cancha { width:260px; }
    .col-metodo { width:140px; text-align:center; }
    .col-estado { width:130px; text-align:center; }
    .col-monto  { width:120px; }

    /* ---- Pills método ---- */
    .pill-metodo{ display:inline-block; padding:3px 8px; border-radius:999px; font-size:12px; font-weight:700; }
    .pill-metodo.club{ background:#f1f5f9; color:#1e293b; }
    .pill-metodo.mercado_pago{ background:#e0f2fe; color:#0369a1; }
    .pill-metodo.tarjeta{ background:#ecfdf5; color:#047857; }

    /* ---- Pills estado ---- */
    .status-pill{ display:inline-flex; align-items:center; justify-content:center; padding:3px 9px; border-radius:999px; font-size:12px; font-weight:600; border:1px solid transparent; white-space:nowrap; }
    .st-pagado{ background:#e6f7f4; border-color:#c8efe8; color:#0f766e; }
    .st-pendiente{ background:#fff7e6; border-color:#ffe1b5; color:#92400e; }
    .st-cancelado{ background:#fde8e8; border-color:#f8c9c9; color:#7f1d1d; }
  </style>

  <!-- Filtros -->
  <div class="fbar" id="filters">
    <div class="f">
      <label>Cancha</label>
      <select id="f-cancha">
        <option value="">Todas</option>
        <?php foreach($canchas as $c): ?>
          <option value="<?= (int)$c['cancha_id'] ?>"><?= h($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="f">
      <label>Método</label>
      <select id="f-metodo">
        <option value="">Todos</option>
        <option value="club">Club</option>
        <option value="mercado_pago">Mercado Pago</option>
        <option value="tarjeta">Tarjeta</option>
      </select>
    </div>
    <div class="f">
      <label>Estado de pago</label>
      <select id="f-estado">
        <option value="">Todos</option>
        <option value="pendiente">Pendiente</option>
        <option value="pagado">Pagado</option>
        <option value="cancelado">Cancelado</option>
      </select>
    </div>
    <div class="f">
      <label>Fecha (Día)</label>
      <select id="f-dia">
        <option value="">Todos</option>
        <?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?>
      </select>
    </div>
    <div class="f">
      <label>Fecha (Mes)</label>
      <select id="f-mes">
        <option value="">Todos</option>
        <?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?>
      </select>
    </div>
  </div>

  <!-- Tabla -->
  <table id="tablaPagosProv">
    <thead>
      <tr>
        <th class="col-fecha">Fecha</th>
        <th class="col-cancha">Cancha</th>
        <th class="col-metodo">Método</th>
        <th class="col-estado">Estado</th>
        <th class="col-monto">Monto</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" style="text-align:center;">No hay pagos registrados para tus canchas.</td></tr>
      <?php else: foreach($rows as $r):
        $metodo = (string)($r['metodo'] ?? '');
        $metodoClass = ($metodo === 'mercado_pago') ? 'mercado_pago' : (($metodo === 'tarjeta') ? 'tarjeta' : 'club');

        $estado = (string)($r['estado'] ?? 'pendiente');
        $stClass = ($estado === 'pagado') ? 'st-pagado' : (($estado === 'cancelado') ? 'st-cancelado' : 'st-pendiente');

        $fecha = ddmm($r['fecha_pago'] ?? null);
        $t     = !empty($r['fecha_pago']) ? strtotime($r['fecha_pago']) : false;
        $dia   = $t ? (int)date('j',$t) : '';
        $mes   = $t ? (int)date('n',$t) : '';
      ?>
        <tr
          data-cancha-id="<?= (int)$r['cancha_id'] ?>"
          data-metodo="<?= h($metodo) ?>"
          data-estado="<?= h($estado) ?>"
          data-dia="<?= $dia ?>"
          data-mes="<?= $mes ?>"
        >
          <td class="col-fecha"><?= h($fecha) ?></td>
          <td class="col-cancha"><span class="truncate"><?= h($r['cancha_nombre'] ?? '—') ?></span></td>
          <td class="col-metodo">
            <span class="pill-metodo <?= $metodoClass ?>"><?= ucfirst(str_replace('_',' ', $metodo)) ?></span>
          </td>
          <td class="col-estado"><span class="status-pill <?= $stClass ?>"><?= ucfirst($estado) ?></span></td>
          <td class="col-monto money">$<?= number_format((float)$r['monto'], 2, ',', '.') ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
/* ==== Filtros (instantáneos) ==== */
(function(){
  const $  = (s,root=document)=>root.querySelector(s);
  const $$ = (s,root=document)=>Array.from(root.querySelectorAll(s));
  const rows = $$('#tablaPagosProv tbody tr');

  function apply(){
    const cancha = $('#f-cancha')?.value || '';
    const metodo = $('#f-metodo')?.value || '';
    const estado = $('#f-estado')?.value || '';
    const d      = $('#f-dia')?.value     || '';
    const m      = $('#f-mes')?.value     || '';

    rows.forEach(tr=>{
      const vCancha = tr.dataset.canchaId || tr.getAttribute('data-cancha-id') || '';
      const vMet    = tr.dataset.metodo   || tr.getAttribute('data-metodo')    || '';
      const vEst    = tr.dataset.estado   || tr.getAttribute('data-estado')    || '';
      const vDia    = tr.dataset.dia      || tr.getAttribute('data-dia')       || '';
      const vMes    = tr.dataset.mes      || tr.getAttribute('data-mes')       || '';

      let show = true;
      show = show && (cancha === '' || String(vCancha) === String(cancha));
      show = show && (metodo === '' || vMet === metodo);
      show = show && (estado === '' || vEst === estado);
      show = show && (d === ''      || String(vDia) === String(d));
      show = show && (m === ''      || String(vMes) === String(m));

      tr.style.display = show ? '' : 'none';
    });
  }

  ['#f-cancha','#f-metodo','#f-estado','#f-dia','#f-mes'].forEach(id=>{
    const el = document.querySelector(id);
    if (el) el.addEventListener('change', apply);
  });

  apply();
})();
</script>

<?php include '../includes/footer.php'; ?>
