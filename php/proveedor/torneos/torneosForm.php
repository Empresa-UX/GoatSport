<?php
/* =========================================================================
 * Form Crear/Editar torneo (proveedor)
 * ========================================================================= */
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../../login.php'); exit;
}
$proveedor_id = (int)$_SESSION['usuario_id'];

$torneo_id = isset($_GET['torneo_id']) ? (int)$_GET['torneo_id'] : 0;

$accion = 'add';
$formTitle = 'Crear torneo';

$nombre=''; $fecha_inicio=''; $fecha_fin=''; $estado='abierto'; $tipo='equipo'; $capacidad=8; $puntos_ganador=0;

/* Flash */
$errors = $_SESSION['flash_errors'] ?? [];
$old    = $_SESSION['flash_old'] ?? [];
unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

if ($old) {
  $nombre = $old['nombre'] ?? $nombre;
  $fecha_inicio = $old['fecha_inicio'] ?? $fecha_inicio;
  $fecha_fin = $old['fecha_fin'] ?? $fecha_fin;
  $estado = $old['estado'] ?? $estado;
  $tipo = $old['tipo'] ?? $tipo;
  $capacidad = (int)($old['capacidad'] ?? $capacidad);
  $puntos_ganador = (int)($old['puntos_ganador'] ?? $puntos_ganador);
}

/* Modo edición: cargar solo si pertenece al proveedor */
if ($torneo_id) {
  $st = $conn->prepare("SELECT * FROM torneos WHERE torneo_id=? AND proveedor_id=? LIMIT 1");
  $st->bind_param("ii", $torneo_id, $proveedor_id);
  $st->execute();
  $rs = $st->get_result();
  if ($row = $rs->fetch_assoc()) {
    $accion = 'edit';
    $formTitle = 'Editar torneo';
    if (!$old) {
      $nombre = $row['nombre'] ?? '';
      $fecha_inicio = $row['fecha_inicio'] ?? '';
      $fecha_fin = $row['fecha_fin'] ?? '';
      $estado = $row['estado'] ?? 'abierto';
      $tipo = $row['tipo'] ?? 'equipo';
      $capacidad = (int)($row['capacidad'] ?? 8);
      $puntos_ganador = (int)($row['puntos_ganador'] ?? 0);
    }
  } else {
    $st->close(); header('Location: torneos.php'); exit;
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
  @media (max-width:640px){ .form-container form{grid-template-columns:1fr;} .row-3{grid-template-columns:1fr;} }
</style>

<div class="form-container">
  <h2><?= htmlspecialchars($formTitle) ?></h2>

  <?php if (!empty($errors)): ?>
    <div class="alert">
      <strong>Revisá los datos:</strong>
      <ul style="margin:6px 0 0 18px;">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form id="formTorneo" method="POST" action="torneosAction.php" novalidate>
    <input type="hidden" name="action" value="<?= htmlspecialchars($accion) ?>">
    <input type="hidden" name="torneo_id" value="<?= (int)$torneo_id ?>">

    <div class="full">
      <label>Nombre del torneo</label>
      <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
    </div>

    <div>
      <label>Fecha de inicio</label>
      <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>" required>
    </div>
    <div>
      <label>Fecha de fin</label>
      <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>" required>
    </div>

    <div>
      <label>Estado</label>
      <select name="estado" required>
        <option value="abierto"    <?= $estado==='abierto'?'selected':'' ?>>Abierto</option>
        <option value="cerrado"    <?= $estado==='cerrado'?'selected':'' ?>>Cerrado</option>
        <option value="finalizado" <?= $estado==='finalizado'?'selected':'' ?>>Finalizado</option>
      </select>
    </div>
    <div>
      <label>Tipo</label>
      <select name="tipo" required>
        <option value="equipo"     <?= $tipo==='equipo'?'selected':'' ?>>Equipo</option>
        <option value="individual" <?= $tipo==='individual'?'selected':'' ?>>Individual</option>
      </select>
    </div>

    <div class="row-3 full">
      <div>
        <label>Capacidad (número par)</label>
        <input type="number" name="capacidad" min="2" step="2" value="<?= (int)$capacidad ?>" required>
      </div>
      <div>
        <label>Puntos ganador</label>
        <input type="number" name="puntos_ganador" min="0" step="1" value="<?= (int)$puntos_ganador ?>" required>
      </div>
    </div>

    <div class="full btn-row">
      <button type="submit" class="btn-add"><?= htmlspecialchars($formTitle) ?></button>
      <a href="torneos.php" class="btn-add">Cancelar</a>
    </div>
  </form>
</div>

<script>
(function(){
  const f = document.getElementById('formTorneo');
  f.addEventListener('submit', function(e){
    const errs=[];
    const nombre = (f.nombre.value||'').trim();
    const fi = f.fecha_inicio.value||'';
    const ff = f.fecha_fin.value||'';
    const cap = parseInt(f.capacidad.value||'0',10);
    const pts = parseInt(f.puntos_ganador.value||'-1',10);
    const estado = (f.estado.value||'').trim();
    const tipo = (f.tipo.value||'').trim();

    if(!nombre) errs.push('El nombre es obligatorio.');
    if(!fi) errs.push('La fecha de inicio es obligatoria.');
    if(!ff) errs.push('La fecha de fin es obligatoria.');
    if(fi && ff && ff < fi) errs.push('La fecha de fin no puede ser anterior a la de inicio.');
    if(!['abierto','cerrado','finalizado'].includes(estado)) errs.push('Estado inválido.');
    if(!['equipo','individual'].includes(tipo)) errs.push('Tipo inválido.');
    if(isNaN(cap) || cap < 2 || cap % 2 !== 0) errs.push('Capacidad debe ser un número par y mayor o igual a 2.');
    if(isNaN(pts) || pts < 0) errs.push('Puntos ganador inválido.');

    if(errs.length){ e.preventDefault(); alert(errs.join('\n')); }
  });
})();
</script>

<?php include '../includes/footer.php'; ?>
