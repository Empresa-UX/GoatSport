<?php
/* =========================================================================
 * file: admin/usuarios/usuariosForm.php
 * Form unificado por rol: clientes | proveedores | recepcionistas
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';

$rol = $_GET['rol'] ?? 'clientes'; // clientes|proveedores|recepcionistas
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if (!in_array($rol,['clientes','proveedores','recepcionistas'], true)) $rol = 'clientes';

$nombre = $email = $contrasenia = '';
$puntos = 0;
$extra = [
  'telefono' => '', 'ciudad' => '', 'nombre_club' => '', 'proveedor_id' => 0
];

if ($user_id > 0) {
  $stmt = $conn->prepare("SELECT * FROM usuarios WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $u = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($u) {
    $nombre = htmlspecialchars($u['nombre']);
    $email  = htmlspecialchars($u['email']);
    $contrasenia = htmlspecialchars($u['contrasenia']);
    $puntos = (int)($u['puntos'] ?? 0);

    if ($rol === 'clientes') {
      $q = $conn->prepare("SELECT telefono, ciudad FROM cliente_detalle WHERE cliente_id=?");
      $q->bind_param("i", $user_id);
      $q->execute();
      $d = $q->get_result()->fetch_assoc();
      $q->close();
      if ($d) { $extra['telefono'] = htmlspecialchars($d['telefono'] ?? ''); $extra['ciudad'] = htmlspecialchars($d['ciudad'] ?? ''); }
    } elseif ($rol === 'proveedores') {
      $q = $conn->prepare("SELECT nombre_club, telefono, ciudad FROM proveedores_detalle WHERE proveedor_id=?");
      $q->bind_param("i", $user_id);
      $q->execute();
      $d = $q->get_result()->fetch_assoc();
      $q->close();
      if ($d) { $extra['nombre_club'] = htmlspecialchars($d['nombre_club'] ?? ''); $extra['telefono'] = htmlspecialchars($d['telefono'] ?? ''); $extra['ciudad'] = htmlspecialchars($d['ciudad'] ?? ''); }
    } else {
      $q = $conn->prepare("SELECT proveedor_id FROM recepcionista_detalle WHERE recepcionista_id=?");
      $q->bind_param("i", $user_id);
      $q->execute();
      $d = $q->get_result()->fetch_assoc();
      $q->close();
      if ($d) { $extra['proveedor_id'] = (int)$d['proveedor_id']; }
    }
  }
}

/* proveedores para select (recepcionistas) */
$proveedores = [];
if ($rol === 'recepcionistas') {
  $rs = $conn->query("
    SELECT u.user_id, COALESCE(pd.nombre_club, u.nombre) AS label
    FROM usuarios u
    LEFT JOIN proveedores_detalle pd ON pd.proveedor_id = u.user_id
    WHERE u.rol='proveedor'
    ORDER BY label ASC
  ");
  if ($rs) $proveedores = $rs->fetch_all(MYSQLI_ASSOC);
}

$title = ($user_id>0 ? 'Editar ' : 'Agregar ') . ucfirst($rol);
?>
<div class="form-container">
  <h2><?= htmlspecialchars($title) ?></h2>
  <form method="POST" action="usuariosAction.php">
    <input type="hidden" name="action" value="<?= $user_id>0 ? 'edit' : 'add' ?>">
    <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
    <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= $nombre ?>" required>

    <label>Email:</label>
    <input type="email" name="email" value="<?= $email ?>" required>

    <label>Contraseña:</label>
    <input type="text" name="contrasenia" value="<?= $contrasenia ?>" required>

    <?php if ($rol === 'clientes'): ?>
      <label>Puntos:</label>
      <input type="number" name="puntos" value="<?= (int)$puntos ?>" min="0">
      <label>Teléfono:</label>
      <input type="text" name="telefono" value="<?= $extra['telefono'] ?>">
      <label>Ciudad:</label>
      <input type="text" name="ciudad" value="<?= $extra['ciudad'] ?>">
    <?php elseif ($rol === 'proveedores'): ?>
      <label>Nombre del club:</label>
      <input type="text" name="nombre_club" value="<?= $extra['nombre_club'] ?>">
      <label>Teléfono:</label>
      <input type="text" name="telefono" value="<?= $extra['telefono'] ?>">
      <label>Ciudad:</label>
      <input type="text" name="ciudad" value="<?= $extra['ciudad'] ?>">
    <?php else: ?>
      <label>Proveedor asignado:</label>
      <select name="proveedor_id" required>
        <option value="">Seleccione...</option>
        <?php foreach ($proveedores as $p): ?>
          <option value="<?= (int)$p['user_id'] ?>" <?= $extra['proveedor_id']==(int)$p['user_id']?'selected':'' ?>><?= htmlspecialchars($p['label']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <button type="submit" class="btn-add"><?= $user_id>0 ? 'Guardar' : 'Crear' ?></button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
