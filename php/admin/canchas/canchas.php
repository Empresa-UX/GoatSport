<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';
?>

<div class="section">
    <div class="section-header">
        <h2>Canchas</h2>
        <button onclick="location.href='canchasForm.php'" class="btn-add">Agregar Cancha</button>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Ubicación</th>
            <th>Tipo</th>
            <th>Capacidad</th>
            <th>Precio</th>
            <th>Acciones</th>
        </tr>

        <?php
        $sql = "SELECT cancha_id, nombre, ubicacion, tipo, capacidad, precio FROM canchas ORDER BY nombre ASC";
        if ($result = $conn->query($sql)):
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['cancha_id'] ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['ubicacion']) ?></td>
            <td><?= htmlspecialchars($row['tipo']) ?></td>
            <td><?= (int)$row['capacidad'] ?></td>
            <td>$<?= number_format($row['precio'],2) ?></td>
            <td>
                <button class="btn-action edit" onclick="location.href='canchasForm.php?cancha_id=<?= $row['cancha_id'] ?>'">✏️</button>

                <form method="POST" action="canchasAction.php" style="display:inline-block;" 
                      onsubmit="return confirm('¿Seguro que quieres eliminar esta cancha?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="cancha_id" value="<?= $row['cancha_id'] ?>">
                    <button type="submit" class="btn-action delete">🗑️</button>
                </form>
            </td>
        </tr>
        <?php
                endwhile;
            else:
        ?>
        <tr>
            <td colspan="7" style="text-align:center;">No hay canchas registradas</td>
        </tr>
        <?php
            endif;
            $result->free();
        else:
        ?>
        <tr>
            <td colspan="7" style="text-align:center; color:#b00;">Error al consultar la base de datos.</td>
        </tr>
        <?php
        endif;
        ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
