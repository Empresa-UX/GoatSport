<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$user_id = $_GET['user_id'] ?? null;
$nombre = $email = $contrasenia = '';
$accion = 'add';
$formTitle = 'Agregar Proveedor';

if($user_id){
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $nombre = htmlspecialchars($row['nombre']);
        $email = htmlspecialchars($row['email']);
        $contrasenia = htmlspecialchars($row['contrasenia']);
        $accion = 'edit';
        $formTitle = 'Editar Proveedor';
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="proveedoresAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="user_id" value="<?= $user_id ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= $nombre ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= $email ?>" required>

        <label>Contraseña:</label>
        <input type="text" name="contrasenia" value="<?= $contrasenia ?>" required>

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
