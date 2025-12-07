<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../../config.php';

$ranking_id = $_GET['ranking_id'] ?? null;
$usuario_id = '';
$puntos     = 0;
$victorias  = 0;
$derrotas   = 0;
$accion     = 'add';
$formTitle  = 'Agregar al Ranking';

if ($ranking_id) {
    $stmt = $conn->prepare("SELECT * FROM ranking WHERE ranking_id = ?");
    $stmt->bind_param("i", $ranking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $usuario_id = $row['usuario_id'];
        $puntos     = (int)$row['puntos'];
        $victorias  = (int)$row['victorias'];
        $derrotas   = (int)$row['derrotas'];
        $accion     = 'edit';
        $formTitle  = 'Editar Ranking';
    }
    $stmt->close();
}
?>

<div class="form-container">
    <h2><?= $formTitle ?></h2>

    <form method="POST" action="rankingAction.php">
        <input type="hidden" name="action" value="<?= $accion ?>">
        <input type="hidden" name="ranking_id" value="<?= htmlspecialchars($ranking_id) ?>">

        <label>Jugador:</label>
        <select name="usuario_id" required>
            <?php
            $usuarios = $conn->query("SELECT user_id, nombre FROM usuarios ORDER BY nombre ASC");
            while($u = $usuarios->fetch_assoc()):
            ?>
                <option value="<?= $u['user_id'] ?>" <?= ($u['user_id'] == $usuario_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Puntos:</label>
        <input type="number" name="puntos" value="<?= (int)$puntos ?>" min="0" required>

        <label>Victorias:</label>
        <input type="number" name="victorias" value="<?= (int)$victorias ?>" min="0" required>

        <label>Derrotas:</label>
        <input type="number" name="derrotas" value="<?= (int)$derrotas ?>" min="0" required>

        <p style="font-size:12px; color:#555; margin-top:8px;">
            Los <strong>partidos jugados</strong> se calculan como <strong>victorias + derrotas</strong>.
        </p>

        <button type="submit" class="btn-add"><?= $formTitle ?></button>
    </form>
</div>

<?php include './../includes/footer.php'; ?>
