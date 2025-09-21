<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';
?>

<div class="section">
    <div class="section-header">
        <h2>Proveedores</h2>
        <button onclick="location.href='proveedoresForm.php'" class="btn-add">Agregar proveedor</button>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Acciones</th>
        </tr>

        <?php
        $sql = "SELECT user_id, nombre, email
                FROM usuarios
                WHERE rol = 'proveedor'
                ORDER BY fecha_registro DESC";
        if ($result = $conn->query($sql)):
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= (int)$row['user_id'] ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
                <button class="btn-action edit" 
                        onclick="location.href='proveedoresForm.php?user_id=<?= $row['user_id'] ?>'">✏️</button>

                <form method="POST" action="proveedoresAction.php" style="display:inline-block;"
                      onsubmit="return confirm('¿Seguro que quieres eliminar este proveedor?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                    <button type="submit" class="btn-action delete">🗑️</button>
                </form>
            </td>
        </tr>
        <?php
                endwhile;
            else:
        ?>
        <tr>
            <td colspan="4" style="text-align:center;">No hay proveedores registrados</td>
        </tr>
        <?php
            endif;
            $result->free();
        else:
        ?>
        <tr>
            <td colspan="4" style="text-align:center; color:#b00;">
                Error al consultar la base de datos.
            </td>
        </tr>
        <?php
        endif;
        ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
