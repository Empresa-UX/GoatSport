<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include './../includes/cards.php';
include '../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../../login.php'); exit;
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

$proveedor_id = (int)$_SESSION['usuario_id'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$passAlert = $_SESSION['flash_pass_alert'] ?? null;
unset($_SESSION['flash_pass_alert']);

/* Data */
$st = $conn->prepare("
  SELECT
    u.user_id, u.nombre, u.email, u.fecha_registro, rd.fecha_asignacion
  FROM recepcionista_detalle rd
  INNER JOIN usuarios u ON u.user_id = rd.recepcionista_id
  WHERE rd.proveedor_id = ?
    AND u.rol = 'recepcionista'
  ORDER BY u.nombre ASC, u.user_id DESC
");
$st->bind_param("i", $proveedor_id);
$st->execute();
$res = $st->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$st->close();
?>
<div class="section">
  <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <h2 style="margin:0;">Mis recepcionistas</h2>
    <a href="recepcionistasForm.php" class="btn-add"><span>Agregar recepcionista</span></a>
  </div>

  <style>
    .btn-add{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;text-decoration:none;font-weight:500;font-size:14px;white-space:nowrap;background:#1bab9d;color:#fff;border-radius:8px}
    .btn-add:hover{background:#139488;}
    .alert-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:10px 12px;margin:12px 0;}
    .fbar{display:grid;grid-template-columns:minmax(280px,1fr) minmax(200px,240px);
      gap:12px;align-items:end;background:#fff;padding:14px 16px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);margin-bottom:12px;}
    @media (max-width:760px){.fbar{grid-template-columns:1fr;}}
    .f{display:flex;flex-direction:column;gap:6px;}
    .f label{font-size:12px;color:#586168;font-weight:700;}
    .f input[type="text"]{padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;}

    table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border-radius:12px;overflow:hidden;table-layout:fixed;}
    thead th{position:sticky;top:0;background:#f8fafc;z-index:1;text-align:left;font-weight:700;padding:10px 12px;font-size:13px;color:#334155;border-bottom:1px solid #e5e7eb;}
    tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top;}
    tbody tr:hover{background:#f7fbfd;}

    .col-nom{width:220px;}
    .col-email{width:260px;}
    .col-asig{width:140px;}
    .col-reg{width:140px;}
    .col-acc{width:150px;text-align:center;}
    .truncate{display:block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .sub{color:#64748b;font-size:12px;font-weight:700;margin-top:4px;}

    .btn-action{appearance:none;border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-weight:700;}
    .btn-action.delete{background:#fde8e8;border:1px solid #f8c9c9;color:#7f1d1d;}
    .actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center;justify-content:center;}
  </style>

  <?php if ($flash): ?>
    <div class="alert-ok"><?= h($flash) ?></div>
  <?php endif; ?>

  <!-- Filtros -->
  <div class="fbar" id="filters">
    <div class="f">
      <label>Buscar (nombre o email)</label>
      <input type="text" id="f-q" placeholder="Ej: Juan / @gmail.com">
    </div>
    <div class="f"><label>—</label><div class="sub">Solo ves recepcionistas asignados a tu club.</div></div>
  </div>

  <table id="tablaRecep">
    <thead>
      <tr>
        <th class="col-nom">Nombre</th>
        <th class="col-email">Email</th>
        <th class="col-asig">Asignado</th>
        <th class="col-reg">Registro</th>
        <th class="col-acc">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5" style="text-align:center;">No tenés recepcionistas cargados.</td></tr>
      <?php else: foreach($rows as $r):
        $txt = mb_strtolower(($r['nombre'] ?? '').' '.($r['email'] ?? ''), 'UTF-8');
        $asig = $r['fecha_asignacion'] ? date('d/m/Y', strtotime($r['fecha_asignacion'])) : '—';
        $reg  = $r['fecha_registro'] ? date('d/m/Y', strtotime($r['fecha_registro'])) : '—';
      ?>
        <tr data-text="<?= h($txt) ?>">
          <td class="col-nom">
            <div class="truncate"><strong><?= h($r['nombre']) ?></strong></div>
            <div class="sub">ID: <?= (int)$r['user_id'] ?></div>
          </td>
          <td class="col-email"><span class="truncate"><?= h($r['email']) ?></span></td>
          <td class="col-asig"><?= h($asig) ?></td>
          <td class="col-reg"><?= h($reg) ?></td>
          <td class="col-acc">
            <div class="actions">
              <form method="POST" action="recepcionistasAction.php"
                onsubmit="return confirm('¿Eliminar recepcionista «<?= h($r['nombre']) ?>»?');"
                style="display:inline-block">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="recepcionista_id" value="<?= (int)$r['user_id'] ?>">
                <button type="submit" class="btn-action delete">Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
(function(){
  const $ = (s,r=document)=>r.querySelector(s);
  const $$ = (s,r=document)=>Array.from(r.querySelectorAll(s));
  const q = $('#f-q');
  const rows = $$('#tablaRecep tbody tr');
  const norm = s => (s||'').toString().toLowerCase();

  const apply = ()=>{
    const text = norm(q.value);
    rows.forEach(tr=>{
      const v = (tr.dataset.text||'');
      tr.style.display = (text==='' || v.includes(text)) ? '' : 'none';
    });
  };

  const debounce=(fn,ms=140)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};};
  q.addEventListener('input', debounce(apply, 160));
  apply();
})();
</script>

<?php if ($passAlert && !empty($passAlert['password'])): ?>
<script>
  const email = <?= json_encode($passAlert['email'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
  const password = <?= json_encode($passAlert['password'] ?? '', JSON_UNESCAPED_UNICODE) ?>;

  alert(
    "Recepcionista creado.\n\n" +
    "Email: " + email + "\n" +
    "Contraseña autogenerada: " + password + "\n\n" +
    "Guardala ahora: no se volverá a mostrar."
  );
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
