<?php
/* =========================================================================
 * file: admin/notificaciones/notificaciones.php  (ADMIN)
 * Cambios solicitados:
 * - Rediseño visual (alineado con reportes: título/mensaje envuelven)
 * - Columna y filtro: ORIGEN (enum: sistema | app | recepcion | proveedor | cliente)
 * - Origen como pill con color según su tipo
 * - Se elimina columna "Indicación" (redundante)
 * - Botón/indicador "Visto" con diseño de cuadrito:
 *      • No leído: cuadro vacío
 *      • Leído: cuadro con ✓
 * - Paginación + “Cargar más”
 * - Marcar como leída (no se puede desmarcar)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header("Location: ../login.php");
  exit;
}

/* CSRF */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

/* Admin actual */
$admin_id = (int)$_SESSION['usuario_id'];
$uids = [$admin_id];
$placeholders = implode(',', array_fill(0, count($uids), '?'));
$typesIn = str_repeat('i', count($uids));

/* ====== Filtros ====== */
$estado = $_GET['estado'] ?? 'todas';   // todas | no_leidas | leidas
$tipo   = $_GET['tipo']   ?? '';
$origen = $_GET['origen'] ?? '';        // sistema | app | recepcion | proveedor | cliente | ''

/* ====== Paginación ====== */
$page   = max(1, (int)($_GET['page']  ?? 1));
$limit  = min(100, max(10, (int)($_GET['limit'] ?? 30)));
$offset = ($page - 1) * $limit;

/* Base WHERE: notificaciones dirigidas al admin */
$where  = " WHERE usuario_id IN ($placeholders) ";
$params = $uids;
$types  = $typesIn;

/* Filtro estado */
if ($estado === 'no_leidas') {
  $where .= " AND leida = 0 ";
} elseif ($estado === 'leidas') {
  $where .= " AND leida = 1 ";
}

/* Filtro tipo */
if ($tipo !== '') {
  $where   .= " AND tipo = ? ";
  $params[] = $tipo;
  $types   .= 's';
}

/* Filtro origen (enum fijo) */
$ORIGENES_ENUM = ['sistema','app','recepcion','proveedor','cliente'];
if ($origen !== '' && in_array($origen, $ORIGENES_ENUM, true)) {
  $where   .= " AND origen = ? ";
  $params[] = $origen;
  $types   .= 's';
}

/* Badge no leídas */
$sqlCountUnread = "
  SELECT COUNT(*) AS cant
  FROM notificaciones
  WHERE usuario_id IN ($placeholders) AND leida = 0
";
$stmt = $conn->prepare($sqlCountUnread);
$stmt->bind_param($typesIn, ...$uids);
$stmt->execute();
$noLeidas = (int)($stmt->get_result()->fetch_assoc()['cant'] ?? 0);
$stmt->close();

/* Total filtrado */
$sqlTotal = "SELECT COUNT(*) AS total FROM notificaciones $where";
$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();
$hasMore = ($offset + $limit) < $total;

/* Tipos disponibles (para este admin) */
$sqlTipos = "
  SELECT DISTINCT tipo
  FROM notificaciones
  WHERE usuario_id IN ($placeholders)
  ORDER BY tipo ASC
";
$stmt = $conn->prepare($sqlTipos);
$stmt->bind_param($typesIn, ...$uids);
$stmt->execute();
$tiposDisponibles = [];
$rsTipos = $stmt->get_result();
while ($row = $rsTipos->fetch_assoc()) $tiposDisponibles[] = $row['tipo'];
$stmt->close();

/* Listado paginado */
$sqlList = "
  SELECT notificacion_id, usuario_id, tipo, origen, titulo, mensaje, creada_en, leida
  FROM notificaciones
  $where
  ORDER BY creada_en DESC, notificacion_id DESC
  LIMIT ? OFFSET ?
";
$paramsList = array_merge($params, [$limit, $offset]);
$typesList  = $types . 'ii';

$stmt = $conn->prepare($sqlList);
$stmt->bind_param($typesList, ...$paramsList);
$stmt->execute();
$notis = $stmt->get_result();
$stmt->close();

