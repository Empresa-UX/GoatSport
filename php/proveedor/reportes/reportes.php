<?php
/* =========================================================================
 * file: php/proveedor/reportes/reportes.php
 * Reportes (SOLO LECTURA) para PROVEEDOR
 * - Columnas: Fecha (dd/mm), Título del reporte, Cancha afectada, Estado
 * - Filtros: Buscar por título, Fecha (Día), Fecha (Mes), Estado
 * - Sin "tipo de falla" y sin edición de estado
 * - Solo reportes asociados a canchas del proveedor (directo o via reserva)
 * ========================================================================= */
include '../includes/header.php';
include '../includes/sidebar.php';
include './../includes/cards.php';
include '../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header("Location: ../../login.php"); exit();
}

$proveedor_id = (int)$_SESSION['usuario_id'];

/* === DATA === */
$sql = "
  SELECT 
    r.id,
    r.nombre_reporte,
    r.descripcion,
    r.fecha_reporte,
    r.estado,
    u.nombre AS usuario_nombre,
    u.email  AS usuario_email,
    c.nombre  AS cancha_directa,
    c2.nombre AS cancha_reserva
  FROM reportes r
  INNER JOIN usuarios u       ON u.user_id = r.usuario_id
  LEFT  JOIN canchas c        ON c.cancha_id   = r.cancha_id
  LEFT  JOIN reservas res     ON res.reserva_id = r.reserva_id
  LEFT  JOIN canchas c2       ON c2.cancha_id  = res.cancha_id
  WHERE (c.proveedor_id = ? OR c2.proveedor_id = ?)
  ORDER BY r.fecha_reporte DESC, r.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $proveedor_id, $proveedor_id);
$stmt->execute();
$res  = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

