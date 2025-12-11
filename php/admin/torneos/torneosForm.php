<?php
require_once './../includes/header.php';
require_once './../includes/sidebar.php';
require_once './../../config.php';
$rol = $_SESSION['rol'] ?? null;

$torneo_id = isset($_GET['torneo_id']) ? (int)$_GET['torneo_id'] : null;
$nombre=''; $creador_id=(int)($_SESSION['user_id']??0); $proveedor_id=0;
$fecha_inicio=''; $fecha_fin=''; $estado='abierto'; $tipo='equipo'; $capacidad=0; $puntos=0;
$accion='add'; $formTitle='Crear Torneo';

if ($torneo_id){
  $stmt=$conn->prepare("SELECT * FROM torneos WHERE torneo_id=?"); $stmt->bind_param("i",$torneo_id); $stmt->execute();
  if($res=$stmt->get_result()){ if($row=$res->fetch_assoc()){
    $nombre=$row['nombre']; $creador_id=(int)$row['creador_id']; $proveedor_id=(int)($row['proveedor_id']??0);
    $fecha_inicio=$row['fecha_inicio']; $fecha_fin=$row['fecha_fin']; $estado=$row['estado']; $tipo=$row['tipo'];
    $capacidad=(int)$row['capacidad']; $puntos=(int)$row['puntos_ganador']; $accion='edit'; $formTitle='Editar Torneo';
  }} $stmt->close();
}
$usuarios = $conn->query("SELECT user_id, nombre FROM usuarios ORDER BY nombre ASC");
$proveedores = $conn->query("SELECT user_id, nombre FROM usuarios WHERE rol='proveedor' ORDER BY nombre ASC");
?>
<div class="form-container">
  <h2><?= $formTitle ?></h2>
  <?php if ($rol==='admin' && $accion==='add'): ?><div class="alert">El administrador no puede crear torneos. Solo puede modificar.</div><?php endif; ?>

  <form method="POST" action="torneosAction.php" onsubmit="return validarCapacidadPar();">
    <input type="hidden" name="action" value="<?= htmlspecialchars($accion) ?>">
    <input type="hidden" name="torneo_id" value="<?= htmlspecialchars((string)$torneo_id) ?>">

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

    <label>Creador (usuario que carga):</label>
    <select name="creador_id" required>
      <?php if ($usuarios): while($u=$usuarios->fetch_assoc()): ?>
        <option value="<?= (int)$u['user_id'] ?>" <?= ((int)$u['user_id']===$creador_id)?'selected':'' ?>><?= htmlspecialchars($u['nombre']) ?></option>
      <?php endwhile; endif; ?>
    </select>

    <label>Proveedor (club dueño del torneo):</label>
    <select name="proveedor_id">
      <option value="0">-- Sin asignar --</option>
      <?php if ($proveedores): while($p=$proveedores->fetch_assoc()): ?>
        <option value="<?= (int)$p['user_id'] ?>" <?= ((int)$p['user_id']===$proveedor_id)?'selected':'' ?>><?= htmlspecialchars($p['nombre']) ?></option>
      <?php endwhile; endif; ?>
    </select>

    <label>Fecha inicio:</label>
    <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>" required>

    <label>Fecha fin:</label>
    <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>" required>

    <label>Estado:</label>
    <select name="estado" required>
      <?php foreach(['abierto','cerrado','finalizado'] as $opt): ?>
        <option value="<?= $opt ?>" <?= $estado===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Tipo de torneo:</label>
    <select name="tipo" required>
      <?php foreach(['individual','equipo'] as $opt): ?>
        <option value="<?= $opt ?>" <?= $tipo===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Capacidad de participantes (par):</label>
    <input type="number" name="capacidad" min="2" step="1" value="<?= htmlspecialchars((string)$capacidad) ?>" required>

    <label>Puntos para el ganador:</label>
    <input type="number" name="puntos_ganador" min="0" step="1" value="<?= htmlspecialchars((string)$puntos) ?>">

    <button type="submit" class="btn-add" <?= ($rol==='admin' && $accion==='add')?'disabled':'' ?>><?= $formTitle ?></button>
  </form>
</div>
<script>
function validarCapacidadPar(){
  const v = parseInt(document.querySelector('input[name="capacidad"]').value || '0', 10);
  if (!Number.isInteger(v) || v < 2 || v % 2 !== 0){ alert('La capacidad debe ser un número par >= 2.'); return false; }
  return true;
}
</script>
<?php include './../includes/footer.php'; ?>