/* ==== Helpers UI ==== */
function tipoToLabel(string $tipo): string {
  static $map = [
    'perfil_actualizado'    => 'Actualización del perfil',
    'reserva_nueva'         => 'Nueva reserva',
    'reserva_cancelada'     => 'Reserva cancelada',
    'torneo_nuevo'          => 'Nuevo torneo',
    'torneo_eliminado'      => 'Torneo eliminado',
    'reporte_generado'      => 'Reporte generado',
    'reporte_nuevo'         => 'Nuevo reporte',
    'reporte_resuelto'      => 'Reporte resuelto',
    'cliente_alta'          => 'Alta de cliente',
    'pago_confirmado'       => 'Pago confirmado',
    'pago_club_confirmado'  => 'Pago club confirmado',
    'pago_club_pendiente'   => 'Pago club pendiente',
  ];
  return $map[$tipo] ?? ucwords(str_replace('_', ' ', $tipo));
}
function tipoToClass(string $tipo): string {
  if (str_starts_with($tipo, 'reserva')) return 'notif-tipo-pill notif-tipo-reserva';
  if (str_starts_with($tipo, 'torneo'))  return 'notif-tipo-pill notif-tipo-torneo';
  if (str_starts_with($tipo, 'reporte')) return 'notif-tipo-pill notif-tipo-reporte';
  if (str_starts_with($tipo, 'pago'))    return 'notif-tipo-pill notif-tipo-pago';
  if (str_starts_with($tipo, 'cliente')) return 'notif-tipo-pill notif-tipo-cliente';
  return 'notif-tipo-pill notif-tipo-sistema';
}
function origenToClass(string $o): string {
  $o = strtolower($o);
  return match ($o) {
    'sistema'    => 'orig-pill orig-sistema',
    'app'        => 'orig-pill orig-app',
    'recepcion'  => 'orig-pill orig-recepcion',
    'proveedor'  => 'orig-pill orig-proveedor',
    'cliente'    => 'orig-pill orig-cliente',
    default      => 'orig-pill orig-sistema',
  };
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<main>
  <div class="section">
    <div class="section-header" style="align-items:center; gap:12px; flex-wrap:wrap;">
      <h2 style="margin-right:auto; display:flex; align-items:center; gap:10px; margin:0;">
        Notificaciones
        <span id="badge-no-leidas" class="badge-count <?= $noLeidas>0?'':'is-zero' ?>">
          <span class="dot"></span>
          <strong><?= $noLeidas ?></strong> sin leer
        </span>
      </h2>
    </div>

    <!-- Filtros -->
    <form method="GET" action="notificaciones.php" class="filterbar" id="filtros">
      <div class="f-field">
        <label class="f-label">Estado</label>
        <select name="estado" class="f-select" id="f-estado">
          <option value="todas"     <?= $estado==='todas'?'selected':'' ?>>Leídas y no leídas</option>
          <option value="no_leidas" <?= $estado==='no_leidas'?'selected':'' ?>>No leídas</option>
          <option value="leidas"    <?= $estado==='leidas'?'selected':'' ?>>Leídas</option>
        </select>
      </div>

      <div class="f-field">
        <label class="f-label">Tipo</label>
        <select name="tipo" class="f-select" id="f-tipo">
          <option value="" <?= $tipo===''?'selected':'' ?>>Todos</option>
          <?php foreach ($tiposDisponibles as $t): ?>
            <option value="<?= h($t) ?>" <?= $tipo===$t?'selected':'' ?>><?= h(tipoToLabel($t)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="f-field">
        <label class="f-label">Origen</label>
        <select name="origen" class="f-select" id="f-origen">
          <option value="" <?= $origen===''?'selected':'' ?>>Todos</option>
          <?php foreach ($ORIGENES_ENUM as $o): ?>
            <option value="<?= h($o) ?>" <?= $origen===$o?'selected':'' ?>><?= h(ucfirst($o)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <input type="hidden" name="page"  value="<?= (int)$page ?>">
      <input type="hidden" name="limit" value="<?= (int)$limit ?>">
    </form>

    <style>
      :root{ --brand:#0f766e; }

      /* === Filtros === */
      .filterbar{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin:14px 0 16px}
      .f-field{display:flex;flex-direction:column;gap:6px;min-width:220px}
      .f-label{font-size:12px;color:#586168;font-weight:600;letter-spacing:.3px}
      .f-select{
        width:100%; padding:10px 12px; border:1px solid #d6dadd; border-radius:10px; background:#fff;
        outline:none; transition:border-color .2s, box-shadow .2s; box-shadow:0 1px 0 rgba(0,0,0,.03)
      }
      .f-select:focus{ border-color:#1bab9d; box-shadow:0 0 0 3px rgba(27,171,157,.12) }

      /* Badge */
      .badge-count{
        display:inline-flex;align-items:center;gap:8px;padding:4px 12px;border-radius:999px;
        background:#e6f7f4;color:#0b6158;border:1px solid #b7e6de;font-size:13px
      }
      .badge-count .dot{ width:8px;height:8px;border-radius:50%;background:#1bab9d; box-shadow:0 0 0 3px rgba(27,171,157,.15) }
      .badge-count.is-zero{ background:#f3f4f6;color:#6b7280;border-color:#e5e7eb }
      .badge-count.is-zero .dot{ background:#cbd5e1; box-shadow:none }

      /* === Tabla (estilo reportes) === */
      table{ width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:12px; overflow:hidden; table-layout:fixed; }
      thead th{ position:sticky; top:0; background:#f8fafc; z-index:1; text-align:left; font-weight:700; padding:10px 12px; font-size:13px; color:#334155; border-bottom:1px solid #e5e7eb; }
      tbody td{ padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
      tbody tr:hover{ background:#f7fbfd; }
      .row-new{ background:#f7fbfd; }

      /* Anchos columnas */
      .col-titulo { width:220px; }
      .col-msj    { width:500px; }
      .col-fecha  { width:70px;  }
      .col-hora   { width:60px;  }
      .col-tipo   { width:100px; text-align:center; }
      .col-orig   { width:100px; text-align: center; }
      .col-visto  { width:60px;  text-align:center; }

      /* Título/Mensaje envuelven */
      .title-text, .desc-text {
        white-space:normal; word-wrap:break-word; font-size:14px; color:#0f172a;
      }
      .sub{ font-size:12px; color:#64748b; }

      /* Tipo pill */
      .notif-tipo-pill{
        display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;
        text-transform:uppercase;letter-spacing:.4px;border:1px solid transparent;
      }
      .notif-tipo-reserva{background:#e0f7e9;color:#19733b;border-color:#b7e6c7}
      .notif-tipo-torneo {background:#e3f2fd;color:#1a5fb4;border-color:#c7e3ff}
      .notif-tipo-reporte{background:#fff3e0;color:#e65100;border-color:#ffd8a8}
      .notif-tipo-pago   {background:#ede7f6;color:#5e35b1;border-color:#d1c4e9}
      .notif-tipo-cliente{background:#f1f8e9;color:#2e7d32;border-color:#d4e8c6}
      .notif-tipo-sistema{background:#eef2f7;color:#415a77;border-color:#d8e0ea}

      /* Origen pill (colores por origen) */
      .orig-pill{
        display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700;
        border:1px solid transparent; white-space:nowrap;
      }
      .orig-sistema  { background:#eef2f7; color:#415a77; border-color:#d8e0ea; }
      .orig-app      { background:#e0ecff; color:#1e40af; border-color:#bfd7ff; }
      .orig-recepcion{ background:#fff7e6; color:#92400e; border-color:#ffe2b8; }
      .orig-proveedor{ background:#e6f7f4; color:#0f766e; border-color:#c8efe8; }
      .orig-cliente  { background:#fde8f1; color:#a11a5b; border-color:#f8c7da; }

      /* Visto — indicador (cuadrito limpio -> con ✓) */
      .read-indicator{
        display:inline-flex; align-items:center; justify-content:center;
        width:34px; height:34px; border-radius:10px;
        border:1px solid #d6dadd; background:#fff; color:#0f766e;
        cursor:pointer; transition:background .15s ease, transform .02s ease, border-color .15s ease, box-shadow .15s ease;
        box-shadow:0 1px 0 rgba(0,0,0,.03);
      }
      .read-indicator:hover{ background:#f1f5f9; border-color:#cbd5e1; }
      .read-indicator:active{ transform:scale(0.98); }
      .read-indicator[disabled]{ opacity:.6; cursor:default; }
      .read-indicator.is-checked{
        background:#e6f7f4; border-color:#c8efe8;
        box-shadow:0 0 0 3px rgba(27,171,157,.12);
      }
      .read-indicator.is-checked::after{
        content:'✓'; font-size:18px; line-height:1; font-weight:900;
      }
      .read-indicator-static{ pointer-events:none; }

      /* Paginación / Cargar más */
      .load-more-wrap{display:flex;justify-content:center;margin:12px 0}
      .btn-load{
        background:#e9eef1;color:#1f2937;border:0;border-radius:10px;padding:9px 14px;
        cursor:pointer;font-weight:600
      }
      .btn-load:disabled{opacity:.6;cursor:default}
    </style>

    <div id="noti-list">
      <table>
        <thead>
          <tr>
            <th class="col-titulo">Título</th>
            <th class="col-msj">Mensaje</th>
            <th class="col-fecha">Fecha</th>
            <th class="col-hora">Hora</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-orig">Origen</th>
            <th class="col-visto">Visto</th>
          </tr>
        </thead>
        <tbody id="noti-rows">
        <?php if ($notis->num_rows): while($n=$notis->fetch_assoc()):
          $isLeida   = (int)$n['leida'] === 1;
          $rowClass  = $isLeida ? '' : 'row-new';
          $pillClass = tipoToClass((string)$n['tipo']);
          $labelTipo = tipoToLabel((string)$n['tipo']);
          $origVal   = strtolower($n['origen'] ?? 'sistema');
          $origLbl   = ucfirst($origVal);
          $origClass = origenToClass($origVal);

          $ts = strtotime($n['creada_en'] ?? 'now');
          $fechaFmt = date('d/m', $ts);
          $horaFmt  = date('H:i', $ts);
        ?>
          <tr class="<?= $rowClass ?>" data-nid="<?= (int)$n['notificacion_id'] ?>">
            <td class="col-titulo"><div class="title-text"><strong><?= h($n['titulo']) ?></strong></div></td>
            <td class="col-msj"><div class="desc-text"><?= nl2br(h($n['mensaje'])) ?></div></td>
            <td class="col-fecha"><?= h($fechaFmt) ?></td>
            <td class="col-hora"><?= h($horaFmt) ?></td>
            <td class="col-tipo"><span class="<?= $pillClass ?>"><?= h($labelTipo) ?></span></td>
            <td class="col-orig"><span class="<?= $origClass ?>"><?= h($origLbl) ?></span></td>
            <td class="col-visto">
              <?php if (!$isLeida): ?>
                <form class="form-mark-read" method="POST" action="notificacionesAction.php" style="display:inline-block;">
                  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                  <input type="hidden" name="action" value="mark_read">
                  <input type="hidden" name="notificacion_id" value="<?= (int)$n['notificacion_id'] ?>">
                  <!-- Cuadrito limpio (sin icono). El ✓ aparece cuando pase a leído -->
                  <button type="submit" class="read-indicator" title="Marcar como leída" aria-label="Marcar como leída"></button>
                </form>
              <?php else: ?>
                <!-- Cuadrito con ✓ -->
                <span class="read-indicator read-indicator-static is-checked" title="Leída" aria-label="Leída"></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="7" style="text-align:center;">No tienes notificaciones.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>

      <?php if ($hasMore): ?>
        <div class="load-more-wrap">
          <button id="load-more" class="btn-load"
                  data-next="<?= (int)($page+1) ?>"
                  data-limit="<?= (int)$limit ?>">Cargar más</button>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
/* Filtros por GET con recarga parcial */
(function(){
  const form = document.getElementById('filtros');
  if (!form) return;

  const resetPage = ()=>{ const p=form.querySelector('input[name="page"]'); if(p) p.value='1'; };

  async function submitAjax(){
    const params = new URLSearchParams(new FormData(form));
    const url = `${form.action}?${params.toString()}`;
    try{
      const res  = await fetch(url, { headers: { 'X-Requested-With':'fetch' } });
      const html = await res.text();
      const doc  = new DOMParser().parseFromString(html, 'text/html');

      const newList  = doc.querySelector('#noti-list');
      const newBadge = doc.querySelector('#badge-no-leidas');
      if (newList)  document.querySelector('#noti-list').replaceWith(newList);
      if (newBadge) document.querySelector('#badge-no-leidas').replaceWith(newBadge);

      history.replaceState({}, '', url);
      wireMarkRead();
      wireLoadMore();
    }catch(e){
      form.submit();
    }
  }

  form.querySelectorAll('select').forEach(sel=>{
    sel.addEventListener('change', ()=>{ resetPage(); submitAjax(); });
  });
})();

/* Marcar como leída (botón cuadrito) */
function wireMarkRead(){
  document.querySelectorAll('.form-mark-read').forEach(f => {
    if (f.dataset.wired) return;
    f.dataset.wired = '1';

    f.addEventListener('submit', async (ev)=>{
      ev.preventDefault();
      const btn = f.querySelector('.read-indicator');
      btn.disabled = true;

      try{
        const res = await fetch(f.action, {
          method:'POST',
          body:new FormData(f),
          headers:{ 'X-Requested-With':'fetch' }
        });
        if (!res.ok && res.status !== 204) throw 0;

        // Actualizar la fila (si el filtro es "no leídas", quitamos; si no, convertimos a leído)
        const row   = f.closest('tr');
        const tbody = row.parentElement;
        const estadoSel = document.getElementById('f-estado');
        const currentEstado = (estadoSel && estadoSel.value) || 'todas';

        if (currentEstado === 'no_leidas') {
          row.remove();
          if (!tbody.querySelector('tr')) {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="7" style="text-align:center;">No tienes notificaciones.</td>';
            tbody.appendChild(tr);
          }
        } else {
          const vistoCell = row.querySelector('.col-visto');
          vistoCell.innerHTML = '<span class="read-indicator read-indicator-static is-checked" title="Leída" aria-label="Leída"></span>';
          row.classList.remove('row-new');
        }

        // Decrementar badge
        const badge = document.getElementById('badge-no-leidas');
        if (badge){
          const strong = badge.querySelector('strong');
          let n = parseInt(strong.textContent||'0',10);
          n = Math.max(0, n-1);
          strong.textContent = String(n);
          badge.classList.toggle('is-zero', n===0);
        }
      }catch(e){
        f.submit();
      }
    });
  });
}
wireMarkRead();

/* Cargar más */
function wireLoadMore(){
  const btn  = document.getElementById('load-more');
  const form = document.getElementById('filtros');
  if (!btn || !form || btn.dataset.wired) return;
  btn.dataset.wired = '1';

  btn.addEventListener('click', async ()=>{
    btn.disabled = true;
    try{
      const params = new URLSearchParams(new FormData(form));
      params.set('page', btn.dataset.next || '2');
      params.set('limit', btn.dataset.limit || '30');
      const url = `${form.action}?${params.toString()}`;

      const res  = await fetch(url, { headers:{ 'X-Requested-With':'fetch' } });
      const html = await res.text();
      const doc  = new DOMParser().parseFromString(html, 'text/html');

      const incomingRows = doc.querySelectorAll('#noti-rows > tr');
      const tbody = document.querySelector('#noti-rows');
      incomingRows.forEach(tr => tbody.appendChild(tr));

      const newBtn = doc.getElementById('load-more');
      if (newBtn) {
        btn.dataset.next  = newBtn.dataset.next;
        btn.dataset.limit = newBtn.dataset.limit || btn.dataset.limit;
        btn.disabled = false;
      } else {
        btn.parentElement.remove();
      }
      wireMarkRead();
    }catch(e){
      btn.disabled = false;
    }
  });
}
wireLoadMore();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
