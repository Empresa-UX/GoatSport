<?php
/* =========================================================================
 * file: admin/notificaciones/notificaciones.php
 * Basado en: recepcionista/notificaciones/notificaciones.php
 * Para rol: admin
 * - Muestra notificaciones donde:
 *      usuario_id = admin actual
 *   (NO filtramos por origen; ya se separa por usuario_id)
 * - Filtros: estado (todas / no_leidas / leidas), tipo
 * - Paginación + "Cargar más"
 * - Marcar como leída (NO se puede desmarcar)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* UI toggles (por ahora no mostramos origen/dest) */
const SHOW_ORIGEN = false;
const SHOW_DEST   = false;

/* CSRF */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

/* UID admin */
$admin_id = (int)$_SESSION['usuario_id'];
$uids = [$admin_id];
$placeholders = implode(',', array_fill(0, count($uids), '?'));
$typesIn = str_repeat('i', count($uids));

/* ====== Filtros ====== */
$estado = $_GET['estado'] ?? 'todas';   // todas | no_leidas | leidas
$tipo   = $_GET['tipo']   ?? '';

/* ====== Paginación ====== */
$page   = max(1, (int)($_GET['page']  ?? 1));
$limit  = min(100, max(10, (int)($_GET['limit'] ?? 30)));
$offset = ($page - 1) * $limit;

/* Base WHERE:
 * - notificaciones dirigidas al admin (usuario_id IN ...)
 *   NO filtramos por origen, porque ya está separado por usuario_id
 */
$where  = " WHERE usuario_id IN ($placeholders) ";
$params = $uids;
$types  = $typesIn;

if ($estado === 'no_leidas') {
    $where .= " AND leida = 0 ";
} elseif ($estado === 'leidas') {
    $where .= " AND leida = 1 ";
}

if ($tipo !== '') {
    $where   .= " AND tipo = ? ";
    $params[] = $tipo;
    $types   .= 's';
}

/* Conteo no leídas (badge global) */
$sqlCountUnread = "
    SELECT COUNT(*) AS cant 
    FROM notificaciones 
    WHERE usuario_id IN ($placeholders) 
      AND leida = 0
";
$stmt = $conn->prepare($sqlCountUnread);
$stmt->bind_param($typesIn, ...$uids);
$stmt->execute();
$noLeidas = (int)($stmt->get_result()->fetch_assoc()['cant'] ?? 0);
$stmt->close();

/* Total para paginación (respeta filtros) */
$sqlTotal = "SELECT COUNT(*) AS total FROM notificaciones $where";
$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();
$hasMore = ($offset + $limit) < $total;

/* Tipos disponibles (solo las del admin) */
$sqlTipos = "
    SELECT DISTINCT tipo 
    FROM notificaciones 
    WHERE usuario_id IN ($placeholders)
    ORDER BY tipo ASC
";
$stmt = $conn->prepare($sqlTipos);
$stmt->bind_param($typesIn, ...$uids);
$stmt->execute();
$rsTipos = $stmt->get_result();
$tiposDisponibles = [];
while ($row = $rsTipos->fetch_assoc()) {
    $tiposDisponibles[] = $row['tipo'];
}
$stmt->close();

/* Listado (paginado) */
$sqlList = "
    SELECT notificacion_id, usuario_id, tipo, origen, titulo, mensaje, creada_en, leida
    FROM notificaciones
    $where
    ORDER BY creada_en DESC
    LIMIT ? OFFSET ?
";
$paramsList = array_merge($params, [$limit, $offset]);
$typesList  = $types . 'ii';

$stmt = $conn->prepare($sqlList);
$stmt->bind_param($typesList, ...$paramsList);
$stmt->execute();
$notis = $stmt->get_result();
$stmt->close();

