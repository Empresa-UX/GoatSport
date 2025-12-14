<?php
/* =========================================================================
 * file: admin/pagos/pagos.php
 * Listado de pagos (solo lectura para admin)
 * - Columnas: ID, Reserva, Jugador, Proveedor/Cancha, Método, Monto, Estado, Fecha
 * - Filtros: búsqueda, proveedor, método, estado, fecha (día/mes)
 * - Sin acciones de edición / eliminación
 * - Ancho de columnas configurable desde CSS (.col-xxx)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';

/* === Helper === */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ddmm(?string $dt): string {
  if (!$dt) return '—';
  $t = strtotime($dt);
  return $t ? date('d/m',$t) : '—';
}

/* === DATA === */
$sql = "
  SELECT 
    p.pago_id,
    p.reserva_id,
    p.monto,
    p.metodo,
    p.estado,
    p.fecha_pago,
    u.user_id  AS jugador_id,
    u.nombre   AS jugador_nombre,
    c.nombre   AS cancha_nombre,
    prov.user_id AS proveedor_id,
    prov.nombre  AS proveedor_nombre
  FROM pagos p
  INNER JOIN usuarios u    ON p.jugador_id = u.user_id
  LEFT JOIN reservas r     ON p.reserva_id = r.reserva_id
  LEFT JOIN canchas c      ON r.cancha_id = c.cancha_id
  LEFT JOIN usuarios prov  ON c.proveedor_id = prov.user_id
  ORDER BY p.fecha_pago DESC, p.pago_id DESC
";
$res  = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

