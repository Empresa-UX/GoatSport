<?php
/* =========================================================================
 * FILE: php/cliente/reservas/steps/reservas_cancha.php
 * ========================================================================= */
require './../../../config.php';

try {
    $sql = "
      SELECT 
        c.cancha_id,
        c.nombre,
        c.tipo,
        c.capacidad,
        c.precio,
        c.proveedor_id,
        COALESCE(pd.nombre_club, u.nombre) AS club_nombre,
        pd.direccion AS club_direccion,
        pd.ciudad    AS club_ciudad,
        pd.barrio    AS barrio,
        TRIM(CONCAT_WS(', ', pd.direccion, pd.barrio, pd.ciudad)) AS ubicacion
      FROM canchas c
      LEFT JOIN proveedores_detalle pd ON pd.proveedor_id = c.proveedor_id
      LEFT JOIN usuarios u             ON u.user_id       = c.proveedor_id
      WHERE pd.barrio IS NOT NULL AND TRIM(pd.barrio) <> ''
      ORDER BY pd.barrio, pd.nombre_club, c.tipo, c.nombre
    ";
    $result = $conn->query($sql);
    if (!$result) throw new Exception($conn->error);

    $canchasPorTipo = ['clasica'=>[], 'cubierta'=>[], 'panoramica'=>[]];
    $barrios = [];
    $ciudades = [];
    $clubFlags = [];

    while ($row = $result->fetch_assoc()) {
        $tipo = strtolower(trim($row['tipo']));
        if (!isset($canchasPorTipo[$tipo])) $canchasPorTipo[$tipo] = [];

        $barrio = trim($row['barrio']);
        $club   = $row['club_nombre'] ?: 'Club';
        $direccion = $row['club_direccion'] ?: '';
        $ciudad    = $row['club_ciudad'] ?: '';

        $direccionCompleta = $direccion ? $direccion . (($ciudad && $ciudad !== $barrio) ? ', ' . $ciudad : '') : 'Dirección no disponible';

        if ($barrio && !in_array($barrio, $barrios, true))   $barrios[]  = $barrio;
        if ($ciudad && !in_array($ciudad, $ciudades, true)) $ciudades[] = $ciudad;

        if (!isset($clubFlags[$barrio])) $clubFlags[$barrio] = [];
        if (!isset($clubFlags[$barrio][$club])) {
            $clubFlags[$barrio][$club] = [
                'nombre'          => $club,
                'direccion'       => $direccionCompleta,
                'direccion_corta' => $direccion,
                'barrio'          => $barrio,
                'ciudad'          => $ciudad,
                'has_individual'  => false,
                'has_equipo'      => false,
            ];
        }
        $cap = (int)$row['capacidad'];
        if ($cap === 2) $clubFlags[$barrio][$club]['has_individual'] = true; // por si en el futuro querés filtrar clubes por esto
        if ($cap === 4) $clubFlags[$barrio][$club]['has_equipo']     = true;

        $row['club_direccion_completa'] = $direccionCompleta;
        $row['club_ciudad'] = $ciudad;
        $canchasPorTipo[$tipo][] = $row;
    }

    sort($barrios);
    sort($ciudades);

    $clubesPorBarrio = [];
    $todosLosClubes  = [];
    foreach ($clubFlags as $barrio => $clubs) {
        $arr = array_values($clubs);
        usort($arr, fn($a,$b)=>strcmp($a['nombre'],$b['nombre']));
        $clubesPorBarrio[$barrio] = $arr;
        foreach ($arr as $c) $todosLosClubes[] = $c;
    }
    usort($todosLosClubes, fn($a,$b)=>strcmp($a['nombre'],$b['nombre']));

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
  --white:#fff; --ink:#043b3d; --panel-h: 580px;
}
.page-wrap{ width:100%; max-width:1150px; margin:0 auto; display:flex; flex-direction:column; gap:22px; }
.h1-center{ color:var(--white); text-align:center; font-size:40px; font-weight:800; margin:0 0 8px; letter-spacing:.2px; }

