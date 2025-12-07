<?php
/* =========================================================================
 * FILE: php/cliente/reservas/steps/reservas_cancha.php
 * ========================================================================= */
require './../../../config.php';

try {
    $sql = "
      SELECT 
        c.cancha_id, c.nombre, c.ubicacion, c.tipo, c.capacidad, c.precio, c.proveedor_id,
        COALESCE(pd.nombre_club, u.nombre) AS club_nombre
      FROM canchas c
      LEFT JOIN proveedores_detalle pd ON pd.proveedor_id = c.proveedor_id
      LEFT JOIN usuarios u            ON u.user_id       = c.proveedor_id
      ORDER BY c.tipo, c.nombre
    ";
    $result = $conn->query($sql);
    if (!$result) throw new Exception($conn->error);

    $canchasPorTipo = ['clasica'=>[], 'cubierta'=>[], 'panoramica'=>[]];
    while ($row = $result->fetch_assoc()) {
        $tipo = strtolower(trim($row['tipo']));
        if (!isset($canchasPorTipo[$tipo])) $canchasPorTipo[$tipo] = [];
        $canchasPorTipo[$tipo][] = $row;
    }
} catch (Exception $e) {
    die("Error al cargar canchas: " . htmlspecialchars($e->getMessage()));
}

include './../../includes/header.php';

$tipoMeta = [
  'clasica'    => ['label'=>'Clásica',    'img'=>'/img/canchas/clasica.png'],
  'cubierta'   => ['label'=>'Cubierta',   'img'=>'/img/canchas/techada.png'],
  'panoramica' => ['label'=>'Panorámica', 'img'=>'/img/canchas/panoramica.png'],
];

$defaultType = 'clasica';
foreach (['clasica','cubierta','panoramica'] as $t) {
    if (!empty($canchasPorTipo[$t])) { $defaultType = $t; break; }
}
?>
<style>
:root{
  --teal-700:#054a56; --teal-600:#07566b; --teal-500:#1bab9d;
  --white:#fff; --ink:#043b3d; --muted:#5a6b6c;
  --panel-h: 580px; /* altura fija solicitada */
}
.page-wrap{ width:100%; max-width:1150px; margin:0 auto; display:flex; flex-direction:column; gap:22px; }
.h1-center{ color:var(--white); text-align:center; font-size:40px; font-weight:800; margin:0 0 8px; letter-spacing:.2px; }

