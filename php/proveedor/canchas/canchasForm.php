<?php
/* =========================================================================
 * file: php/proveedor/canchas/canchasForm.php
 * Crear/Editar cancha. En editar agrega selector Estado (Activo/Desactivar).
 * Corrige precarga de "Tipo" aun si en DB está sin acento (p.ej. 'clasica').
 * ========================================================================= */
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../login.php'); exit;
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

$proveedor_id = (int)$_SESSION['usuario_id'];
$cancha_id    = isset($_GET['cancha_id']) ? (int)$_GET['cancha_id'] : 0;

$accion    = 'add';
$formTitle = 'Crear cancha';

/* defaults */
$nombre = '';
$tipo = '';
$capacidad_txt = '';
$precio = '0.00';
$hora_apertura = '08:00';
$hora_cierre   = '23:00';
$descripcion   = '';
$activa        = 1; // sólo para editar

/* Flash */
$errors = $_SESSION['flash_errors'] ?? [];
$old    = $_SESSION['flash_old'] ?? [];
unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

if (!empty($old)) {
  $nombre        = $old['nombre'] ?? $nombre;
  $tipo          = $old['tipo'] ?? $tipo;
  $capacidad_txt = $old['capacidad_txt'] ?? $capacidad_txt;
  $precio        = $old['precio'] ?? $precio;
  $hora_apertura = $old['hora_apertura'] ?? $hora_apertura;
  $hora_cierre   = $old['hora_cierre'] ?? $hora_cierre;
  $descripcion   = $old['descripcion'] ?? $descripcion;
  if (isset($old['activa'])) $activa = (int)$old['activa'];
}

/* Normalizador de acentos para comparar tipos */
function norm($s){
  $s = mb_strtolower((string)$s,'UTF-8');
  $from = ['á','é','í','ó','ú','ä','ë','ï','ö','ü','ñ'];
  $to   = ['a','e','i','o','u','a','e','i','o','u','n'];
  return str_replace($from,$to,$s);
}