/* === Listas para selects === */
$proveedores = [];
foreach ($rows as $r) {
  if (!empty($r['proveedor_id']) && !empty($r['proveedor_nombre'])) {
    $proveedores[$r['proveedor_id'].'|'.$r['proveedor_nombre']] = true;
  }
}
ksort($proveedores, SORT_NATURAL | SORT_FLAG_CASE);
?>
<div class="section">
  <div class="section-header">
    <h2 style="margin:0;">Pagos</h2>
  </div>

  <style>
    :root{
      --brand:#0f766e;
    }

    /* ---- Filtros ---- */
    .fbar{
      display:grid;
      grid-template-columns:
        minmax(260px,1fr)    /* Buscar */
        minmax(200px,220px)  /* Proveedor */
        minmax(160px,180px)  /* Método */
        minmax(160px,180px)  /* Estado */
        minmax(110px,130px)  /* Día */
        minmax(110px,130px); /* Mes */
      gap:12px;
      align-items:end;
      background:#fff;
      padding:14px 16px;
      border-radius:12px;
      box-shadow:0 4px 12px rgba(0,0,0,.08);
      margin-bottom:12px;
    }
    @media (max-width:1100px){
      .fbar{ grid-template-columns: repeat(2,minmax(220px,1fr)); }
    }
    @media (max-width:640px){
      .fbar{ grid-template-columns: 1fr; }
    }
    .f{
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    .f label{
      font-size:12px;
      color:#586168;
      font-weight:700;
    }
    .f input[type="text"], .f select{
      padding:9px 10px;
      border:1px solid #d6dadd;
      border-radius:10px;
      background:#fff;
      outline:none;
    }

    /* ---- Tabla ---- */
    table{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      background:#fff;
      border-radius:12px;
      overflow:hidden;
      table-layout:fixed;
    }
    thead th{
      position:sticky;
      top:0;
      background:#f8fafc;
      z-index:1;
      text-align:left;
      font-weight:700;
      padding:10px 12px;
      font-size:13px;
      color:#334155;
      border-bottom:1px solid #e5e7eb;
    }
    tbody td{
      padding:10px 12px;
      border-bottom:1px solid #f1f5f9;
      vertical-align:top;
    }
    tbody tr:hover{ background:#f7fbfd; }
    .truncate{
      display:block;
      max-width:100%;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .sub{
      font-size:12px;
      color:#64748b;
    }

    /* === Anchos de columnas (podés tocarlos a gusto) === */
    .col-id       { width:50px; }
    .col-reserva  { width:80px; }
    .col-jugador  { width:180px; }
    .col-provcan  { width:240px; }
    .col-metodo   { width:120px; text-align: center;}
    .col-monto    { width:110px; }
    .col-estado   { width:120px; text-align: center;}
    .col-fecha    { width:80px; }

    .money{ white-space:nowrap; }

    /* ---- Pills método ---- */
    .pill-metodo {
      display:inline-block;
      padding:3px 8px;
      border-radius:999px;
      font-size:12px;
      font-weight:bold;
    }
    .pill-metodo.club {
      background:#f1f5f9;
      color:#1e293b;
    }
    .pill-metodo.mercado_pago {
      background:#e0f2fe;
      color:#0369a1;
    }
    .pill-metodo.tarjeta {
      background:#ecfdf5;
      color:#047857;
    }

    /* ---- Pills estado ---- */
    .status-pill{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:3px 9px;
      border-radius:999px;
      font-size:12px;
      font-weight:600;
      border:1px solid transparent;
      white-space:nowrap;
    }
    .st-pagado{
      background:#e6f7f4;
      border-color:#c8efe8;
      color:#0f766e;
    }
    .st-pendiente{
      background:#fff7e6;
      border-color:#ffe1b5;
      color:#92400e;
    }
    .st-cancelado{
      background:#fde8e8;
      border-color:#f8c9c9;
      color:#7f1d1d;
    }
  </style>

  <!-- Filtros -->
  <div class="fbar" id="filters">
    <div class="f">
      <label>Buscar</label>
      <input type="text" id="f-q" placeholder="Jugador / reserva / cancha">
    </div>
    <div class="f">
      <label>Proveedor</label>
      <select id="f-prov">
        <option value="">Todos</option>
        <?php foreach(array_keys($proveedores) as $key):
          [$pid,$pname] = explode('|',$key,2); ?>
          <option value="<?= (int)$pid ?>"><?= h($pname) ?></option>
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
      <label>Estado</label>
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
  <table id="tablaPagos">
    <thead>
      <tr>
        <th class="col-id">ID</th>
        <th class="col-reserva">Reserva</th>
        <th class="col-jugador">Jugador</th>
        <th class="col-provcan">Proveedor / Cancha</th>
        <th class="col-metodo">Método</th>
        <th class="col-monto">Monto</th>
        <th class="col-estado">Estado</th>
        <th class="col-fecha">Fecha</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="8" style="text-align:center;">No hay pagos registrados</td></tr>
      <?php else: foreach($rows as $r):
        $metodo = $r['metodo'];
        $metodoClass = 'club';
        if ($metodo === 'mercado_pago') $metodoClass = 'mercado_pago';
        if ($metodo === 'tarjeta')      $metodoClass = 'tarjeta';

        $estado = $r['estado'] ?: 'pendiente';
        $stClass = 'st-pendiente';
        if ($estado === 'pagado')   $stClass = 'st-pagado';
        elseif ($estado === 'cancelado') $stClass = 'st-cancelado';

        $fecha = ddmm($r['fecha_pago'] ?? null);
        $t     = $r['fecha_pago'] ? strtotime($r['fecha_pago']) : false;
        $dia   = $t ? (int)date('j',$t) : '';
        $mes   = $t ? (int)date('n',$t) : '';

        $texto = strtolower(
          ($r['jugador_nombre'] ?? '') . ' ' .
          ($r['reserva_id'] ?? '') . ' ' .
          ($r['cancha_nombre'] ?? '')
        );
      ?>
        <tr
          data-text="<?= h($texto) ?>"
          data-prov-id="<?= (int)($r['proveedor_id'] ?? 0) ?>"
          data-metodo="<?= h($metodo) ?>"
          data-estado="<?= h($estado) ?>"
          data-dia="<?= $dia ?>"
          data-mes="<?= $mes ?>"
        >
          <td class="col-id"><?= (int)$r['pago_id'] ?></td>
          <td class="col-reserva">#<?= (int)$r['reserva_id'] ?></td>
          <td class="col-jugador">
            <div class="truncate"><strong><?= h($r['jugador_nombre']) ?></strong></div>
          </td>
          <td class="col-provcan">
            <?php if (!empty($r['proveedor_nombre'])): ?>
              <div class="truncate"><strong><?= h($r['proveedor_nombre']) ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($r['cancha_nombre'])): ?>
              <div class="sub truncate"><?= h($r['cancha_nombre']) ?></div>
            <?php endif; ?>
          </td>
          <td class="col-metodo">
            <span class="pill-metodo <?= $metodoClass ?>">
              <?= ucfirst(str_replace('_',' ', $metodo)) ?>
            </span>
          </td>
          <td class="col-monto money">$<?= number_format((float)$r['monto'], 2, ',', '.') ?></td>
          <td class="col-estado">
            <span class="status-pill <?= $stClass ?>"><?= ucfirst($estado) ?></span>
          </td>
          <td class="col-fecha"><?= h($fecha) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
/* ==== Filtros pagos ==== */
(function(){
  const $  = (s,root=document)=>root.querySelector(s);
  const $$ = (s,root=document)=>Array.from(root.querySelectorAll(s));
  const rows = $$('#tablaPagos tbody tr');
  const norm = s => (s||'').toString().toLowerCase();

  function apply(){
    const q      = norm($('#f-q')?.value);
    const provId = $('#f-prov')?.value || '';
    const metodo = $('#f-metodo')?.value || '';
    const estado = $('#f-estado')?.value || '';
    const d      = $('#f-dia')?.value || '';
    const m      = $('#f-mes')?.value || '';

    rows.forEach(tr=>{
      const vText   = tr.dataset.text   || '';
      const vProv   = tr.dataset.provId || tr.getAttribute('data-prov-id') || '';
      const vMet    = tr.dataset.metodo || tr.getAttribute('data-metodo')  || '';
      const vEst    = tr.dataset.estado || tr.getAttribute('data-estado')  || '';
      const vDia    = tr.dataset.dia    || tr.getAttribute('data-dia')      || '';
      const vMes    = tr.dataset.mes    || tr.getAttribute('data-mes')      || '';

      let show = true;
      show = show && (q === ''      || vText.includes(q));
      show = show && (provId === '' || String(vProv) === String(provId));
      show = show && (metodo === '' || vMet === metodo);
      show = show && (estado === '' || vEst === estado);
      show = show && (d === ''      || String(vDia) === String(d));
      show = show && (m === ''      || String(vMes) === String(m));

      tr.style.display = show ? '' : 'none';
    });
  }

  const listen = (id,ev='change') => {
    const el = document.querySelector(id);
    if (!el) return;
    el.addEventListener(ev, apply);
  };

  listen('#f-q','input');
  listen('#f-prov');
  listen('#f-metodo');
  listen('#f-estado');
  listen('#f-dia');
  listen('#f-mes');

  apply();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