/* Tabs */
.tabs{ display:flex; gap:16px; justify-content:center; margin-top:2px; margin-bottom:4px; flex-wrap:wrap; }
.tab{ padding:14px 22px; border-radius:14px; background: rgba(255,255,255,0.18); color:#fff; font-weight:900; border:1px solid rgba(255,255,255,0.22); cursor:pointer; user-select:none; transition:.18s; display:flex; align-items:center; gap:10px; font-size:16px; }
.tab:hover{ filter:brightness(1.06); transform:translateY(-1px); }
.tab.active{ background:var(--white); color:var(--teal-700); border-color:transparent; box-shadow:0 10px 28px rgba(0,0,0,.20); }

/* Shell */
.shell{ display:grid; grid-template-columns: 1.05fr 0.95fr; gap:22px; }
@media (max-width: 980px){ .shell{ grid-template-columns:1fr; } }

/* ---------------- IZQ (lista) ---------------- */
.left{
  background: rgba(255,255,255,0.10);
  border-radius:18px; box-shadow: 0 12px 28px rgba(0,0,0,0.35); padding:16px;
  display:flex; flex-direction:column; gap:12px;
  height: var(--panel-h); /* altura fija */
}
.controls{ display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
/* CORRECCIÓN puntual */
.search{
  flex:1;
  min-width:260px;
  background:#fff;
  color:var(--ink);               /* antes: color:#ink;  */
  border:1px solid #e1ecec;
  border-radius:12px;
  padding:12px 14px;
  outline:none;
  font-size:15px;
  box-shadow:0 4px 14px rgba(0,0,0,0.10);
}

/* Si querés fallback por si --ink no existe: */
/*
.search{
  color:var(--ink, #043b3d);
}
*/
.search::placeholder{ color:#95a8a9; }
.small-muted{ color:#e7f3f3; font-size:12px; }

.list-wrap{ display:flex; flex-direction:column; gap:10px; flex:1; min-height:0; }
.list{
  border-radius:14px; overflow:auto;
  background: rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.05);
  flex:1; min-height:0; /* scroll interno si hace falta */
}
.item{
  display:grid; grid-template-columns: 1fr auto; gap:10px; align-items:center;
  padding:14px 16px; border-bottom:1px solid rgba(255,255,255,.08); color:#fff; cursor:pointer; transition:.16s;
}
.item:last-child{ border-bottom:none; }
.item:hover{ background: rgba(255,255,255,.10); }
.item.active{ outline:2px solid var(--teal-500); outline-offset:-2px; background: rgba(255,255,255,.12); }
.item .title{ font-weight:900; letter-spacing:.2px; font-size:16px; }
.item .meta{ font-size:12px; color:#e7f3f3; margin-top:2px; }
.item .chips{ display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
.chip{ font-size:12px; font-weight:800; color:#043b3d; background:#fff; border-radius:999px; padding:6px 10px; box-shadow:0 6px 16px rgba(0,0,0,.16); }

/* Paginación fija abajo del panel */
.paginate{ display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:auto; }
.paginate .info{ color:#f0fbfb; font-size:13px; }
.pager{ display:flex; gap:8px; align-items:center; }
.pager button{ padding:8px 12px; border:1px solid #cfe9e7; color:#054a56; background:#fff; border-radius:10px; font-weight:800; cursor:pointer; }
.pager button[disabled]{ opacity:.6; cursor:not-allowed; }

/* ---------------- DER (detalle) ---------------- */
.right{
  background:#fff; color:var(--ink); border-radius:18px; box-shadow: 0 14px 38px rgba(0,0,0,0.28);
  padding:14px;
  display:grid; grid-template-rows: auto auto 1fr auto auto; gap:12px;
  height: var(--panel-h); /* altura fija SIEMPRE, elegido o no */
}
.type-preview{ width:100%; height:140px; border-radius:12px; overflow:hidden; border:1px solid #e7eeee; background:#f7f9f9; display:flex; align-items:center; justify-content:center; }
.type-preview img{ width:100%; height:100%; object-fit:cover; }
.right h3{ margin:0; font-size:18px; font-weight:900; color:var(--teal-700); }

/* Zona central: hace scroll si el contenido crece */
.center-scroll{ min-height:0; overflow:auto; }

/* Specs */
.specs{ display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:10px; }
.spec{ border:1px solid #e7eeee; background:#f8fbfb; border-radius:10px; padding:10px; min-height:68px; display:flex; flex-direction:column; gap:6px; }
.spec .label{ font-size:12px; letter-spacing:.2px; color:#557173; font-weight:800; text-transform:uppercase; }
.spec .value{ font-size:15px; font-weight:800; color:#043b3d; min-height:18px; }

.empty{ border:1px dashed #dbe6e6; background:#fbfefe; border-radius:10px; padding:14px; font-size:14px; color:#2a4e51; display:flex; align-items:center; justify-content:center; text-align:center; }

/* Tips compactos */
.preview{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
.preview .box{ background:#f7f9f9; border:1px solid #e7eeee; border-radius:10px; padding:10px; font-size:13px; }
.preview .box strong{ color:#07566b; }

/* Barra de resumen al pie del card */
.cta{ display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
.summary{ font-weight:900; color:#054a56; }
.btn-next{ padding:10px 14px; border-radius:10px; border:none; font-weight:900; color:#fff; background: linear-gradient(180deg, var(--teal-600), var(--teal-700)); cursor:pointer; box-shadow:0 10px 28px rgba(0,0,0,.20); }
.btn-next:disabled{ opacity:.6; cursor:not-allowed; box-shadow:none; }
.btn-outline{ padding:10px 14px; border-radius:10px; background:#fff; color:#054a56; border:1px solid #1bab9d; font-weight:900; }

.hidden{ display:none !important; }

/* En mobile liberamos la altura fija para no cortar el flujo */
@media (max-width: 980px){
  .left, .right{ height:auto; min-height: var(--panel-h); }
}
@media (max-width: 820px){ .preview{ grid-template-columns:1fr; } .specs{ grid-template-columns:1fr; } }
</style>

<div class="page-wrap">
  <h1 class="h1-center">Elegí tu cancha</h1>

  <!-- Tabs -->
  <div class="tabs" id="tabs">
    <?php foreach (['clasica','cubierta','panoramica'] as $t): ?>
      <button class="tab <?= $t===$defaultType?'active':'' ?>" data-type="<?= $t ?>">
        <?= htmlspecialchars($tipoMeta[$t]['label']) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="shell">
    <!-- IZQ -->
    <div class="left">
      <div class="controls">
        <input id="search" class="search" type="text" placeholder="Buscar por club, nombre o ubicación…">
        <span class="small-muted">Resultados: <strong id="count">0</strong></span>
      </div>

      <?php foreach (['clasica','cubierta','panoramica'] as $t): ?>
        <div class="list-wrap <?= $t===$defaultType?'':'hidden' ?>" data-list-wrap="<?= $t ?>" id="wrap-<?= $t ?>">
          <div class="list" id="list-<?= $t ?>">
            <?php if (!empty($canchasPorTipo[$t])): ?>
              <?php foreach ($canchasPorTipo[$t] as $c): 
                  $precio = number_format((float)$c['precio'], 2, ',', '.');
                  $club   = $c['club_nombre'] ?: 'Club';
                  $name   = $c['nombre'];
                  $caps   = (int)$c['capacidad'];
                  $uid    = (int)$c['cancha_id'];
                  $index  = mb_strtolower($name.' '.$club.' '.$c['ubicacion']);
              ?>
                <div class="item" 
                     tabindex="0"
                     data-name="<?= htmlspecialchars($index) ?>"
                     data-id="<?= $uid ?>"
                     data-nombre="<?= htmlspecialchars($name) ?>"
                     data-club="<?= htmlspecialchars($club) ?>"
                     data-ubicacion="<?= htmlspecialchars($c['ubicacion']) ?>"
                     data-capacidad="<?= $caps ?>"
                     data-precio="<?= (float)$c['precio'] ?>"
                     data-tipo="<?= htmlspecialchars($t) ?>">
                  <div>
                    <div class="title"><?= htmlspecialchars($name) ?></div>
                    <div class="meta"><?= htmlspecialchars($club) ?> • <?= htmlspecialchars($c['ubicacion']) ?></div>
                    <div class="chips">
                      <span class="chip">$ <?= $precio ?></span>
                      <span class="chip"><?= $caps ?> jugadores</span>
                      <span class="chip"><?= htmlspecialchars($tipoMeta[$t]['label']) ?></span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="item" data-disabled="1" style="justify-content:center;cursor:default;">No hay canchas en este tipo</div>
            <?php endif; ?>
          </div>

          <!-- Paginación -->
          <div class="paginate">
            <div class="info"><span id="info-<?= $t ?>">Página 1</span></div>
            <div class="pager">
              <button type="button" data-prev="<?= $t ?>">« Anterior</button>
              <button type="button" data-next="<?= $t ?>">Siguiente »</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- DER -->
    <div class="right" id="detail">
      <div class="type-preview" id="d-img">
        <img src="<?= htmlspecialchars($tipoMeta[$defaultType]['img']) ?>" alt="Vista tipo" />
      </div>

      <h3 id="d-title">Seleccioná una sede</h3>

      <!-- Zona central con scroll si hace falta -->
      <div class="center-scroll">
        <div id="specs-empty" class="empty">
          Tip: elegí una cancha de la izquierda para ver el detalle, precio y continuar.
        </div>
        <div id="specs" class="specs hidden">
          <div class="spec"><div class="label">Tipo</div><div class="value" id="d-tipo">—</div></div>
          <div class="spec"><div class="label">Club</div><div class="value" id="d-club">—</div></div>
          <div class="spec"><div class="label">Ubicación</div><div class="value" id="d-ubicacion">—</div></div>
          <div class="spec"><div class="label">Capacidad</div><div class="value" id="d-capacidad">—</div></div>
          <div class="spec"><div class="label">Precio</div><div class="value" id="d-precio">—</div></div>
        </div>

        <div class="preview">
          <div class="box">
            <strong>Consejo</strong><br>
            Podés dividir el costo con tus compañeros en el paso de pago.
          </div>
          <div class="box">
            <strong>Disponibilidad</strong><br>
            En el próximo paso vas a elegir fecha y hora disponibles.
          </div>
        </div>
      </div>

      <!-- pie -->
      <div class="cta">
        <div class="summary" id="summary">$ —</div>
        <div class="actions">
          <a class="btn-outline" href="/php/cliente/home_cliente.php">Volver</a>
          <button id="btn-continue" class="btn-next" type="button" disabled>Continuar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include './../../includes/footer.php'; ?>

<script>
// ====== Estado ======
let activeType = <?= json_encode($defaultType) ?>;
let selectedId = null;

// ====== Paginación ======
const PAGE_SIZE = 4;
const pagestate = {
  clasica: {page:1, total:0, pages:1, filtered:[]},
  cubierta:{page:1, total:0, pages:1, filtered:[]},
  panoramica:{page:1, total:0, pages:1, filtered:[]}
};

// ====== Imágenes por tipo ======
const imgMap = {
  'clasica':    "<?= addslashes($tipoMeta['clasica']['img']) ?>",
  'cubierta':   "<?= addslashes($tipoMeta['cubierta']['img']) ?>",
  'panoramica': "<?= addslashes($tipoMeta['panoramica']['img']) ?>",
};

// ====== Tabs ======
document.querySelectorAll('.tab').forEach(t => {
  t.addEventListener('click', () => {
    const type = t.dataset.type;
    if (type === activeType) return;

    document.querySelectorAll('.tab').forEach(x => x.classList.toggle('active', x===t));
    document.querySelectorAll('[data-list-wrap]').forEach(wrap => wrap.classList.toggle('hidden', wrap.dataset.listWrap !== type));

    activeType = type;

    // Reset detalle
    document.querySelector('#d-img img').src = imgMap[type] || imgMap['clasica'];
    setDetailEmpty();

    // Refiltrar + reset page
    applyFilter(true);
  });
});

// ====== Selección ======
function handleSelect(el){
  document.querySelectorAll(`#list-${activeType} .item`).forEach(i => i.classList.remove('active'));
  el.classList.add('active');

  selectedId = el.dataset.id;
  const tipo = el.dataset.tipo;
  const precioNum = Number(el.dataset.precio || 0);
  const precioStr = precioNum.toLocaleString('es-AR', {minimumFractionDigits:2});
  const labelTipo = (tipo==='clasica'?'Clásica':(tipo==='cubierta'?'Cubierta':'Panorámica'));

  document.querySelector('#d-img img').src = imgMap[tipo] || imgMap['clasica'];

  document.getElementById('d-title').textContent     = `#${selectedId} — ${el.dataset.nombre}`;
  document.getElementById('d-tipo').textContent      = labelTipo;
  document.getElementById('d-club').textContent      = el.dataset.club || '—';
  document.getElementById('d-ubicacion').textContent = el.dataset.ubicacion || '—';
  document.getElementById('d-capacidad').textContent = `${el.dataset.capacidad} jugadores`;
  document.getElementById('d-precio').textContent    = `$ ${precioStr}`;
  document.getElementById('summary').textContent     = `Total: $ ${precioStr}`;

  document.getElementById('specs-empty').classList.add('hidden');
  document.getElementById('specs').classList.remove('hidden');
  document.getElementById('btn-continue').disabled = false;
}

function setDetailEmpty(){
  selectedId = null;
  document.getElementById('d-title').textContent = 'Seleccioná una sede';
  ['d-tipo','d-club','d-ubicacion','d-capacidad','d-precio'].forEach(id => document.getElementById(id).textContent = '—');
  document.getElementById('summary').textContent = '$ —';
  document.getElementById('btn-continue').disabled = true;
  document.getElementById('specs').classList.add('hidden');
  document.getElementById('specs-empty').classList.remove('hidden');
}

function wireItems(){
  document.querySelectorAll(`#list-${activeType} .item`).forEach(it=>{
    it.addEventListener('click', function(){
      if (this.getAttribute('data-disabled') === '1') return;
      handleSelect(this);
    });
    it.addEventListener('keydown', function(e){
      if (e.key === 'Enter' && this.getAttribute('data-disabled') !== '1'){
        handleSelect(this);
      }
    });
  });
}

// ====== CTA continuar ======
document.getElementById('btn-continue').addEventListener('click', () => {
  if (!selectedId) return;
  window.location.href = 'reservas.php?cancha=' + encodeURIComponent(selectedId);
});

// ====== Búsqueda + paginación ======
const input = document.getElementById('search');
input.addEventListener('input', () => applyFilter(true));

function getAllItems(type){
  const list = document.getElementById('list-'+type);
  return list ? Array.from(list.querySelectorAll('.item')) : [];
}

function applyFilter(resetPage){
  ['clasica','cubierta','panoramica'].forEach(type=>{
    const items = getAllItems(type);
    const q = input.value.trim().toLowerCase();

    const filtered = items.filter(it=>{
      const disabled = it.getAttribute('data-disabled') === '1';
      if (disabled) return false;
      const hay = it.dataset.name || '';
      return !q || hay.includes(q);
    });

    pagestate[type].filtered = filtered;
    pagestate[type].total    = filtered.length;
    pagestate[type].pages    = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
    if (resetPage) pagestate[type].page = 1;
    if (pagestate[type].page > pagestate[type].pages) pagestate[type].page = 1;

    renderPage(type);
  });

  updateCount();
}

function renderPage(type){
  const allItems = getAllItems(type);
  allItems.forEach(it=>{
    if (it.getAttribute('data-disabled')==='1') { it.style.display=''; return; }
    it.style.display='none';
  });

  const list = pagestate[type].filtered;
  const page = pagestate[type].page;
  const from = (page-1)*PAGE_SIZE;
  const to   = from + PAGE_SIZE;

  list.slice(from, to).forEach(it=>{ it.style.display=''; });

  const info  = document.getElementById('info-'+type);
  const total = pagestate[type].total;
  const pages = pagestate[type].pages;
  info.textContent = (total>0) ? `Página ${page} de ${pages} — ${total} resultados` : `Sin resultados`;

  const prevBtn = document.querySelector(`[data-prev="${type}"]`);
  const nextBtn = document.querySelector(`[data-next="${type}"]`);
  if (prevBtn && nextBtn){
    prevBtn.disabled = (page<=1 || total===0);
    nextBtn.disabled = (page>=pages || total===0);
    prevBtn.onclick = function(){
      if (pagestate[type].page>1){ pagestate[type].page--; renderPage(type); updateCount(); }
    };
    nextBtn.onclick = function(){
      if (pagestate[type].page<pages){ pagestate[type].page++; renderPage(type); updateCount(); }
    };
  }

  if (type===activeType) wireItems();
}

function updateCount(){
  document.getElementById('count').textContent = pagestate[activeType].total;
}

// Inicial
applyFilter(true);
wireItems();
</script>
