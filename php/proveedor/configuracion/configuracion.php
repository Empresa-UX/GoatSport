<?php
// php/proveedor/configuracion/configuracion.php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header("Location: ../login.php"); exit();
}
$proveedor_id = (int)$_SESSION['usuario_id'];

/* Usuario */
$stmt = $conn->prepare("SELECT nombre, email FROM usuarios WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $proveedor_id);
$stmt->execute(); $usuario = $stmt->get_result()->fetch_assoc(); $stmt->close();
$nombre = $usuario['nombre'] ?? '';
$email  = $usuario['email']  ?? '';

/* Detalle proveedor */
$stmt = $conn->prepare("
  SELECT nombre_club, telefono, direccion, ciudad, descripcion
  FROM proveedores_detalle WHERE proveedor_id=? LIMIT 1
");
$stmt->bind_param("i", $proveedor_id);
$stmt->execute(); $detalle = $stmt->get_result()->fetch_assoc(); $stmt->close();

$nombre_club = $detalle['nombre_club'] ?? '';
$telefono    = $detalle['telefono']    ?? '';
$direccion   = $detalle['direccion']   ?? '';
$ciudad      = $detalle['ciudad']      ?? '';
$descripcion = $detalle['descripcion'] ?? '';

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<div class="section">
  <div class="section-header">
    <h2>Mi perfil / Datos del club</h2>
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

      <label>Nombre (contacto):</label>
      <input type="text" name="nombre" required maxlength="80"
             value="<?= h($nombre) ?>">

      <label>Email:</label>
      <input type="email" name="email" required maxlength="120"
             value="<?= h($email) ?>"
             pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$">

      <hr style="margin:20px 0;">

      <h3>Datos del club</h3>

      <label>Nombre del club:</label>
      <input type="text" name="nombre_club" maxlength="100"
             value="<?= h($nombre_club) ?>">

      <label>Teléfono / WhatsApp:</label>
      <input type="text" name="telefono" maxlength="25"
             placeholder="+54 9 11 1234-5678"
             value="<?= h($telefono) ?>">

      <label>Dirección:</label>
      <input type="text" name="direccion" maxlength="140"
             value="<?= h($direccion) ?>">

      <label>Ciudad:</label>
      <input type="text" name="ciudad" maxlength="80"
             value="<?= h($ciudad) ?>">

      <label>Descripción del club:</label>
      <textarea name="descripcion" rows="4" maxlength="1000"><?= h($descripcion) ?></textarea>

      <div style="display:flex; gap:10px; align-items:center; margin-top:15px;">
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

  function snapshot(formEl){
    const data = {};
    formEl.querySelectorAll('input, textarea, select').forEach(el=>{
      if (!el.name) return;
      if (el.type === 'checkbox' || el.type === 'radio') {
        data[el.name] = el.checked ? '1' : '0';
      } else {
        data[el.name] = el.value ?? '';
      }
    });
    delete data['action'];
    return JSON.stringify(data);
  }

  const initial = snapshot(form);

  function checkChanged(){
    btnGuardar.disabled = (snapshot(form) === initial);
  }

  form.querySelectorAll('input, textarea, select').forEach(el=>{
    el.addEventListener('input', checkChanged);
    el.addEventListener('change', checkChanged);
  });

  // Cancelar: volver a la pantalla anterior
  btnCancelar.addEventListener('click', ()=>{
    if (document.referrer && window.history.length > 1) {
      window.history.back();
    } else {
      // Fallback si llegó directo (ajustá esta ruta si tu dashboard es otra)
      window.location.href = '../home_proveedor.php';
    }
  });

  checkChanged();
})();
</script>

<?php include '../includes/footer.php'; ?>