/* === AUX === */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ddmm(?string $d): string { if(!$d) return '—'; $t=strtotime($d); return $t?date('d/m',$t):'—'; }
?>
<div class="section">
  <div class="section-header">
    <h2 style="margin:0;">Reportes de mis canchas</h2>
  </div>

  <style>
    :root{ --brand:#0f766e; }

    /* ---- Filtros (mismo estilo admin) ---- */
    .fbar{
      display:grid;
      grid-template-columns:
        minmax(260px,1fr)    /* texto */
        minmax(120px,140px)  /* día */
        minmax(120px,140px)  /* mes */
        minmax(160px,180px); /* estado */
      gap:12px; align-items:end; background:#fff; padding:14px 16px;
      border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08); margin-bottom:12px;
    }
    @media (max-width:1100px){ .fbar{ grid-template-columns: repeat(2,minmax(220px,1fr)); } }
    @media (max-width:640px){ .fbar{ grid-template-columns: 1fr; } }
    .f{ display:flex; flex-direction:column; gap:6px; }
    .f label{ font-size:12px; color:#586168; font-weight:700; }
    .f input[type="text"], .f select{ padding:9px 10px; border:1px solid #d6dadd; border-radius:10px; background:#fff; outline:none; }

    /* ---- Tabla (mismo look admin) ---- */
    table{ width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:12px; overflow:hidden; table-layout:fixed; }
    thead th{ position:sticky; top:0; background:#f8fafc; z-index:1; text-align:left; font-weight:700; padding:10px 12px; font-size:13px; color:#334155; border-bottom:1px solid #e5e7eb; }
    tbody td{ padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
    tbody tr:hover{ background:#f7fbfd; }

    .col-fecha  { width:70px; }
    .col-titulo { width:600px; }   /* envolvente */
    .col-cancha { width:120px; }
    .col-estado { width:70px; text-align:center; }

    .title-text{
      white-space: normal;
      overflow-wrap: break-word;
      word-break: break-word;
    }
    .sub{
      font-size:13px;
      color:#64748b;
      white-space: normal;
      overflow-wrap: break-word;
    }
    .truncate{display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}

    /* Estado (solo lectura) */
    .status-pill{ display:inline-flex; align-items:center; justify-content:center; padding:3px 9px; border-radius:999px; font-size:12px; font-weight:600; border:1px solid transparent; white-space:nowrap; }
    .st-pend{ background:#fff7e6; border-color:#ffe1b5; color:#92400e; }
    .st-res { background:#e6f7f4; border-color:#c8efe8; color:#0f766e; }
  </style>

  <!-- Filtros -->
  <div class="fbar" id="filters">
    <div class="f">
      <label>Buscar por título</label>
      <input type="text" id="f-q" placeholder="Ej: Luz, piso mojado, red rota">
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
    <div class="f">
      <label>Estado</label>
      <select id="f-estado">
        <option value="">Todos</option>
        <option value="Pendiente">Pendiente</option>
        <option value="Resuelto">Resuelto</option>
      </select>
    </div>
  </div>

  <!-- Tabla -->
  <table id="tablaReportesProv">
    <thead>
      <tr>
        <th class="col-fecha">Fecha</th>
        <th class="col-titulo">Título del reporte</th>
        <th class="col-cancha">Cancha afectada</th>
        <th class="col-estado">Estado</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="4" style="text-align:center;">No hay reportes para tus canchas.</td></tr>
      <?php else: foreach($rows as $r):
        $fecha = ddmm($r['fecha_reporte']);
        $t     = strtotime($r['fecha_reporte']);
        $dia   = $t ? (int)date('j',$t) : '';
        $mes   = $t ? (int)date('n',$t) : '';
        $estado  = $r['estado'] ?: 'Pendiente';
        $canchaNombre = $r['cancha_directa'] ?: $r['cancha_reserva'] ?: '—';
      ?>
        <tr
          data-title="<?= h(mb_strtolower($r['nombre_reporte'] ?? '','UTF-8')) ?>"
          data-dia="<?= $dia ?>"
          data-mes="<?= $mes ?>"
          data-estado="<?= h($estado) ?>"
        >
          <td class="col-fecha"><?= h($fecha) ?></td>
          <td class="col-titulo">
            <div class="title-text"><strong><?= h($r['nombre_reporte']) ?></strong></div>
            <?php if (!empty($r['descripcion'])): ?>
        <div class="sub"><?= h($r['descripcion']) ?></div>
            <?php endif; ?>
          </td>
          <td class="col-cancha"><span class="truncate"><?= h($canchaNombre) ?></span></td>
          <td class="col-estado">
            <?php if ($estado === 'Resuelto'): ?>
              <span class="status-pill st-res">Resuelto</span>
            <?php else: ?>
              <span class="status-pill st-pend">Pendiente</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
/* Filtros instantáneos: título, día, mes, estado */
(function(){
  const $  = (s,root=document)=>root.querySelector(s);
  const $$ = (s,root=document)=>Array.from(root.querySelectorAll(s));
  const rows = $$('#tablaReportesProv tbody tr');
  const norm = s => (s||'').toString().toLowerCase();

  function apply(){
    const q   = norm($('#f-q')?.value);
    const d   = $('#f-dia')?.value || '';
    const m   = $('#f-mes')?.value || '';
    const est = $('#f-estado')?.value || '';

    rows.forEach(tr=>{
      const vTitle  = tr.dataset.title || '';
      const vDia    = tr.dataset.dia  || '';
      const vMes    = tr.dataset.mes  || '';
      const vEstado = tr.dataset.estado || '';

      let show = true;
      show = show && (q === ''   || vTitle.includes(q));
      show = show && (d === ''   || String(vDia) === String(d));
      show = show && (m === ''   || String(vMes) === String(m));
      show = show && (est === '' || vEstado === est);

      tr.style.display = show ? '' : 'none';
    });
  }

  const listen = (id,ev='change') => { const el=$(id); if(el) el.addEventListener(ev, apply); };
  listen('#f-q','input'); listen('#f-dia'); listen('#f-mes'); listen('#f-estado');
  apply();
})();
</script>

<?php include '../includes/footer.php'; ?>
