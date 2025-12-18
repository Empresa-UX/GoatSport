<?php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../../login.php'); exit;
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$errors = $_SESSION['flash_errors'] ?? [];
$old    = $_SESSION['flash_old'] ?? [];
unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

$nombre = $old['nombre'] ?? '';
$email  = $old['email'] ?? '';
?>
<style>
  .form-container{background:#fff;border-radius:12px;padding:16px;box-shadow:0 4px 12px rgba(0,0,0,.08);max-width:760px;}
  .form-container h2{margin-top:0;}
  .form-container form{display:grid;grid-template-columns:1fr;gap:12px;}
  .form-container label{font-size:12px;color:#586168;font-weight:700;}
  .form-container input{padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;}
  .btn-row{display:flex;gap:10px;align-items:center;margin-top:6px;}
  .alert{background:#fff7ed;border:1px solid #fed7aa;color:#7c2d12;border-radius:10px;padding:10px 12px;margin-bottom:10px;}
  .btn-add{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;text-decoration:none;font-weight:700;font-size:14px;border-radius:10px;border:1px solid #bfd7ff;background:#e0ecff;color:#1e40af;cursor:pointer;}
  .btn-add:hover{filter:brightness(0.98);}
</style>

<div class="form-container">
  <h2>Agregar recepcionista</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert">
      <strong>Revisá los datos:</strong>
      <ul style="margin:6px 0 0 18px;">
        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form id="formRecep" method="POST" action="recepcionistasAction.php" novalidate>
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <input type="hidden" name="action" value="add">

    <div>
      <label>Nombre</label>
      <input type="text" name="nombre" value="<?= h($nombre) ?>" required maxlength="100">
    </div>

    <div>
      <label>Email (único)</label>
      <input type="email" name="email" value="<?= h($email) ?>" required maxlength="150">
    </div>

    <div class="btn-row">
      <button type="submit" class="btn-add">Crear recepcionista</button>
      <a href="recepcionistas.php" class="btn-add">Cancelar</a>
    </div>
  </form>
</div>

<script>
(function(){
  const f=document.getElementById('formRecep');
  f.addEventListener('submit', function(e){
    const errs=[];
    const nombre=(f.nombre.value||'').trim();
    const email=(f.email.value||'').trim();

    if(!nombre) errs.push('Nombre requerido.');
    if(nombre && nombre.length > 100) errs.push('Nombre: máximo 100 caracteres.');
    if(!email) errs.push('Email requerido.');
    if(email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errs.push('Email inválido.');
    if(email && email.length > 150) errs.push('Email: máximo 150 caracteres.');

    if(errs.length){
      e.preventDefault();
      alert(errs.join('\n'));
    }
  });
})();
</script>

<?php include '../includes/footer.php'; ?>
