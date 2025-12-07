<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

// Traemos proveedores + cantidad de canchas
$sql = "
    SELECT 
        u.user_id,
        u.nombre,
        u.email,
        u.fecha_registro,
        COUNT(c.cancha_id) AS total_canchas
    FROM usuarios u
    LEFT JOIN canchas c ON c.proveedor_id = u.user_id
    WHERE u.rol = 'proveedor'
    GROUP BY u.user_id, u.nombre, u.email, u.fecha_registro
    ORDER BY u.fecha_registro DESC
";

$result = $conn->query($sql);
?>

<div class="section">
    <div class="section-header">
        <h2>Proveedores</h2>
        <button onclick="location.href='proveedoresForm.php'" class="btn-add">Agregar proveedor</button>
    </div>

    <style>
        .filtros-admin {
            display:flex;
            gap:15px;
            align-items:center;
            background:white;
            padding:12px 18px;
            border-radius:10px;
            box-shadow:0 4px 10px rgba(0,0,0,0.08);
            margin-bottom:18px;
            width:max-content;
        }

        .filtros-admin label {
            font-weight:bold;
            color:#043b3d;
            font-size:14px;
        }

        .filtros-admin input[type="text"] {
            padding:8px 10px;
            border-radius:8px;
            border:1px solid #c8c8c8;
            background:#f8f8f8;
            font-size:14px;
            transition:.2s;
        }

        .filtros-admin input[type="text"]:focus {
            outline:none;
            border-color:#0a5557;
            background:#fff;
            box-shadow:0 0 4px rgba(4,59,61,0.3);
        }
    </style>

    <!-- (opcional) pequeño buscador por nombre/email, sin lógica todavía -->
    <!--
    <form method="GET" class="filtros-admin">
        <label>
            Buscar:
            <input type="text" name="q" placeholder="Nombre o email">
        </label>
    </form>
    -->

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Canchas</th>
            <th>Fecha registro</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['user_id'] ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= (int)$row['total_canchas'] ?></td>
                    <td><?= htmlspecialchars($row['fecha_registro']) ?></td>
                    <td>
                        <button class="btn-action edit" 
                                onclick="location.href='proveedoresForm.php?user_id=<?= $row['user_id'] ?>'">
                            ✏️
                        </button>

                        <form method="POST" action="proveedoresAction.php" style="display:inline-block;"
                              onsubmit="return confirm('¿Seguro que quieres eliminar este proveedor?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                            <button type="submit" class="btn-action delete">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center;">No hay proveedores registrados</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>
