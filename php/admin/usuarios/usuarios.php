<?php
/* =========================================================================
 * file: admin/usuarios/usuarios.php
 * Vistas: clientes | proveedores | recepcionistas
 * - Fechas dd/mm
 * - Clientes: solo emails @gmail.com
 * - Filtros específicos por vista
 * - Tabs fondo blanco / activo color sistema
 * - Pills: género y puntos
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';

$view  = $_GET['view'] ?? 'clientes';
$valid = ['clientes','proveedores','recepcionistas'];
if (!in_array($view,$valid,true)) $view = 'clientes';

/* label para botón Agregar */
$labelMap = [
  'clientes'       => 'cliente',
  'proveedores'    => 'proveedor',
  'recepcionistas' => 'recepcionista'
];
$labelAdd = $labelMap[$view] ?? 'Usuario';

/* === DATA === */
if ($view === 'clientes') {
  // Solo @gmail.com
  $sql = "
    SELECT 
      u.user_id, u.nombre, u.email, u.puntos, u.fecha_registro,
      cd.genero, cd.telefono, cd.ciudad,
      COUNT(r.reserva_id) AS total_reservas
    FROM usuarios u
    LEFT JOIN cliente_detalle cd ON cd.cliente_id = u.user_id
    LEFT JOIN reservas r ON r.creador_id = u.user_id
    WHERE u.rol='cliente' AND u.email LIKE '%@gmail.com'
    GROUP BY u.user_id, u.nombre, u.email, u.puntos, u.fecha_registro, cd.genero, cd.telefono, cd.ciudad
    ORDER BY u.fecha_registro DESC
  ";
} elseif ($view === 'proveedores') {
  $sql = "
    SELECT 
      u.user_id, u.nombre, u.email, u.fecha_registro,
      pd.nombre_club, pd.telefono, pd.direccion, pd.ciudad
    FROM usuarios u
    LEFT JOIN proveedores_detalle pd ON pd.proveedor_id = u.user_id
    WHERE u.rol='proveedor'
    ORDER BY COALESCE(pd.nombre_club, u.nombre) ASC
  ";
} else {
  $sql = "
    SELECT 
      u.user_id, u.nombre, u.email, u.fecha_registro,
      rd.proveedor_id, rd.fecha_asignacion,
      COALESCE(pd.nombre_club, pu.nombre) AS proveedor_label
    FROM usuarios u
    LEFT JOIN recepcionista_detalle rd ON rd.recepcionista_id = u.user_id
    LEFT JOIN usuarios pu ON pu.user_id = rd.proveedor_id
    LEFT JOIN proveedores_detalle pd ON pd.proveedor_id = rd.proveedor_id
    WHERE u.rol='recepcionista'
    ORDER BY u.fecha_registro DESC
  ";
}
$res  = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

/* === AUX LISTAS PARA FILTROS === */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$ciudades = [];
$clubs    = [];
$proveedores = [];

