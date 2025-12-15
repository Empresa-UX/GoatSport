<?php
/* =========================================================================
 * file: php/proveedor/promociones/promocionesForm.php
 * Crear promoción (proveedor) — UI y validaciones pedidas
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') {
  header('Location: ../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Canchas activas del proveedor */
$stmt = $conn->prepare("SELECT cancha_id, nombre FROM canchas WHERE proveedor_id=? AND activa=1 ORDER BY nombre");
$stmt->bind_param("i",$proveedor_id);
$stmt->execute();
$canchas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Flash */
$errors = $_SESSION['flash_errors'] ?? [];
$old    = $_SESSION['flash_old'] ?? [];
unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

$today = date('Y-m-d');

$nombre = $old['nombre'] ?? '';
$descripcion = $old['descripcion'] ?? '';
$cancha_id = (int)($old['cancha_id'] ?? 0); // 0 = todas
$pct = $old['porcentaje_descuento'] ?? '';
$fi  = $old['fecha_inicio'] ?? '';
$ff  = $old['fecha_fin'] ?? '';
$hi  = $old['hora_inicio'] ?? '';
$hf  = $old['hora_fin'] ?? '';
$dias = $old['dias_semana'] ?? []; // array de strings '1'..'7'
$minRes = (int)($old['minima_reservas'] ?? 0);
?>
<style>
  .form-container{background:#fff;border-radius:12px;padding:16px;box-shadow:0 4px 12px rgba(0,0,0,.08);max-width:860px;}
  .form-container h2{margin-top:0;}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  @media (max-width:720px){ .grid{grid-template-columns:1fr;} }
  label{font-size:12px;color:#586168;font-weight:700;}
  input,select,textarea{padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;}
  textarea{resize:none;}
  .full{grid-column:1/-1;}
  .row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
  .chips{display:flex;flex-wrap:wrap;gap:8px;}
  .chip{display:inline-flex;align-items:center;gap:6px;border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:6px 10px;}
  .btn-row{display:flex;gap:10px;align-items:center;margin-top:8px;}
  .btn-add{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;text-decoration:none;font-weight:700;font-size:14px;border-radius:10px;border:1px solid #bfd7ff;background:#e0ecff;color:#1e40af;cursor:pointer;}
  .alert{background:#fff7ed;border:1px solid #fed7aa;color:#7c2d12;border-radius:10px;padding:10px 12px;margin-bottom:10px;}
</style>

<div class="form-container">
  <h2>Crear promoción</h2>

  <?php if ($errors): ?>
    <div class="alert">
      <strong>Revisá los datos:</strong>
      <ul style="margin:6px 0 0 18px;"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="promocionesAction.php" id="frmPromo" class="grid">
    <input type="hidden" name="action" value="add">

    <!-- 1F: Título -->
    <div class="full">
      <label>Nombre</label>
      <input type="text" name="nombre" maxlength="100" value="<?= h($nombre) ?>" required>
    </div>

    <!-- 2F: Descripción -->
    <div class="full">
      <label>Descripción</label>
      <textarea name="descripcion" rows="3" maxlength="2000"><?= h($descripcion) ?></textarea>
    </div>

    <!-- 3F: Cancha / % Desc / Mín. reservas (misma fila) -->
    <div class="row-3 full">
      <div>
        <label>Cancha</label>
        <select name="cancha_id">
          <option value="0" <?= $cancha_id===0 ? 'selected':'' ?>>Todas las canchas</option>
          <?php foreach($canchas as $c): ?>
            <option value="<?= (int)$c['cancha_id'] ?>" <?= (int)$c['cancha_id']===$cancha_id?'selected':'' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>% Descuento (1–99)</label>
        <input type="number" step="0.01" min="1" max="99.99" name="porcentaje_descuento" value="<?= h($pct) ?>" required>
      </div>
      <div>
        <label>Mínima de reservas</label>
        <input type="number" min="0" step="1" name="minima_reservas" value="<?= (int)$minRes ?>">
      </div>
    </div>

    <!-- 4F: Fecha/Hora inicio — Fecha/Hora fin (dos columnas) -->
    <div>
      <label>Fecha inicio</label>
      <input type="date" name="fecha_inicio" min="<?= h($today) ?>" value="<?= h($fi) ?>" required>
      <div style="height:6px"></div>
      <label>Hora inicio (opcional)</label>
      <input type="time" name="hora_inicio" value="<?= h($hi) ?>">
    </div>
    <div>
      <label>Fecha fin</label>
      <input type="date" name="fecha_fin" min="<?= h($today) ?>" value="<?= h($ff) ?>" required>
      <div style="height:6px"></div>
      <label>Hora fin (opcional)</label>
      <input type="time" name="hora_fin" value="<?= h($hf) ?>">
    </div>

    <!-- 5F: Días de la semana (obligatorio ≥ 1) -->
    <div class="full">
      <label>Días de la semana (al menos uno)</label>
      <div class="chips" style="margin-top:6px">
        <?php
          $opts = ['1'=>'Lu','2'=>'Ma','3'=>'Mi','4'=>'Ju','5'=>'Vi','6'=>'Sá','7'=>'Do'];
          $sel = is_array($dias) ? $dias : array_filter(explode(',', (string)$dias));
        ?>
        <?php foreach($opts as $k=>$v): ?>
          <label class="chip"><input type="checkbox" name="dias_semana[]" value="<?= $k ?>" <?= in_array((string)$k,$sel,true)?'checked':'' ?>> <?= $v ?></label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="full btn-row">
      <button type="submit" class="btn-add">Crear promoción</button>
      <a href="promociones.php" class="btn-add">Cancelar</a>
    </div>
  </form>
</div>

<script>
(function(){
  const f  = document.getElementById('frmPromo');
  const fi = f.querySelector('input[name="fecha_inicio"]');
  const ff = f.querySelector('input[name="fecha_fin"]');
  const hi = f.querySelector('input[name="hora_inicio"]');
  const hf = f.querySelector('input[name="hora_fin"]');

  function syncMinFin(){
    if (fi.value) { ff.min = fi.value; if (ff.value && ff.value < fi.value) ff.value = fi.value; }
  }
  fi.addEventListener('change', syncMinFin);
  window.addEventListener('DOMContentLoaded', syncMinFin);

  f.addEventListener('submit', function(e){
    const errs=[];
    const pct = parseFloat(f.porcentaje_descuento.value||'NaN');
    if (isNaN(pct) || !(pct > 0 && pct < 100)) errs.push('El % de descuento debe ser mayor a 0 y menor a 100.');

    if (!fi.value) errs.push('Fecha inicio requerida.');
    if (!ff.value) errs.push('Fecha fin requerida.');
    if (fi.value && ff.value && ff.value < fi.value) errs.push('La fecha fin no puede ser anterior a la de inicio.');

    // si hay horas y las fechas son iguales, hora fin > hora inicio
    if (fi.value && ff.value && fi.value === ff.value && hi.value && hf.value && hf.value <= hi.value) {
      errs.push('En el mismo día, la hora fin debe ser posterior a la hora inicio.');
    }

    // días seleccionados: al menos 1
    const days = Array.from(f.querySelectorAll('input[name="dias_semana[]"]:checked'));
    if (days.length === 0) errs.push('Seleccioná al menos un día de la semana.');

    if (errs.length){ e.preventDefault(); alert(errs.join('\n')); }
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