/* Modo edición (solo si la cancha está aprobada; si no, no debería ser editable) */
if ($cancha_id) {
  $st = $conn->prepare("SELECT * FROM canchas WHERE cancha_id=? AND proveedor_id=? LIMIT 1");
  $st->bind_param('ii', $cancha_id, $proveedor_id);
  $st->execute(); $rs = $st->get_result();
  if ($row = $rs->fetch_assoc()) {
    if (($row['estado'] ?? '') !== 'aprobado') { $st->close(); header('Location: canchas.php'); exit; }
    $accion          = 'edit';
    $formTitle       = 'Editar cancha';
    if (!$old) {
      $nombre          = $row['nombre'] ?? '';
      /* corrige: seleccionar aunque DB tenga 'clasica' */
      $dbTipo          = $row['tipo'] ?? '';
      $tipo            = $dbTipo;
      $capacidad_txt   = (($row['capacidad'] ?? null) === 2) ? 'Individual' : ((($row['capacidad'] ?? null) === 4) ? 'Equipo' : '');
      $precio          = $row['precio'] ?? '0.00';
      $hora_apertura   = $row['hora_apertura'] ? substr($row['hora_apertura'],0,5) : '08:00';
      $hora_cierre     = $row['hora_cierre'] ? substr($row['hora_cierre'],0,5) : '23:00';
      $descripcion     = $row['descripcion'] ?? '';
      $activa          = (int)($row['activa'] ?? 1);
    }
  } else {
    $st->close(); header('Location: canchas.php'); exit;
  }
  $st->close();
}
?>
<style>
  .form-container{background:#fff;border-radius:12px;padding:16px;box-shadow:0 4px 12px rgba(0,0,0,.08);max-width:760px;}
  .form-container h2{margin-top:0;}
  .form-container form{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  .form-container label{font-size:12px;color:#586168;font-weight:700;}
  .form-container input,.form-container select,.form-container textarea{padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;}
  .full{grid-column:1 / -1;}
  .row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
  .btn-row{display:flex;gap:10px;align-items:center;margin-top:6px;}
  .alert{background:#fff7ed;border:1px solid #fed7aa;color:#7c2d12;border-radius:10px;padding:10px 12px;margin-bottom:10px;}
  .btn-add{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;text-decoration:none;font-weight:700;font-size:14px;border-radius:10px;border:1px solid #bfd7ff;background:#e0ecff;color:#1e40af;cursor:pointer;}
  .btn-add:hover{filter:brightness(0.98);}
  input[type="number"]{margin:0 !important;}
  textarea{resize:none;}
  @media (max-width:640px){
    .form-container form{grid-template-columns:1fr;}
    .row-3{grid-template-columns:1fr;}
  }
</style>

<div class="form-container">
  <h2><?= htmlspecialchars($formTitle) ?></h2>

  <?php if (!empty($errors)): ?>
    <div class="alert">
      <strong>Revisá los datos:</strong>
      <ul style="margin:6px 0 0 18px;">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form id="formCancha" method="POST" action="canchasAction.php" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
    <input type="hidden" name="action" value="<?= htmlspecialchars($accion) ?>">
    <input type="hidden" name="cancha_id" value="<?= (int)$cancha_id ?>">

    <div class="full">
      <label>Nombre de la cancha</label>
      <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
    </div>

    <!-- Tipo / Capacidad / Estado (solo en editar) -->
    <div class="row-3 full">
      <div>
        <label>Tipo de cancha</label>
        <select name="tipo" required>
          <option value="">— Seleccionar —</option>
          <?php
            $opts = ['clásica','panorámica','cubierta'];
            foreach ($opts as $opt) {
              $sel = (norm($tipo) === norm($opt)) ? 'selected' : '';
              echo '<option value="'.htmlspecialchars($opt).'" '.$sel.'>'.htmlspecialchars(ucfirst($opt)).'</option>';
            }
          ?>
        </select>
      </div>

      <div>
        <label>Capacidad</label>
        <select name="capacidad_txt" required>
          <option value="">— Seleccionar —</option>
          <option value="Individual" <?= ($capacidad_txt==='Individual')?'selected':''; ?>>Individual</option>
          <option value="Equipo" <?= ($capacidad_txt==='Equipo')?'selected':''; ?>>Equipo</option>
        </select>
      </div>

      <?php if ($accion === 'edit'): ?>
      <div>
        <label>Estado</label>
        <select name="activa">
          <option value="1" <?= $activa ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= !$activa ? 'selected' : '' ?>>Desactivar</option>
        </select>
      </div>
      <?php endif; ?>
    </div>

    <div class="full row-3">
      <div>
        <label>Precio por hora</label>
        <input type="number" step="0.01" min="0" name="precio" value="<?= htmlspecialchars($precio) ?>" required>
      </div>
      <div>
        <label>Hora de apertura</label>
        <input type="time" name="hora_apertura" value="<?= htmlspecialchars($hora_apertura) ?>" required>
      </div>
      <div>
        <label>Hora de cierre</label>
        <input type="time" name="hora_cierre" value="<?= htmlspecialchars($hora_cierre) ?>" required>
      </div>
    </div>

    <div class="full">
      <label><?= $accion==='edit' ? 'Descripción' : 'Descripción (opcional)' ?></label>
      <textarea name="descripcion" rows="5" placeholder="Detalles, superficie, notas..."><?= htmlspecialchars($descripcion) ?></textarea>
    </div>

    <div class="full btn-row">
        <?php $submitText = ($accion === 'edit') ? 'Guardar cambios' : $formTitle; ?>
        <button id="btnSubmit" type="submit" class="btn-add" <?= ($accion === 'edit') ? 'disabled' : '' ?>>
        <?= htmlspecialchars($submitText) ?>
        </button>      <a href="canchas.php" class="btn-add">Cancelar</a>
    </div>
  </form>
</div>

<script>
/* Validación cliente */
(function(){
  const f=document.getElementById('formCancha');
  f.addEventListener('submit',function(e){
    const precio=parseFloat(f.precio.value||'0');
    const A=(f.hora_apertura.value||'').trim();
    const C=(f.hora_cierre.value||'').trim();
    const Cap=(f.capacidad_txt.value||'').trim();
    const Tipo=(f.tipo.value||'').trim();
    const errs=[];
    if(!f.nombre.value.trim()) errs.push('El nombre es obligatorio.');
    if(!Tipo) errs.push('El tipo es obligatorio.');
    if(!Cap) errs.push('La capacidad es obligatoria.');
    if(isNaN(precio)||precio<=0) errs.push('El precio por hora debe ser mayor a 0.');
    if(!A||!C) errs.push('Debés indicar hora de apertura y de cierre.');
    if(A&&C&&A>=C) errs.push('La hora de apertura no puede ser mayor o igual a la de cierre.');
    if(errs.length){ e.preventDefault(); alert(errs.join('\n')); }
  });
})();
</script>
<script>
(function(){
  const f = document.getElementById('formCancha');
  const btn = document.getElementById('btnSubmit');

  // Solo aplica en modo edición
  const isEdit = (f.action.value === 'edit');
  if (!isEdit) return;

  const snapshot = () => {
    const fd = new FormData(f);
    // No cuentan estos campos como "cambio"
    fd.delete('csrf');
    // action/cancha_id deberían ser iguales siempre, pero los dejamos por seguridad
    // (si querés, también podés borrarlos)
    // fd.delete('action');
    // fd.delete('cancha_id');

    // Normalizamos para comparar estable
    return Array.from(fd.entries())
      .map(([k,v]) => `${k}=${String(v).trim()}`)
      .sort()
      .join('&');
  };

  const initial = snapshot();

  const refresh = () => {
    const changed = snapshot() !== initial;
    btn.disabled = !changed;
  };

  // Escucha cambios (inputs + selects + textarea)
  f.addEventListener('input', refresh, true);
  f.addEventListener('change', refresh, true);

  // Arranca deshabilitado (porque no hay cambios reales)
  refresh();
})();
</script>

<?php include '../includes/footer.php'; ?>