/* Helpers */
function tipoToLabel(string $tipo): string {
    static $map = [
        'perfil_actualizado'    => 'Actualización del perfil',
        'reserva_nueva'         => 'Nueva reserva',
        'reserva_cancelada'     => 'Reserva cancelada',
        'torneo_nuevo'          => 'Nuevo torneo',
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
?>
<main>
  <div class="section">
    <div class="section-header" style="align-items:center; gap:12px; flex-wrap:wrap;">
      <h2 style="margin-right:auto; display:flex; align-items:center; gap:10px;">
        Notificaciones
        <span id="badge-no-leidas" class="badge-count <?= $noLeidas>0?'':'is-zero' ?>">
          <span class="dot"></span>
          <strong><?= $noLeidas ?></strong> notificaciones sin leer
        </span>
      </h2>
    </div>

    <!-- Filtros (auto) -->
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
        <select name="tipo" class="f-select">
          <option value="" <?= $tipo===''?'selected':'' ?>>Todos</option>
          <?php foreach ($tiposDisponibles as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $tipo===$t?'selected':'' ?>>
              <?= htmlspecialchars(tipoToLabel($t)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <input type="hidden" name="page"  value="<?= (int)$page ?>">
      <input type="hidden" name="limit" value="<?= (int)$limit ?>">
    </form>

    <style>
      .filterbar{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin:14px 0 16px}
      .f-field{display:flex;flex-direction:column;gap:6px;min-width:220px}
      .f-label{font-size:12px;color:#586168;font-weight:600;letter-spacing:.3px}
      .f-select{
        width:100%; padding:10px 12px; border:1px solid #d6dadd; border-radius:10px; background:#fff;
        outline:none; transition:border-color .2s, box-shadow .2s; box-shadow:0 1px 0 rgba(0,0,0,.03)
      }
      .f-select:focus{
        border-color:#1bab9d; box-shadow:0 0 0 3px rgba(27,171,157,.12)
      }

      .badge-count{
        display:inline-flex;align-items:center;gap:8px;padding:4px 12px;border-radius:999px;
        background:#e6f7f4;color:#0b6158;border:1px solid #b7e6de;font-size:13px
      }
      .badge-count .dot{
        width:8px;height:8px;border-radius:50%;background:#1bab9d;
        box-shadow:0 0 0 3px rgba(27,171,157,.15)
      }
      .badge-count.is-zero{
        background:#f3f4f6;color:#6b7280;border-color:#e5e7eb
      }
      .badge-count.is-zero .dot{
        background:#cbd5e1; box-shadow:none
      }

      .col-tipo{text-align:center}
      .notif-tipo-pill{
        display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;
        text-transform:uppercase;letter-spacing:.4px
      }
      .notif-tipo-reserva{background:#e0f7e9;color:#19733b}
      .notif-tipo-torneo {background:#e3f2fd;color:#1a5fb4}
      .notif-tipo-reporte{background:#fff3e0;color:#e65100}
      .notif-tipo-pago   {background:#ede7f6;color:#5e35b1}
      .notif-tipo-cliente{background:#f1f8e9;color:#2e7d32}
      .notif-tipo-sistema{background:#eef2f7;color:#415a77}

      .chip{
        padding:2px 10px;border-radius:999px;font-size:12px;border:1px solid transparent;
        min-width:100px;text-align:center
      }
      .chip-ok{background:#e8f5e9;color:#2e7d32;border-color:#c8e6c9}
      .chip-warn{background:#ffebee;color:#c62828;border-color:#ffcdd2}
      .row-new{background:#f7fbfd}

      .toggle-read{border:none;cursor:pointer;font-size:18px;background:transparent}
      .toggle-read[disabled]{opacity:.5;cursor:default}
      .icon-read{font-size:18px;line-height:1}
      table td{vertical-align:top}

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
            <th>Fecha</th>
            <th>Hora</th>
            <th>Título</th>
            <th>Mensaje</th>
            <th>Tipo</th>
            <?php if (SHOW_ORIGEN): ?><th>Origen</th><?php endif; ?>
            <?php if (SHOW_DEST):   ?><th>Destinatario</th><?php endif; ?>
            <th>Indicación</th>
            <th>Visto</th>
          </tr>
        </thead>
        <tbody id="noti-rows">
        <?php if ($notis->num_rows): while($n=$notis->fetch_assoc()):
          $isLeida   = (int)$n['leida'] === 1;
          $rowClass  = $isLeida ? '' : 'row-new';
          $pillClass = tipoToClass($n['tipo']);
          $estadoTxt = $isLeida ? 'Leído' : 'No leído';
          $estadoCls = $isLeida ? 'chip-ok' : 'chip-warn';
          $dest      = 'Admin';

          $labelTipo = tipoToLabel((string)$n['tipo']);

          $ts = strtotime($n['creada_en'] ?? 'now');
          $fechaFmt = date('d/m', $ts);
          $horaFmt  = date('H:i', $ts);
        ?>
          <tr class="<?= $rowClass ?>" data-nid="<?= (int)$n['notificacion_id'] ?>">
            <td><?= htmlspecialchars($fechaFmt) ?></td>
            <td><?= htmlspecialchars($horaFmt) ?></td>
            <td><?= htmlspecialchars($n['titulo']) ?></td>
            <td><?= nl2br(htmlspecialchars($n['mensaje'])) ?></td>
            <td class="col-tipo">
              <span class="<?= $pillClass ?>"><?= htmlspecialchars($labelTipo) ?></span>
            </td>
            <?php if (SHOW_ORIGEN): ?>
              <td><?= htmlspecialchars(ucfirst($n['origen'])) ?></td>
            <?php endif; ?>
            <?php if (SHOW_DEST):   ?>
              <td><?= htmlspecialchars($dest) ?></td>
            <?php endif; ?>
            <td class="state">
              <span class="chip <?= $estadoCls ?>"><?= $estadoTxt ?></span>
            </td>
            <td class="action">
              <?php if (!$isLeida): ?>
                <form class="form-mark-read" method="POST" action="notificacionesAction.php" style="display:inline-block;">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="mark_read">
                  <input type="hidden" name="notificacion_id" value="<?= (int)$n['notificacion_id'] ?>">
                  <button type="submit" class="toggle-read" title="Marcar como leída">☐</button>
                </form>
              <?php else: ?>
                <span class="icon-read" title="Leída">✅</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr>
            <td colspan="<?= 7 + (int)SHOW_ORIGEN + (int)SHOW_DEST ?>" style="text-align:center;">
              No tienes notificaciones.
            </td>
          </tr>
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
(function(){
  const form = document.getElementById('filtros');
  if (!form) return;

  const resetPage = ()=>{ const p=form.querySelector('input[name="page"]'); if(p) p.value='1'; };

  async function submitAjax(){
    const params = new URLSearchParams(new FormData(form));
    const url = `${form.action}?${params.toString()}`;
    try{
      const res = await fetch(url, { headers: { 'X-Requested-With':'fetch' } });
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');

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

function wireMarkRead(){
  const estadoSel = document.getElementById('f-estado');

  document.querySelectorAll('.form-mark-read').forEach(f => {
    if (f.dataset.wired) return;
    f.dataset.wired = '1';

    f.addEventListener('submit', async (ev)=>{
      ev.preventDefault();
      const btn = f.querySelector('button.toggle-read');
      btn.disabled = true;
      try{
        const res = await fetch(f.action, {
          method:'POST',
          body:new FormData(f),
          headers:{ 'X-Requested-With':'fetch' }
        });
        if (!res.ok && res.status !== 204) throw 0;

        const row   = f.closest('tr');
        const tbody = row.parentElement;
        const currentEstado = (estadoSel && estadoSel.value) || 'todas';

        if (currentEstado === 'no_leidas') {
          row.remove();
          if (!tbody.querySelector('tr')) {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="<?= 7 + (int)SHOW_ORIGEN + (int)SHOW_DEST ?>" style="text-align:center;">No tienes notificaciones.</td>`;
            tbody.appendChild(tr);
          }
        } else {
          const chip = row.querySelector('.state .chip');
          row.classList.remove('row-new');
          chip.classList.remove('chip-warn');
          chip.classList.add('chip-ok');
          chip.textContent = 'Leído';
          row.querySelector('td.action').innerHTML =
            '<span class="icon-read" title="Leída">✅</span>';
        }

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

function wireLoadMore(){
  const btn = document.getElementById('load-more');
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

      const res = await fetch(url, { headers:{ 'X-Requested-With':'fetch' } });
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');

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
