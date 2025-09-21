<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';
?>

<div class="section">
    <div class="section-header">
        <h2>Usuarios (Clientes)</h2>
        <button onclick="location.href='./usuariosForm.php'" class="btn-add">Agregar usuario</button>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Puntos</th>
            <th>Fecha de Registro</th>
            <th>Acciones</th>
        </tr>

        <?php
        $sql = "SELECT user_id, nombre, email, puntos, fecha_registro
                FROM usuarios
                WHERE rol = 'cliente'
                ORDER BY fecha_registro DESC";
        if ($result = $conn->query($sql)):
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $row['user_id'] ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= $row['puntos'] ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($row['fecha_registro'])) ?></td>
                        <td>
                            <button class="btn-action edit"
                                onclick="location.href='usuariosForm.php?user_id=<?= $row['user_id'] ?>'">✏️</button>

                            <form method="POST" action="usuariosAction.php" style="display:inline-block;"
                                onsubmit="return confirm('¿Seguro que quieres eliminar este usuario?');">
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
                    <td colspan="6" style="text-align:center;">No hay clientes registrados</td>
                </tr>
                <?php
            endif;
            $result->free();
        else:
            ?>
            <tr>
                <td colspan="6" style="text-align:center; color:#b00;">
                    Error al consultar la base de datos.
                </td>
            </tr>
            <?php
        endif;
        ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>