.tabs{ display:flex; gap:16px; justify-content:center; margin-top:2px; margin-bottom:4px; flex-wrap:wrap; }
.tab{ padding:9px 14px; border-radius:14px; background: rgba(255,255,255,0.18); color:#fff; font-weight:900; border:1px solid rgba(255,255,255,0.22); cursor:pointer; user-select:none; transition:.18s; display:flex; align-items:center; gap:10px; font-size:16px; }
.tab:hover{ filter:brightness(1.06); transform:translateY(-1px); }
.tab.active{ background:var(--white); color:var(--teal-700); border-color:transparent; box-shadow:0 10px 28px rgba(0,0,0,.20); }

.shell{ display:grid; grid-template-columns: 1.05fr 0.95fr; gap:22px; }
@media (max-width: 980px){ .shell{ grid-template-columns:1fr; } }

.left{
  background: rgba(255,255,255,0.10);
  border-radius:18px; box-shadow: 0 12px 28px rgba(0,0,0,0.35); padding:16px;
  display:flex; flex-direction:column; gap:12px;
  height: var(--panel-h);
}
.controls{ display:flex; gap:12px; align-items:center; flex-wrap:wrap; }

.select{
  min-width:170px; background:#fff; color:var(--ink);
  border:1px solid #e1ecec; border-radius:12px; padding:12px 14px;
  outline:none; font-size:15px; box-shadow:0 4px 14px rgba(0,0,0,0.10); cursor:pointer;
}

.small-muted{ color:#e7f3f3; font-size:12px; }
.list-wrap{ display:flex; flex-direction:column; gap:10px; flex:1; min-height:0; }
.list{ border-radius:14px; overflow:auto; background: rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); box-shadow: inset 0 1px 0 rgba(255,255,255,.05); flex:1; min-height:0; }

.club-item{ display:block; padding:16px; border-bottom:1px solid rgba(255,255,255,.08); color:#fff; cursor:pointer; transition:.16s; background: rgba(255,255,255,.05); }
.club-item:last-child{ border-bottom:none; }
.club-item:hover{ background: rgba(255,255,255,.12); transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.15); }
.club-item.selected{ background: rgba(255,255,255,.15); border-left:4px solid var(--teal-500); }
.club-name{ font-weight:900; letter-spacing:.2px; font-size:16px; margin-bottom:6px; color:#fff; }
.club-address{ font-size:13px; color:#e7f3f3; display:flex; align-items:center; gap:6px; }
.club-address:before{ content:"📍"; font-size:12px; }
.club-badge{ display:inline-block; background: rgba(27,171,157,0.2); color:#e7f3f3; padding:4px 10px; border-radius:12px; font-size:11px; margin-top:8px; font-weight:600; border:1px solid rgba(27,171,157,0.3); }
.club-barrio{ display:inline-block; background: rgba(255,255,255,0.1); color:#e7f3f3; padding:3px 8px; border-radius:8px; font-size:10px; margin-top:6px; font-weight:600; }

.item{ display:grid; grid-template-columns: 1fr auto; gap:10px; align-items:center; padding:14px 16px; border-bottom:1px solid rgba(255,255,255,.08); color:#fff; cursor:pointer; transition:.16s; }
.item:last-child{ border-bottom:none; }
.item:hover{ background: rgba(255,255,255,.10); }
.item.active{ outline:2px solid var(--teal-500); outline-offset:-2px; background: rgba(255,255,255,.12); }
.item .title{ font-weight:900; letter-spacing:.2px; font-size:16px; }
.item .meta{ font-size:12px; color:#e7f3f3; margin-top:2px; }
.item .chips{ display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
.chip{ font-size:12px; font-weight:800; color:#043b3d; background:#fff; border-radius:999px; padding:6px 10px; box-shadow:0 6px 16px rgba(0,0,0,.16); }

.paginate{ display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:auto; }
.paginate .info{ color:#f0fbfb; font-size:13px; }
.pager{ display:flex; gap:8px; align-items:center; }
.pager button{ padding:8px 12px; border:1px solid #cfe9e7; color:#054a56; background:#fff; border-radius:10px; font-weight:800; cursor:pointer; }
.pager button[disabled]{ opacity:.6; cursor:not-allowed; }

.right{
  background:#fff; color:var(--ink); border-radius:18px; box-shadow: 0 14px 38px rgba(0,0,0,0.28);
  padding:14px;
  display:grid; grid-template-rows: auto auto 1fr auto auto; gap:12px;
  height: var(--panel-h);
}
.type-preview{ width:100%; height:140px; border-radius:12px; overflow:hidden; border:1px solid #e7eeee; background:#f7f9f9; display:flex; align-items:center; justify-content:center; }
.type-preview img{ width:100%; height:100%; object-fit:cover; }
.right h3{ margin:0; font-size:18px; font-weight:900; color:var(--teal-700); }

.center-scroll{ min-height:0; overflow:auto; }

.specs{ display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:10px; min-height: 270px;}
.spec{ border:1px solid #e7eeee; background:#f8fbfb; border-radius:10px; padding:6px; min-height:40px; display:flex; flex-direction:column; gap:6px; }
.spec .label{ font-size:12px; letter-spacing:.2px; color:#557173; font-weight:600; text-transform:uppercase; }
.spec .value{ font-size:15px; font-weight:600; color:#043b3d; min-height:18px; }

.empty{ border:1px solid #e7eeee; background:#f8fbfb; border-radius:12px; padding:22px 18px; font-size:14px; color:#043b3d; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); min-height: 200px; }
.empty-icon{ font-size: 42px; margin-bottom: 16px; color: #1bab9d; opacity: 0.8; }
.empty-title{ font-size:16px; font-weight:900; color:#054a56; margin-bottom: 12px; letter-spacing: 0.2px; }
.empty-steps{ font-size: 13px; color: #043b3d; line-height: 1.5; max-width: 280px; }
.empty-step{ display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; text-align: left; }
.empty-step-num{ background: #1bab9d; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 900; flex-shrink: 0; margin-top: 1px; }

.cta{ margin-top:10px; } /* espacio pedido */
.cta-corners{ display:grid; grid-template-columns: 1fr auto 1fr; align-items:center; }
.cta-left{ justify-self:start; }
.cta-right{ justify-self:end; }

.btn-next{ padding:10px 14px; border-radius:10px; border:none; font-weight:600; color:#fff; background: linear-gradient(180deg, var(--teal-600), var(--teal-700)); cursor:pointer; box-shadow:0 10px 28px rgba(0,0,0,.20); }
.btn-next:disabled{ opacity:.6; cursor:not-allowed; box-shadow:none; }
.btn-outline{ padding:10px 14px; border-radius:10px; background:#fff; color:#054a56; border:1px solid #1bab9d; font-weight:600; text-decoration: none}

.hidden{ display:none !important; }

@media (max-width: 980px){
  .left, .right{ height:auto; min-height: var(--panel-h); }
  .controls{ flex-direction:column; align-items:stretch; }
  .select{ min-width:100%; width:100%; }
}
@media (max-width: 820px){ .preview{ grid-template-columns:1fr; } .specs{ grid-template-columns:1fr; } }
</style>

<div class="page-wrap">
  <h1 id="main-title" class="h1-center">Elegí un club cerca tuyo</h1>

  <div class="tabs" id="tabs">
    <?php foreach (['clasica','cubierta','panoramica'] as $t): ?>
      <button class="tab <?= $t===$defaultType?'active':'' ?>" data-type="<?= $t ?>">
        <?= htmlspecialchars($tipoMeta[$t]['label']) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="shell">
    <div class="left">
      <div class="controls">
        <select id="filtro-ciudad" class="select">
          <option value="">Ciudad</option>
          <?php foreach ($ciudades as $ci): ?>
            <option value="<?= htmlspecialchars($ci) ?>"><?= htmlspecialchars($ci) ?></option>
          <?php endforeach; ?>
        </select>

        <select id="filtro-barrio" class="select">
          <option value="">Selecciona tu barrio</option>
          <?php foreach ($barrios as $barrio): ?>
            <option value="<?= htmlspecialchars($barrio) ?>"><?= htmlspecialchars($barrio) ?></option>
          <?php endforeach; ?>
        </select>

        <select id="filtro-capacidad" class="select">
          <option value="">Forma de reserva</option>
          <option value="individual">Forma individual</option>
          <option value="equipo">Por equipo</option>
        </select>
        
        <span class="small-muted">
          <span id="mode-indicator">Todos los clubes</span> • 
          Resultados: <strong id="count">0</strong>
        </span>
      </div>

      <div id="clubes-container" class="list-wrap">
        <div class="list" id="clubes-list"></div>
        <div class="paginate">
          <div class="info"><span id="info-clubes">Seleccioná un club para ver sus canchas</span></div>
        </div>
      </div>

      <?php foreach (['clasica','cubierta','panoramica'] as $t): ?>
        <div class="list-wrap hidden" data-list-wrap="<?= $t ?>" id="wrap-<?= $t ?>">
          <div class="list" id="list-<?= $t ?>">
            <?php if (!empty($canchasPorTipo[$t])): ?>
              <?php foreach ($canchasPorTipo[$t] as $c): 
                  $precio = number_format((float)$c['precio'], 2, ',', '.');
                  $club   = $c['club_nombre'] ?: 'Club';
                  $name   = $c['nombre'];
                  $caps   = (int)$c['capacidad'];
                  $uid    = (int)$c['cancha_id'];
                  $barrio = $c['barrio'] ?: '';
                  $ubicacion = $c['ubicacion'] ?: '';
                  $direccionClub = $c['club_direccion_completa'] ?: '';
                  $ciudadClub = $c['club_ciudad'] ?: '';
              ?>
                <div class="item" 
                     tabindex="0"
                     data-id="<?= $uid ?>"
                     data-nombre="<?= htmlspecialchars($name) ?>"
                     data-club="<?= htmlspecialchars($club) ?>"
                     data-barrio="<?= htmlspecialchars($barrio) ?>"
                     data-ubicacion="<?= htmlspecialchars($ubicacion) ?>"
                     data-direccion-club="<?= htmlspecialchars($direccionClub) ?>"
                     data-ciudad="<?= htmlspecialchars($ciudadClub) ?>"
                     data-capacidad="<?= $caps ?>"
                     data-precio="<?= (float)$c['precio'] ?>"
                     data-tipo="<?= htmlspecialchars($t) ?>">
                  <div>
                    <div class="title"><?= htmlspecialchars($name) ?></div>
                    <div class="meta"><?= htmlspecialchars($club) ?> • <?= htmlspecialchars($barrio) ?></div>
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

    <div class="right" id="detail">
      <div class="type-preview" id="d-img">
        <img src="<?= htmlspecialchars($tipoMeta[$defaultType]['img']) ?>" alt="Vista tipo" />
      </div>

      <h3 id="d-title">Seleccioná una sede</h3>

      <div class="center-scroll">
        <div id="specs-empty" class="empty">
          <div class="empty-icon">📍</div>
          <div class="empty-title">¿Cómo reservar?</div>
          <div class="empty-steps">
            <div class="empty-step"><div class="empty-step-num">1</div><div>Seleccioná un club de la lista</div></div>
            <div class="empty-step"><div class="empty-step-num">2</div><div>Verás las canchas disponibles</div></div>
            <div class="empty-step"><div class="empty-step-num">3</div><div>Seleccioná una cancha para ver detalles</div></div>
            <div class="empty-step"><div class="empty-step-num">4</div><div>Filtrá por barrio/capacidad/ciudad si necesitás</div></div>
          </div>
        </div>
        
        <div id="specs" class="specs hidden">
          <div class="spec"><div class="label">Tipo</div><div class="value" id="d-tipo">—</div></div>
          <div class="spec"><div class="label">Club</div><div class="value" id="d-club">—</div></div>
          <div class="spec"><div class="label">Barrio</div><div class="value" id="d-barrio">—</div></div>
          <div class="spec"><div class="label">Dirección</div><div class="value" id="d-direccion">—</div></div>
          <div class="spec"><div class="label">Capacidad</div><div class="value" id="d-capacidad">—</div></div>
          <div class="spec"><div class="label">Precio por hora</div><div class="value" id="d-precio">—</div></div>
        </div>

        <!-- CTA: botones en esquinas + summary en el centro -->
        <div class="cta cta-corners">
          <a class="btn-outline cta-left" href="/php/cliente/home_cliente.php">Volver</a>
          <div class="summary" style="visibility: hidden;;" id="summary"></div>
          <button id="btn-continue" class="btn-next cta-right" type="button" disabled>Continuar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include './../../includes/footer.php'; ?>

<script>
// ====== Estado ======
let activeType = '<?= $defaultType ?>';
let selectedId = null;
let selectedClub = null;
let selectedBarrio = null;
let selectedCap = '';   // '' | 'individual' | 'equipo' (solo afecta canchas)
let selectedCity = '';  // '' | ciudad

// ====== Datos ======
const clubesPorBarrio = <?= json_encode($clubesPorBarrio, JSON_UNESCAPED_UNICODE) ?>;
const todosLosClubes  = <?= json_encode($todosLosClubes,  JSON_UNESCAPED_UNICODE) ?>;

// ====== Paginación ======
const PAGE_SIZE = 6;
const pagestate = {
  clasica: {page:1, total:0, pages:1, filtered:[]},
  cubierta:{page:1, total:0, pages:1, filtered:[]},
  panoramica:{page:1, total:0, pages:1, filtered:[]}
};

// ====== Imágenes por tipo ======
const imgMap = {
  'clasica':    '<?= addslashes($tipoMeta['clasica']['img']) ?>',
  'cubierta':   '<?= addslashes($tipoMeta['cubierta']['img']) ?>',
  'panoramica': '<?= addslashes($tipoMeta['panoramica']['img']) ?>'
};

// ====== DOM ======
const mainTitle     = document.getElementById('main-title');
const selectBarrio  = document.getElementById('filtro-barrio');
const selectCap     = document.getElementById('filtro-capacidad');
const selectCiudad  = document.getElementById('filtro-ciudad');
const clubesContainer = document.getElementById('clubes-container');
const clubesList      = document.getElementById('clubes-list');
const modeIndicator   = document.getElementById('mode-indicator');

// ====== Tabs ======
document.querySelectorAll('.tab').forEach(t => {
  t.addEventListener('click', () => {
    const type = t.dataset.type;
    if (type === activeType) return;
    document.querySelectorAll('.tab').forEach(x => x.classList.toggle('active', x===t));
    activeType = type;
    document.querySelector('#d-img img').src = imgMap[type] || imgMap['clasica'];
    if (selectedClub && selectedBarrio) {
      showCanchasForClub(selectedClub, selectedBarrio);
    } else {
      setDetailEmpty();
    }
  });
});

// ====== Filtros ======
selectBarrio.addEventListener('change', () => {
  selectedBarrio = selectBarrio.value || '';
  onFiltersChange('barrio');
});
selectCap.addEventListener('change', () => {
  selectedCap = selectCap.value || '';
  onFiltersChange('capacidad');
});
selectCiudad.addEventListener('change', () => {
  selectedCity = selectCiudad.value || '';
  onFiltersChange('ciudad');
});

// Solo barrio/ciudad evalúan si el club seleccionado sigue siendo válido.
// Capacidad NUNCA te saca de canchas; solo refiltra las canchas visibles.
function onFiltersChange(kind){
  if (selectedClub) {
    const stillMatches = clubMatchesFiltersOnlyLocation({
      nombre:selectedClub,
      barrio: findClubBarrio(selectedClub),
      ciudad: findClubCity(selectedClub)
    });
    if (!stillMatches) {
      selectedClub = null;
      selectedId = null;
      showClubsView();
      setDetailEmpty();
    } else {
      // Si seguimos en el club, re-render de canchas (aplica capacidad y ciudad)
      showCanchasForClub(selectedClub, findClubBarrio(selectedClub));
    }
  }
  // Render de clubes (capacidad no afecta esta lista)
  renderClubList();
}

function findClubBarrio(clubName){
  const found = todosLosClubes.find(c => c.nombre === clubName);
  return found ? found.barrio : '';
}
function findClubCity(clubName){
  const found = todosLosClubes.find(c => c.nombre === clubName);
  return found ? (found.ciudad || '') : '';
}

// ====== Render lista clubes ======
function clubMatchesFiltersOnlyLocation(club){
  if (selectedBarrio && club.barrio !== selectedBarrio) return false;
  if (selectedCity   && (club.ciudad || '') !== selectedCity) return false;
  return true;
}

function renderClubList(){
  clubesList.innerHTML = '';
  const source = selectedBarrio ? (clubesPorBarrio[selectedBarrio] || []) : todosLosClubes;

  // Importante: capacidad NO filtra clubes, solo barrio/ciudad.
  const filteredClubs = source.filter(clubMatchesFiltersOnlyLocation);

  filteredClubs.forEach((club, index) => {
    const clubDiv = document.createElement('div');
    clubDiv.className = 'club-item';
    clubDiv.dataset.club = club.nombre;
    clubDiv.dataset.barrio = club.barrio;
    clubDiv.dataset.direccion = club.direccion;
    clubDiv.dataset.index = index;
    clubDiv.innerHTML = `
      <div class="club-name">${club.nombre}</div>
      <div class="club-address">${club.direccion}</div>
      <div class="club-barrio">${club.barrio}${club.ciudad ? ' • ' + club.ciudad : ''}</div>
      <div class="club-badge">Ver canchas disponibles</div>
    `;
    clubDiv.addEventListener('click', () => {
      selectClub(clubDiv, club.nombre, club.barrio, club.direccion);
    });
    clubesList.appendChild(clubDiv);
  });

  const scopeText = selectedBarrio ? `Barrio: ${selectedBarrio}` : 'Todos los clubes';
  modeIndicator.textContent = scopeText;
  document.getElementById('info-clubes').textContent = 
    filteredClubs.length ? `${filteredClubs.length} clubes disponibles${selectedBarrio ? ' en ' + selectedBarrio : ''}` : 'No hay clubes para los filtros seleccionados';
  updateCount(filteredClubs.length);
  showClubsView();
}

function showClubsView(){
  clubesContainer.classList.remove('hidden');
  document.querySelectorAll('[data-list-wrap]').forEach(wrap => wrap.classList.add('hidden'));
  mainTitle.textContent = 'Elegí un club cerca tuyo';
}

// ====== Seleccionar club ======
function selectClub(clubElement, clubNombre, barrio, direccion) {
  document.querySelectorAll('.club-item').forEach(item => item.classList.remove('selected'));
  clubElement.classList.add('selected');
  selectedClub = clubNombre;
  selectedBarrio = barrio;
  selectedId = null;

  modeIndicator.textContent = `Club: ${clubNombre}`;
  mainTitle.textContent = 'Elegí tu cancha';

  showCanchasForClub(clubNombre, barrio);

  document.getElementById('d-title').textContent = clubNombre;
  document.getElementById('d-club').textContent = clubNombre;
  document.getElementById('d-barrio').textContent = barrio || '—';
  document.getElementById('d-direccion').textContent = direccion || '—';

  document.getElementById('specs-empty').classList.add('hidden');
  document.getElementById('specs').classList.remove('hidden');
  document.getElementById('d-tipo').textContent = '—';
  document.getElementById('d-capacidad').textContent = '—';
  document.getElementById('d-precio').textContent = '—';
  document.getElementById('summary').textContent = '';
  document.getElementById('btn-continue').disabled = true;
}

// ====== Canchas del club (aplica capacidad a canchas) ======
function showCanchasForClub(clubNombre, barrio) {
  clubesContainer.classList.add('hidden');
  ['clasica','cubierta','panoramica'].forEach(type => {
    document.getElementById('wrap-' + type).classList.add('hidden');
  });
  document.getElementById('wrap-' + activeType).classList.remove('hidden');

  const items = Array.from(document.querySelectorAll(`#list-${activeType} .item`));
  const filtered = items.filter(it => {
    if (it.getAttribute('data-disabled') === '1') return false;
    const club = it.dataset.club || '';
    const itemBarrio = it.dataset.barrio || '';
    if (!(club === clubNombre && itemBarrio === barrio)) return false;

    if (selectedCap === 'individual' && Number(it.dataset.capacidad) !== 2) return false;
    if (selectedCap === 'equipo'     && Number(it.dataset.capacidad) !== 4) return false;

    if (selectedCity && (it.dataset.ciudad || '') !== selectedCity) return false;
    return true;
  });

  pagestate[activeType].filtered = filtered;
  pagestate[activeType].total = filtered.length;
  pagestate[activeType].pages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  pagestate[activeType].page = 1;

  renderPage(activeType);
  updateCount();
  wireItems();
}

// ====== Selección de cancha ======
function handleSelect(el){
  document.querySelectorAll('#list-' + activeType + ' .item').forEach(i => i.classList.remove('active'));
  el.classList.add('active');

  selectedId = el.dataset.id;
  const tipo = el.dataset.tipo;
  const precioNum = Number(el.dataset.precio || 0);
  const precioStr = precioNum.toLocaleString('es-AR', {minimumFractionDigits:2});
  const labelTipo = (tipo==='clasica'?'Clásica':(tipo==='cubierta'?'Cubierta':'Panorámica'));

  document.querySelector('#d-img img').src = imgMap[tipo] || imgMap['clasica'];

  document.getElementById('d-title').textContent     = '#' + selectedId + ' — ' + el.dataset.nombre;
  document.getElementById('d-tipo').textContent      = labelTipo;
  document.getElementById('d-club').textContent      = el.dataset.club || '—';
  document.getElementById('d-barrio').textContent    = el.dataset.barrio || '—';
  document.getElementById('d-direccion').textContent = el.dataset.direccionClub || '—';
  document.getElementById('d-capacidad').textContent = el.dataset.capacidad + ' jugadores';
  document.getElementById('d-precio').textContent    = '$ ' + precioStr;
  document.getElementById('summary').textContent     = 'Total: $ ' + precioStr;

  document.getElementById('specs-empty').classList.add('hidden');
  document.getElementById('specs').classList.remove('hidden');
  document.getElementById('btn-continue').disabled = false;
}

function setDetailEmpty(){
  selectedId = null;
  document.getElementById('d-title').textContent = 'Seleccioná una sede';
  document.getElementById('d-tipo').textContent = '—';
  document.getElementById('d-club').textContent = '—';
  document.getElementById('d-barrio').textContent = '—';
  document.getElementById('d-direccion').textContent = '—';
  document.getElementById('d-capacidad').textContent = '—';
  document.getElementById('d-precio').textContent = '—';
  document.getElementById('summary').textContent = '$ —';
  document.getElementById('btn-continue').disabled = true;
  document.getElementById('specs').classList.add('hidden');
  document.getElementById('specs-empty').classList.remove('hidden');
  document.querySelector('#d-img img').src = imgMap[activeType] || imgMap['clasica'];
  mainTitle.textContent = 'Elegí un club cerca tuyo';
}

function wireItems(){
  document.querySelectorAll('#list-' + activeType + ' .item').forEach(it=>{
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

document.getElementById('btn-continue').addEventListener('click', () => {
  if (!selectedId) return;
  window.location.href = 'reservas.php?cancha=' + encodeURIComponent(selectedId);
});

function getAllItems(type){
  const list = document.getElementById('list-'+type);
  return list ? Array.from(list.querySelectorAll('.item')) : [];
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
  info.textContent = (total>0) ? 'Página ' + page + ' de ' + pages + ' — ' + total + ' resultados' : 'Sin resultados';

  const prevBtn = document.querySelector('[data-prev="' + type + '"]');
  const nextBtn = document.querySelector('[data-next="' + type + '"]');
  if (prevBtn && nextBtn){
    prevBtn.disabled = (page<=1 || total===0);
    nextBtn.disabled = (page>=pages || total===0);
    prevBtn.onclick = function(){ if (pagestate[type].page>1){ pagestate[type].page--; renderPage(type); updateCount(); } };
    nextBtn.onclick = function(){ if (pagestate[type].page<pages){ pagestate[type].page++; renderPage(type); updateCount(); } };
  }
  if (type===activeType) wireItems();
}

function updateCount(count = null){
  document.getElementById('count').textContent = (count !== null) ? count : pagestate[activeType].total;
}

// ====== Inicial ======
window.addEventListener('DOMContentLoaded', () => {
  renderClubList(); // capacidad no afecta esta vista
});
</script>
