<?php
/* =========================================================================
 * file: admin/reportes/reportes.php
 * Listado de reportes de SISTEMA (tipo_falla='sistema')
 * - Columnas: ID, Título, Descripción, Usuario, Fecha (dd/mm), Estado
 * - Filtros: nombre, usuario, fecha (día/mes), estado
 * - Admin puede cambiar Pendiente -> Resuelto con dropdown en la columna Estado
 *   (si ya está Resuelto, NO se puede modificar)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';

/* === DATA: solo tipo_falla = 'sistema' === */
$sql = "
  SELECT 
    r.id,
    r.nombre_reporte,
    r.descripcion,
    r.usuario_id,
    r.fecha_reporte,
    r.estado,
    u.nombre AS usuario_nombre,
    u.email  AS usuario_email
  FROM reportes r
  INNER JOIN usuarios u ON u.user_id = r.usuario_id
  WHERE r.tipo_falla = 'sistema'
  ORDER BY r.fecha_reporte DESC, r.id DESC
";
$res  = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

/* === AUX para filtros === */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ddmm(?string $d): string {
  if (!$d) return '—';
  $t = strtotime($d);
  return $t ? date('d/m',$t) : '—';
}

$usuarios = [];
foreach ($rows as $r) {
  $usuarios[$r['usuario_id'].'|'.$r['usuario_nombre']] = true;
}
ksort($usuarios, SORT_NATURAL|SORT_FLAG_CASE);
?>
<div class="section">
  <div class="section-header">
    <h2 style="margin:0;">Reportes</h2>
  </div>

  <style>
    :root{
      --brand:#0f766e;
    }

    /* ---- Filtros ---- */
    .fbar{
      display:grid;
      grid-template-columns:
        minmax(260px,1fr)    /* nombre */
        minmax(220px,260px)  /* usuario */
        minmax(120px,140px)  /* día */
        minmax(120px,140px)  /* mes */
        minmax(160px,180px); /* estado */
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
    .f{ display:flex; flex-direction:column; gap:6px; }
    .f label{ font-size:12px; color:#586168; font-weight:700; }
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

    /* === Anchos columnas (modificalos a gusto) === */
    .col-id     { width:30px; }
    .col-titulo { width:200px; }
    .col-desc   { width:400px; }  /* cambiá este valor si querés más/menos espacio */
    .col-user   { width:160px; }
    .col-fecha  { width:50px; }
    .col-estado { width:100px; }

    /* Descripción: sin "..." y con salto de línea */
    .desc-text{
      white-space:normal;
      word-wrap:break-word;
      font-size:13px;
      color:#0f172a;
    }

    /* ---- Estado pill estático (Resuelto) ---- */
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
    .st-resuelto{
      background:#e6f7f4;
      border-color:#c8efe8;
      color:#0f766e;
    }

    /* ---- Dropdown para Pendiente ---- */
    .estado-dropdown{
      position:relative;
      display:inline-block;
    }
    .estado-btn{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:4px 10px;
      border-radius:999px;
      border:1px solid #ffe1b5;
      background:#fff7e6;
      color:#92400e;
      font-size:12px;
      font-weight:600;
      cursor:pointer;
    }
    .estado-btn:hover{
      filter:brightness(0.98);
    }
    .estado-chev{
      font-size:15px;
      line-height:1;
    }

    .estado-menu{
      position:absolute;
      top:100%;
      left:0;
      margin-top:4px;
      background:#fff;
      border-radius:8px;
      border:1px solid #e2e8f0;
      box-shadow:0 6px 16px rgba(15,23,42,.18);
      min-width:130px;
      padding:4px;
      z-index:10;
      display:none;
    }
    .estado-dropdown.open .estado-menu{
      display:block;
    }
    .estado-menu button{
      width:100%;
      text-align:left;
      padding:6px 8px;
      border:none;
      background:transparent;
      font-size:13px;
      cursor:pointer;
      border-radius:6px;
    }
    .estado-menu button:hover{
      background:#f1f5f9;
    }
  </style>

  <!-- Filtros -->
  <div class="fbar" id="filters">
    <div class="f">
      <label>Por nombre</label>
      <input type="text" id="f-q" placeholder="Título o descripción">
    </div>
    <div class="f">
      <label>Usuario</label>
      <select id="f-user">
        <option value="">Todos</option>
        <?php foreach(array_keys($usuarios) as $key):
          [$uid,$uname] = explode('|',$key,2); ?>
          <option value="<?= (int)$uid ?>"><?= h($uname) ?></option>
        <?php endforeach; ?>
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
  <table id="tablaReportes">
    <thead>
      <tr>
        <th class="col-id">ID</th>
        <th class="col-titulo">Título del reporte</th>
        <th class="col-desc">Descripción</th>
        <th class="col-user">Usuario</th>
        <th class="col-fecha">Fecha</th>
        <th class="col-estado">Estado actual</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" style="text-align:center;">No hay reportes registrados</td></tr>
      <?php else: foreach($rows as $r):
        $fecha = ddmm($r['fecha_reporte']);
        $t     = strtotime($r['fecha_reporte']);
        $dia   = $t ? (int)date('j',$t) : '';
        $mes   = $t ? (int)date('n',$t) : '';
        $estado  = $r['estado'] ?: 'Pendiente';
        $isPend  = ($estado === 'Pendiente');
      ?>
        <tr
          data-text="<?= h(mb_strtolower(($r['nombre_reporte'] ?? '').' '.($r['descripcion'] ?? ''),'UTF-8')) ?>"
          data-user-id="<?= (int)$r['usuario_id'] ?>"
          data-dia="<?= $dia ?>"
          data-mes="<?= $mes ?>"
          data-estado="<?= h($estado) ?>"
        >
          <td class="col-id"><?= (int)$r['id'] ?></td>
          <td class="col-titulo">
            <div class="truncate"><strong><?= h($r['nombre_reporte']) ?></strong></div>
          </td>
          <td class="col-desc">
            <div class="desc-text"><?= nl2br(h($r['descripcion'])) ?></div>
          </td>
          <td class="col-user">
            <div class="truncate"><strong><?= h($r['usuario_nombre']) ?></strong></div>
            <div class="sub truncate"><?= h($r['usuario_email']) ?></div>
          </td>
          <td class="col-fecha"><?= h($fecha) ?></td>
          <td class="col-estado">
            <?php if ($isPend): ?>
              <div class="estado-dropdown" data-id="<?= (int)$r['id'] ?>">
                <button type="button" class="estado-btn">
                  <span>Pendiente</span>
                  <span class="estado-chev">▾</span>
                </button>
                <div class="estado-menu">
                  <button type="button" data-value="Resuelto">Resuelto</button>
                </div>
              </div>
            <?php else: ?>
              <span class="status-pill st-resuelto">Resuelto</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
/* ==== Filtros ==== */
(function(){
  const $  = (s,root=document)=>root.querySelector(s);
  const $$ = (s,root=document)=>Array.from(root.querySelectorAll(s));
  const rows = $$('#tablaReportes tbody tr');
  const norm = s => (s||'').toString().toLowerCase();

  function apply(){
    const q      = norm($('#f-q')?.value);
    const userId = $('#f-user')?.value || '';
    const d      = $('#f-dia')?.value || '';
    const m      = $('#f-mes')?.value || '';
    const est    = $('#f-estado')?.value || '';

    rows.forEach(tr=>{
      const vText   = tr.dataset.text || '';
      const vUser   = tr.dataset.userId || '';
      const vDia    = tr.dataset.dia || tr.getAttribute('data-dia') || '';
      const vMes    = tr.dataset.mes || tr.getAttribute('data-mes') || '';
      const vEstado = tr.dataset.estado || tr.getAttribute('data-estado') || '';

      let show = true;
      show = show && (q === ''      || vText.includes(q));
      show = show && (userId === '' || vUser === userId);
      show = show && (d === ''      || String(vDia) === String(d));
      show = show && (m === ''      || String(vMes) === String(m));
      show = show && (est === ''    || vEstado === est);

      tr.style.display = show ? '' : 'none';
    });
  }

  const listen = (id,ev='change') => {
    const el = document.querySelector(id);
    if (!el) return;
    el.addEventListener(ev, apply);
  };

  listen('#f-q','input');
  listen('#f-user');
  listen('#f-dia');
  listen('#f-mes');
  listen('#f-estado');

  apply();
})();

/* ==== Dropdown de estado (Pendiente -> Resuelto) ==== */
(function(){
  const $$ = (s,root=document)=>Array.from(root.querySelectorAll(s));

  function closeAll(){
    $$('.estado-dropdown.open').forEach(d=>d.classList.remove('open'));
  }

  // Abrir/cerrar menú
  $$('.estado-dropdown').forEach(box=>{
    const btn  = box.querySelector('.estado-btn');
    const menu = box.querySelector('.estado-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', (e)=>{
      e.stopPropagation();
      const isOpen = box.classList.contains('open');
      closeAll();
      if (!isOpen) box.classList.add('open');
    });

    // Click en opción (solo Resuelto)
    menu.querySelectorAll('button[data-value]').forEach(opt=>{
      opt.addEventListener('click', async (e)=>{
        e.stopPropagation();
        const val = opt.dataset.value;
        const id  = box.dataset.id;
        if (!id || val !== 'Resuelto') return;

        try{
          const body = new URLSearchParams();
          body.append('action','update_estado');
          body.append('id',id);
          body.append('estado','Resuelto');

          const resp = await fetch('reportesAction.php', {
            method:'POST',
            headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
            body: body.toString()
          });

          const data = await resp.json().catch(()=>null);
          if (!data || !data.ok) {
            alert('No se pudo actualizar el estado.');
            closeAll();
            return;
          }

          // Reemplazar dropdown por pill Resuelto
          const td = box.parentElement;
          const pill = document.createElement('span');
          pill.className = 'status-pill st-resuelto';
          pill.textContent = 'Resuelto';
          td.innerHTML = '';
          td.appendChild(pill);

          // Actualizar atributo data-estado en la fila para que los filtros sigan bien
          const tr = td.closest('tr');
          if (tr) {
            tr.dataset.estado = 'Resuelto';
          }

        }catch(err){
          console.error(err);
          alert('Error de conexión al actualizar el estado.');
        } finally {
          closeAll();
        }
      });
    });
  });

  // Cerrar al hacer click fuera
  document.addEventListener('click', ()=>closeAll());
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
