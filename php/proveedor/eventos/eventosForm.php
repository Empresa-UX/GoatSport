<?php
/* =========================================================================
 * file: php/proveedor/eventos/eventosForm.php
 * Crear evento especial (proveedor) — UI ajustada
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') {
  header('Location: ../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

/* Canchas propias */
$stmt = $conn->prepare("SELECT cancha_id, nombre FROM canchas WHERE proveedor_id=? AND activa=1 ORDER BY nombre");
$stmt->bind_param("i",$proveedor_id);
$stmt->execute();
$canchas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$errors = $_SESSION['flash_errors'] ?? [];
$old    = $_SESSION['flash_old'] ?? [];
unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

$titulo = $old['titulo'] ?? '';
$descripcion = $old['descripcion'] ?? '';
$tipo = $old['tipo'] ?? 'bloqueo';
$cancha_id = (int)($old['cancha_id'] ?? 0);
$fi = $old['fecha_inicio'] ?? '';
$ff = $old['fecha_fin'] ?? '';

$todayMin = date('Y-m-d') . 'T00:00'; // bloquear pasado
?>
<style>
  .form-container{background:#fff;border-radius:12px;padding:16px;box-shadow:0 4px 12px rgba(0,0,0,.08);max-width:760px;}
  .form-container h2{margin-top:0;}
  .form-container form{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  .form-container label{font-size:12px;color:#586168;font-weight:700;}
  .form-container input,.form-container select,.form-container textarea{
    padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none; width: 100%;
  }
  textarea{resize:none;} /* quitar el “resizer” */
  .full{grid-column:1 / -1;}
  .btn-row{display:flex;gap:10px;align-items:center;margin-top:6px;}
  .alert{background:#fff7ed;border:1px solid #fed7aa;color:#7c2d12;border-radius:10px;padding:10px 12px;margin-bottom:10px;}
  .btn-add{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;text-decoration:none;font-weight:700;font-size:14px;border-radius:10px;border:1px solid #bfd7ff;background:#e0ecff;color:#1e40af;cursor:pointer;}
  .btn-add:hover{filter:brightness(0.98);}
  @media (max-width:640px){ .form-container form{grid-template-columns:1fr;} }
</style>

<div class="form-container">
  <h2>Crear evento especial</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert">
      <strong>Revisá los datos:</strong>
      <ul style="margin:6px 0 0 18px;"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="eventosAction.php" id="frmEvento">
    <input type="hidden" name="action" value="add">

    <div class="full">
      <label>Título</label>
      <input type="text" name="titulo" maxlength="100" value="<?= h($titulo) ?>" required>
    </div>

    <div class="full">
      <label>Descripción</label>
      <textarea name="descripcion" rows="3" maxlength="2000"><?= h($descripcion) ?></textarea>
    </div>

    <!-- MISMA FILA: Tipo + Cancha -->
    <div>
      <label>Tipo de bloqueo</label>
      <select name="tipo" required>
        <option value="bloqueo" <?= $tipo==='bloqueo'?'selected':'' ?>>Bloqueo</option>
        <option value="torneo"  <?= $tipo==='torneo' ?'selected':'' ?>>Torneo</option>
        <option value="otro"    <?= $tipo==='otro'   ?'selected':'' ?>>Otro</option>
      </select>
    </div>
    <div>
      <label>Cancha</label>
      <select name="cancha_id" required>
        <option value="">— Elegí cancha —</option>
        <?php foreach($canchas as $c): ?>
          <option value="<?= (int)$c['cancha_id'] ?>" <?= (int)$c['cancha_id']===$cancha_id?'selected':'' ?>>
            <?= h($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Fechas -->
    <div>
      <label>Inicio</label>
      <input type="datetime-local" name="fecha_inicio" min="<?= h($todayMin) ?>" value="<?= h($fi) ?>" required>
    </div>

    <div>
      <label>Fin</label>
      <input type="datetime-local" name="fecha_fin" min="<?= h($todayMin) ?>" value="<?= h($ff) ?>" required>
    </div>

    <div class="full btn-row">
      <button type="submit" class="btn-add">Crear evento especial</button>
      <a href="eventos.php" class="btn-add">Cancelar</a>
    </div>
  </form>
</div>

<script>
(function(){
  const f = document.getElementById('frmEvento');
  const ini = f.querySelector('input[name="fecha_inicio"]');
  const fin = f.querySelector('input[name="fecha_fin"]');

  function syncMinFin(){
    if (ini.value) {
      // Fin no puede ser anterior a Inicio
      fin.min = ini.value;
      if (fin.value && fin.value < ini.value) fin.value = ini.value;
    }
  }
  ini.addEventListener('change', syncMinFin);
  window.addEventListener('DOMContentLoaded', syncMinFin);
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
