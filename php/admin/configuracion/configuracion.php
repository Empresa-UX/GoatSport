<?php
// php/admin/configuracion/configuracion.php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header("Location: ../login.php"); exit();
}
$admin_id = (int)$_SESSION['usuario_id'];

/* Admin */
$stmt = $conn->prepare("SELECT nombre, email, fecha_registro FROM usuarios WHERE user_id=? AND rol='admin' LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute(); $admin = $stmt->get_result()->fetch_assoc(); $stmt->close();

$nombre = $admin['nombre'] ?? '';
$email  = $admin['email']  ?? '';
$fecha_registro = $admin['fecha_registro'] ?? date('Y-m-d H:i:s');

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<div class="section">
  <div class="section-header">
    <h2>Mi perfil (Administrador)</h2>
    <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn-add" style="text-decoration: none; font-size: 16px;" href="configuracionPassword.php">Cambiar contraseña</a>
    </div>
  </div>

  <?php if ($ok): ?>
    <div style="padding:10px;margin-bottom:15px;border-radius:8px;background:#e1f7e1;color:#2e7d32;">
      <?= h($ok) ?>
    </div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div style="padding:10px;margin-bottom:15px;border-radius:8px;background:#fdecea;color:#c62828;">
      <?= h($err) ?>
    </div>
  <?php endif; ?>

  <div class="form-container" style="max-width:600px;">
    <form action="configuracionAction.php" method="POST" novalidate id="perfilForm">
      <input type="hidden" name="action" value="update_profile">

      <h3>Datos de la cuenta</h3>

      <label>Nombre:</label>
      <input type="text" name="nombre" required maxlength="80" value="<?= h($nombre) ?>">

      <label>Email:</label>
      <input type="email" name="email" required maxlength="120"
             value="<?= h($email) ?>" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$">

      <div style="display:flex; gap:10px; align-items:center; margin-top:16px;">
        <button type="submit" class="btn-add" id="btnGuardar" disabled>Guardar cambios</button>
        <button type="button" class="btn-add" id="btnCancelar">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const form = document.getElementById('perfilForm');
  const btnGuardar = document.getElementById('btnGuardar');
  const btnCancelar = document.getElementById('btnCancelar');

  function snap(formEl){
    const data = {};
    formEl.querySelectorAll('input, textarea, select').forEach(el=>{
      if (!el.name) return;
      if (el.type === 'checkbox' || el.type === 'radio') data[el.name] = el.checked ? '1':'0';
      else data[el.name] = el.value ?? '';
    });
    delete data['action'];
    return JSON.stringify(data);
  }
  const initial = snap(form);

  function check(){
    btnGuardar.disabled = (snap(form) === initial);
  }

  form.querySelectorAll('input, textarea, select').forEach(el=>{
    el.addEventListener('input', check);
    el.addEventListener('change', check);
  });

  btnCancelar.addEventListener('click', ()=>{
    if (document.referrer && window.history.length > 1) {
      window.history.back();
    } else {
      // Fallback a tu dashboard admin (ajustá si es otro)
      window.location.href = '../home_admin.php';
    }
  });

  check();
})();
</script>

<?php include '../includes/footer.php'; ?>
