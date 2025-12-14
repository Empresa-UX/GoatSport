<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

if (!isset($conn)) {
    die("Error de conexión");
}

$sql = "
    SELECT 
        id,
        nombre_contacto,
        email,
        nombre_club,
        estado,
        fecha_solicitud
    FROM solicitudes_proveedores
    ORDER BY fecha_solicitud DESC
";
$result = $conn->query($sql);
?>

<style>
/* CONTENEDOR */
.section {
    background: #f4f6f8;
    padding: 25px;
    border-radius: 14px;
}

/* HEADER */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    margin: 0;
    color: #043b3d;
}

/* TABLA */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

table th {
    background: #043b3d;
    color: white;
    text-align: left;
    padding: 14px;
    font-size: 14px;
}

table td {
    padding: 12px 14px;
    border-bottom: 1px solid #e5e5e5;
    font-size: 14px;
}

table tr:hover {
    background: #f1f9f9;
}

/* ESTADOS */
.status-pill {
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: bold;
    display: inline-block;
}

.status-pending {
    background: #fff4cc;
    color: #a17c00;
}

.status-available {
    background: #d4f8e8;
    color: #0f6a43;
}

.status-unavailable {
    background: #ffd6d6;
    color: #9c1f1f;
}

/* BOTONES */
.btn-action {
    border: none;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
}

.btn-action.edit {
    background: #e0f2f1;
}

.btn-action.edit:hover {
    background: #b2dfdb;
}
</style>

<div class="section">
    <div class="section-header">
        <h2>Solicitudes de Proveedores</h2>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Contacto</th>
            <th>Email</th>
            <th>Club</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $estado = $row['estado'] ?? 'pendiente';
                $estadoClass = match ($estado) {
                    'aprobada' => 'status-available',
                    'rechazada' => 'status-unavailable',
                    default => 'status-pending'
                };
            ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['nombre_contacto']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['nombre_club']) ?></td>
                <td>
                    <span class="status-pill <?= $estadoClass ?>">
                        <?= ucfirst($estado) ?>
                    </span>
                </td>
                <td>
                    <button class="btn-action edit"
                        onclick="location.href='ver.php?id=<?= $row['id'] ?>'">
                        Ver detalles
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:20px;">
                    No hay solicitudes registradas
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>