if ($view === 'clientes' || $view === 'proveedores') {
  foreach ($rows as $r) {
    $c = trim($r['ciudad'] ?? '');
    if ($c!=='') $ciudades[$c] = true;
  }
  ksort($ciudades, SORT_NATURAL|SORT_FLAG_CASE);
}
if ($view === 'proveedores') {
  foreach ($rows as $r) {
    $lab = trim(($r['nombre_club'] ?? ''));
    if ($lab!=='') $clubs[$lab]=true;
  }
  ksort($clubs, SORT_NATURAL|SORT_FLAG_CASE);
}
if ($view === 'recepcionistas') {
  $ps = $conn->query("
    SELECT u.user_id, COALESCE(pd.nombre_club,u.nombre) AS label
    FROM usuarios u
    LEFT JOIN proveedores_detalle pd ON pd.proveedor_id=u.user_id
    WHERE u.rol='proveedor'
    ORDER BY label ASC
  ");
  if ($ps) $proveedores = $ps->fetch_all(MYSQLI_ASSOC);
}

/* === HELPERS === */
function ddmm(?string $dt): string {
  if (!$dt) return '—';
  $t = strtotime($dt);
  return $t ? date('d/m', $t) : '—';
}
function generoLabelClase(?string $g): array {
  $g = $g ? strtolower($g) : '';
  if ($g === 'masculino')        return ['Hombre','pg-m'];
  if ($g === 'femenino')         return ['Mujer','pg-f'];
  if ($g === 'otro')             return ['Otro','pg-o'];
  if ($g === 'prefiero_no_decir')return ['No elegido','pg-o'];
  return ['—','pg-o'];
}
function puntosClase(int $p): string {
  if ($p === 0)  return 'pp-0';   // rojo suave
  if ($p < 100)  return 'pp-mid'; // azul
  return 'pp-high';               // verde
}
?>
<div class="section">

  <!-- Header + Tabs -->
  <div class="section-header" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
      <a class="tab <?= $view==='clientes'?'active':'' ?>" href="?view=clientes">Clientes</a>
      <a class="tab <?= $view==='proveedores'?'active':'' ?>" href="?view=proveedores">Proveedores</a>
      <a class="tab <?= $view==='recepcionistas'?'active':'' ?>" href="?view=recepcionistas">Recepcionistas</a>
    </div>
    <button onclick="location.href='./usuariosForm.php?rol=<?= $view ?>'" class="btn-add">
      Agregar <?= $labelAdd ?>
    </button>
  </div>

  <style>
    :root{
      --brand:#0f766e; /* color principal del sistema */
    }

    .btn-add {
      display:inline-flex; align-items:center; gap:8px; padding:8px 12px;
      text-decoration:none; font-weight:600; font-size:14px; transition:filter .15s ease, transform .03s ease; white-space:nowrap;
    }
    .btn-add:hover { background:#139488; }

    /* Tabs fondo blanco; activo = brand */
    .tab{
      display:inline-block; padding:6px 10px; border-radius:999px; text-decoration:none;
      background:#fff; border:1px solid #d6dadd; color:#334155; font-weight:600; font-size:13px;
    }
    .tab.active{   background: #1bab9d;
; color:#fff; border-color:#1bab9d; }

    /* Filtros (mismo diseño de canchas) */
    .fbar {
      display:grid;
      gap:12px; align-items:end; background:#fff; padding:14px 16px;
      border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08); margin-bottom:12px;
    }
    .fbar.cols-4{ grid-template-columns: minmax(280px,1fr) minmax(200px,220px) minmax(160px,180px) minmax(160px,180px); }
    .fbar.cols-5{ grid-template-columns: minmax(280px,1fr) minmax(200px,220px) minmax(160px,180px) minmax(120px,140px) minmax(120px,140px); }
/* cols-6: usado sólo en Recepcionistas */
    .fbar.cols-6{
    /* 1) Por nombre (ancho grande)
        2) Proveedor (medio)
        3-6) Asignación Día/Mes + Registro Día/Mes (todos iguales) */
    grid-template-columns:
        minmax(280px,1fr)
        minmax(200px,220px)
        repeat(4, minmax(120px,140px));
    }
    @media (max-width:1100px){ .fbar{ grid-template-columns: repeat(2,minmax(220px,1fr)); } }
    @media (max-width:640px){ .fbar{ grid-template-columns: 1fr; } }

    .f{ display:flex; flex-direction:column; gap:6px; }
    .f label{ font-size:12px; color:#586168; font-weight:700; }
    .f input[type="text"], .f select{
      padding:9px 10px; border:1px solid #d6dadd; border-radius:10px; background:#fff; outline:none;
    }

    /* Tabla */
    table{
      width:100%; border-collapse:separate; border-spacing:0; background:#fff;
      border-radius:12px; overflow:hidden; table-layout:fixed;
    }
    thead th{
      position:sticky; top:0; background:#f8fafc; z-index:1;
      text-align:left; font-weight:700; padding:10px 12px;
      font-size:13px; color:#334155; border-bottom:1px solid #e5e7eb;
    }
    tbody td{ padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
    tbody tr:hover{ background:#f7fbfd; }
    .truncate{ display:block; max-width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    /* === Col widths (tocá estos valores a gusto) === */
    /* CLIENTES */
    .col-id      { width:30px; }
    .col-nombre  { width:135px; }
    .col-email   { width:170px; }
    .col-genero  { width:80px; }
    .col-puntos  { width:80px; }
    .col-res     { width:80px; }
    .col-tel     { width:120px; }
    .col-ciudad  { width:110px; }
    .col-fecha   { width:60px; }
    .col-acc     { width:140px; }

    /* PROVEEDORES (reusan varias) */
    .col-club    { width:145px; }
    .col-dir     { width:145px; }

    /* RECEPCIONISTAS */
    .col-prov    { width:220px; }

    /* Pills puntos */
    .pill-pts{
      display:inline-block; padding:3px 8px; border-radius:999px;
      font-size:12px; font-weight:bold; border:1px solid transparent;
    }
    .pill-pts.pp-0   { background:#fde8e8; border-color:#f8c9c9; color:#7f1d1d; }
    .pill-pts.pp-mid { background:#e0ecff; border-color:#bfd7ff; color:#1e40af; }
    .pill-pts.pp-high{ background:#e6f7f4; border-color:#c8efe8; color:#0f766e; }

    /* Pills género */
    .pill-gen{
      display:inline-block; padding:2px 8px; border-radius:999px;
      font-size:12px; border:1px solid transparent; white-space:nowrap;
    }
    .pg-m{ background:#e0ecff; border-color:#bfd7ff; color:#1e40af; } /* Hombre: azul */
    .pg-f{ background:#fde8f1; border-color:#f8c7da; color:#a11a5b; } /* Mujer: rosa */
    .pg-o{ background:#f1f5f9; border-color:#e2e8f0; color:#475569; } /* Otro/ND: neutro */

    /* Acciones (mismo look, con gap) */
    .btn-action{
      appearance:none; border:none; border-radius:8px;
      padding:6px 10px; cursor:pointer; font-weight:700;
    }
    .btn-action.edit   { background:#e0ecff; border:1px solid #bfd7ff; color:#1e40af; }
    .btn-action.delete { background:#fde8e8; border:1px solid #f8c9c9; color:#7f1d1d; }

    .actions{ display:flex; flex-direction:column; gap:6px; }
    @media (min-width:680px){ .actions{ flex-direction:row; } }
  </style>

  <!-- Filtros -->
  <?php if ($view==='clientes'): ?>
    <div class="fbar cols-5" id="filters">
      <div class="f">
        <label>Por nombre</label>
        <input type="text" id="f-q" placeholder="Ej: Juan">
      </div>
      <div class="f">
        <label>Ciudad</label>
        <select id="f-ciudad">
          <option value="">Todas</option>
          <?php foreach(array_keys($ciudades) as $c) echo '<option value="'.h($c).'">'.h($c).'</option>'; ?>
        </select>
      </div>
      <div class="f">
        <label>Registro (Día)</label>
        <select id="f-r-dia">
          <option value="">Todos</option>
          <?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Registro (Mes)</label>
        <select id="f-r-mes">
          <option value="">Todos</option>
          <?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Género</label>
        <select id="f-genero">
          <option value="">Todos</option>
          <option value="hombre">Hombre</option>
          <option value="mujer">Mujer</option>
          <option value="otro">Otro</option>
          <option value="prefiere no decir">Prefiere no decir</option>
        </select>
      </div>
    </div>

  <?php elseif ($view==='proveedores'): ?>
    <div class="fbar cols-5" id="filters">
      <div class="f">
        <label>Por nombre</label>
        <input type="text" id="f-q" placeholder="Ej: Martín">
      </div>
      <div class="f">
        <label>Club</label>
        <select id="f-club">
          <option value="">Todos</option>
          <?php foreach(array_keys($clubs) as $c) echo '<option value="'.h($c).'">'.h($c).'</option>'; ?>
        </select>
      </div>
      <div class="f">
        <label>Ciudad</label>
        <select id="f-ciudad">
          <option value="">Todas</option>
          <?php foreach(array_keys($ciudades) as $c) echo '<option value="'.h($c).'">'.h($c).'</option>'; ?>
        </select>
      </div>
      <div class="f">
        <label>Registro (Día)</label>
        <select id="f-r-dia">
          <option value="">Todos</option>
          <?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Registro (Mes)</label>
        <select id="f-r-mes">
          <option value="">Todos</option>
          <?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?>
        </select>
      </div>
    </div>

  <?php else: ?>
    <div class="fbar cols-6" id="filters">
      <div class="f">
        <label>Por nombre</label>
        <input type="text" id="f-q" placeholder="Ej: Ana">
      </div>
      <div class="f">
        <label>Proveedor</label>
        <select id="f-prov">
          <option value="">Todos</option>
          <?php foreach($proveedores as $p) echo '<option value="'.(int)$p['user_id'].'">'.h($p['label']).'</option>'; ?>
        </select>
      </div>
      <div class="f">
        <label>Asignación (Día)</label>
        <select id="f-a-dia">
          <option value="">Todos</option>
          <?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Asignación (Mes)</label>
        <select id="f-a-mes">
          <option value="">Todos</option>
          <?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Registro (Día)</label>
        <select id="f-r-dia">
          <option value="">Todos</option>
          <?php for($d=1;$d<=31;$d++) echo "<option>$d</option>"; ?>
        </select>
      </div>
      <div class="f">
        <label>Registro (Mes)</label>
        <select id="f-r-mes">
          <option value="">Todos</option>
          <?php for($m=1;$m<=12;$m++) echo "<option>$m</option>"; ?>
        </select>
      </div>
    </div>
  <?php endif; ?>

  <!-- TABLA -->
  <table id="tablaUsuarios">
    <thead>
      <?php if ($view==='clientes'): ?>
        <tr>
          <th class="col-id">ID</th>
          <th class="col-nombre">Nombre</th>
          <th class="col-email">Email</th>
          <th class="col-genero">Género</th>
          <th class="col-puntos">Puntos</th>
          <th class="col-res">Reservas</th>
          <th class="col-tel">Teléfono</th>
          <th class="col-ciudad">Ciudad</th>
          <th class="col-fecha">Registro</th>
          <th class="col-acc">Acciones</th>
        </tr>
      <?php elseif ($view==='proveedores'): ?>
        <tr>
          <th class="col-id">ID</th>
          <th class="col-nombre">Nombre</th>
          <th class="col-email">Email</th>
          <th class="col-club">Club</th>
          <th class="col-tel">Teléfono</th>
          <th class="col-dir">Dirección</th>
          <th class="col-ciudad">Ciudad</th>
          <th class="col-fecha">Registro</th>
          <th class="col-acc">Acciones</th>
        </tr>
      <?php else: ?>
        <tr>
          <th class="col-id">ID</th>
          <th class="col-nombre">Nombre</th>
          <th class="col-email">Email</th>
          <th class="col-prov">Proveedor</th>
          <th class="col-fecha">Asignación</th>
          <th class="col-fecha">Registro</th>
          <th class="col-acc">Acciones</th>
        </tr>
      <?php endif; ?>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="12" style="text-align:center;">Sin registros</td></tr>
      <?php else: foreach($rows as $r): ?>
        <?php if ($view==='clientes'):
          $pts    = (int)($r['puntos'] ?? 0);
          $ptsCls = puntosClase($pts);
          [$gLbl,$gCls] = generoLabelClase($r['genero'] ?? null);
          $reg   = ddmm($r['fecha_registro']);
          $day   = $r['fecha_registro'] ? (int)date('j', strtotime($r['fecha_registro'])) : '';
          $mon   = $r['fecha_registro'] ? (int)date('n', strtotime($r['fecha_registro'])) : '';
          $ciu   = $r['ciudad'] ?? '';
        ?>
          <tr
            data-text="<?= h(mb_strtolower(($r['nombre']??'').' '.($r['email']??''),'UTF-8')) ?>"
            data-ciudad="<?= h(mb_strtolower($ciu,'UTF-8')) ?>"
            data-r-dia="<?= $day ?>" data-r-mes="<?= $mon ?>"
            data-genero="<?= h(mb_strtolower($gLbl,'UTF-8')) ?>"
          >
            <td class="col-id"><?= (int)$r['user_id'] ?></td>
            <td class="col-nombre">
              <div class="truncate"><strong><?= h($r['nombre']) ?></strong></div>
            </td>
            <td class="col-email"><span class="truncate"><?= h($r['email']) ?></span></td>
            <td class="col-genero">
              <span class="pill-gen <?= $gCls ?>"><?= h($gLbl) ?></span>
            </td>
            <td class="col-puntos">
              <span class="pill-pts <?= $ptsCls ?>"><?= $pts ?></span>
            </td>
            <td class="col-res"><?= (int)($r['total_reservas'] ?? 0) ?></td>
            <td class="col-tel"><?= h(($r['telefono'] ?? '') ?: '—') ?></td>
            <td class="col-ciudad"><?= h($ciu ?: '—') ?></td>
            <td class="col-fecha"><?= h($reg) ?></td>
            <td class="col-acc">
              <div class="actions">
                <button class="btn-action edit"
                  onclick="location.href='usuariosForm.php?rol=clientes&user_id=<?= (int)$r['user_id'] ?>'">Editar</button>
                <form method="POST" action="usuariosAction.php" onsubmit="return confirm('¿Eliminar cliente?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="rol" value="clientes">
                  <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>">
                  <button type="submit" class="btn-action delete">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>

        <?php elseif ($view==='proveedores'):
          $reg = ddmm($r['fecha_registro']);
          $day = $r['fecha_registro'] ? (int)date('j', strtotime($r['fecha_registro'])) : '';
          $mon = $r['fecha_registro'] ? (int)date('n', strtotime($r['fecha_registro'])) : '';
          $club = $r['nombre_club'] ?? '';
          $ciu  = $r['ciudad'] ?? '';
        ?>
          <tr
            data-text="<?= h(mb_strtolower(($r['nombre']??'').' '.($r['email']??''),'UTF-8')) ?>"
            data-club="<?= h(mb_strtolower($club,'UTF-8')) ?>"
            data-ciudad="<?= h(mb_strtolower($ciu,'UTF-8')) ?>"
            data-r-dia="<?= $day ?>" data-r-mes="<?= $mon ?>"
          >
            <td class="col-id"><?= (int)$r['user_id'] ?></td>
            <td class="col-nombre">
              <div class="truncate"><strong><?= h($r['nombre']) ?></strong></div>
            </td>
            <td class="col-email"><span class="truncate"><?= h($r['email']) ?></span></td>
            <td class="col-club"><span class="truncate"><?= h($club ?: '—') ?></span></td>
            <td class="col-tel"><?= h(($r['telefono'] ?? '') ?: '—') ?></td>
            <td class="col-dir"><span class="truncate"><?= h(($r['direccion'] ?? '') ?: '—') ?></span></td>
            <td class="col-ciudad"><?= h($ciu ?: '—') ?></td>
            <td class="col-fecha"><?= h($reg) ?></td>
            <td class="col-acc">
              <div class="actions">
                <button class="btn-action edit"
                  onclick="location.href='usuariosForm.php?rol=proveedores&user_id=<?= (int)$r['user_id'] ?>'">Editar</button>
                <form method="POST" action="usuariosAction.php" onsubmit="return confirm('¿Eliminar proveedor?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="rol" value="proveedores">
                  <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>">
                  <button type="submit" class="btn-action delete">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>

        <?php else:
          $reg      = ddmm($r['fecha_registro']);
          $areg     = ddmm($r['fecha_asignacion'] ?? null);
          $rDay     = $r['fecha_registro'] ? (int)date('j', strtotime($r['fecha_registro'])) : '';
          $rMon     = $r['fecha_registro'] ? (int)date('n', strtotime($r['fecha_registro'])) : '';
          $aDay     = $r['fecha_asignacion'] ? (int)date('j', strtotime($r['fecha_asignacion'])) : '';
          $aMon     = $r['fecha_asignacion'] ? (int)date('n', strtotime($r['fecha_asignacion'])) : '';
          $provId   = (int)($r['proveedor_id'] ?? 0);
          $provLabel= $r['proveedor_label'] ?? '—';
        ?>
          <tr
            data-text="<?= h(mb_strtolower(($r['nombre']??''),'UTF-8')) ?>"
            data-email="<?= h(mb_strtolower(($r['email']??''),'UTF-8')) ?>"
            data-prov="<?= $provId ?: '' ?>"
            data-a-dia="<?= $aDay ?>" data-a-mes="<?= $aMon ?>"
            data-r-dia="<?= $rDay ?>" data-r-mes="<?= $rMon ?>"
          >
            <td class="col-id"><?= (int)$r['user_id'] ?></td>
            <td class="col-nombre">
              <div class="truncate"><strong><?= h($r['nombre']) ?></strong></div>
            </td>
            <td class="col-email"><span class="truncate"><?= h($r['email']) ?></span></td>
            <td class="col-prov"><span class="truncate"><?= h($provLabel) ?></span></td>
            <td class="col-fecha"><?= h($areg) ?></td>
            <td class="col-fecha"><?= h($reg) ?></td>
            <td class="col-acc">
              <div class="actions">
                <button class="btn-action edit"
                  onclick="location.href='usuariosForm.php?rol=recepcionistas&user_id=<?= (int)$r['user_id'] ?>'">Editar</button>
                <form method="POST" action="usuariosAction.php" onsubmit="return confirm('¿Eliminar recepcionista?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="rol" value="recepcionistas">
                  <input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>">
                  <button type="submit" class="btn-action delete">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
/* ===== Filtros ===== */
(function(){
  const $  = (s,root=document)=>root.querySelector(s);
  const $$ = (s,root=document)=>Array.from(root.querySelectorAll(s));
  const rows = $$('#tablaUsuarios tbody tr');
  const view = new URLSearchParams(location.search).get('view') || 'clientes';
  const norm = s => (s||'').toString().toLowerCase();

  function apply(){
    rows.forEach(tr=>{
      let show = true;

      if (view==='clientes'){
        const q       = norm($('#f-q')?.value);
        const ciudad  = norm($('#f-ciudad')?.value);
        const d       = $('#f-r-dia')?.value || '';
        const m       = $('#f-r-mes')?.value || '';
        const gen     = norm($('#f-genero')?.value);

        const vText   = tr.dataset.text   || '';
        const vCiudad = tr.dataset.ciudad || '';
        const vDia    = tr.dataset.rDia   || tr.getAttribute('data-r-dia') || '';
        const vMes    = tr.dataset.rMes   || tr.getAttribute('data-r-mes') || '';
        const vGen    = tr.dataset.genero || '';

        show = show && (q === ''      || vText.includes(q));
        show = show && (ciudad === '' || vCiudad === norm(ciudad));
        show = show && (d === ''      || String(vDia) === String(d));
        show = show && (m === ''      || String(vMes) === String(m));
        show = show && (gen === ''    || vGen === gen);
      }
      else if (view==='proveedores'){
        const q       = norm($('#f-q')?.value);
        const club    = norm($('#f-club')?.value);
        const ciudad  = norm($('#f-ciudad')?.value);
        const d       = $('#f-r-dia')?.value || '';
        const m       = $('#f-r-mes')?.value || '';

        const vText   = tr.dataset.text   || '';
        const vClub   = tr.dataset.club   || '';
        const vCiudad = tr.dataset.ciudad || '';
        const vDia    = tr.dataset.rDia   || tr.getAttribute('data-r-dia') || '';
        const vMes    = tr.dataset.rMes   || tr.getAttribute('data-r-mes') || '';

        show = show && (q === ''      || vText.includes(q));
        show = show && (club === ''   || vClub === norm(club));
        show = show && (ciudad === '' || vCiudad === norm(ciudad));
        show = show && (d === ''      || String(vDia) === String(d));
        show = show && (m === ''      || String(vMes) === String(m));
      }
      else {
        const q       = norm($('#f-q')?.value);
        const prov    = $('#f-prov')?.value || '';
        const ad      = $('#f-a-dia')?.value || '';
        const am      = $('#f-a-mes')?.value || '';
        const rd      = $('#f-r-dia')?.value || '';
        const rm      = $('#f-r-mes')?.value || '';

        const vText   = tr.dataset.text  || '';
        const vProv   = tr.dataset.prov  || tr.getAttribute('data-prov') || '';
        const vAd     = tr.dataset.aDia  || tr.getAttribute('data-a-dia') || '';
        const vAm     = tr.dataset.aMes  || tr.getAttribute('data-a-mes') || '';
        const vRd     = tr.dataset.rDia  || tr.getAttribute('data-r-dia') || '';
        const vRm     = tr.dataset.rMes  || tr.getAttribute('data-r-mes') || '';

        show = show && (q === ''     || vText.includes(q));
        show = show && (prov === ''  || vProv === prov);
        show = show && (ad === ''    || String(vAd) === String(ad));
        show = show && (am === ''    || String(vAm) === String(am));
        show = show && (rd === ''    || String(vRd) === String(rd));
        show = show && (rm === ''    || String(vRm) === String(rm));
      }

      tr.style.display = show ? '' : 'none';
    });
  }

  const listen = (id,ev='change') => { const el=$(id); if(el) el.addEventListener(ev, apply); }

  if (view==='clientes'){
    listen('#f-q','input'); listen('#f-ciudad'); listen('#f-r-dia'); listen('#f-r-mes'); listen('#f-genero');
  }
  if (view==='proveedores'){
    listen('#f-q','input'); listen('#f-club'); listen('#f-ciudad'); listen('#f-r-dia'); listen('#f-r-mes');
  }
  if (view==='recepcionistas'){
    listen('#f-q','input'); listen('#f-prov');
    listen('#f-a-dia'); listen('#f-a-mes'); listen('#f-r-dia'); listen('#f-r-mes');
  }

  apply();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
