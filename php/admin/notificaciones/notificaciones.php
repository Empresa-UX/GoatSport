<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

// ----------------------------------------------------
// 1) Traer lista de tipos distintos para el filtro
// ----------------------------------------------------
$tipos = [];
$resTipos = $conn->query("SELECT DISTINCT tipo FROM notificaciones ORDER BY tipo ASC");
if ($resTipos) {
    while ($t = $resTipos->fetch_assoc()) {
        if (!empty($t['tipo'])) {
            $tipos[] = $t['tipo'];
        }
    }
}

// ----------------------------------------------------
// 2) Filtros desde GET
// ----------------------------------------------------
$filtro_tipo  = $_GET['tipo']  ?? 'todos';
$filtro_leida = $_GET['leida'] ?? 'todas';

$where  = [];
$params = [];
$types  = "";

// Filtrar por tipo si no es "todos"
if ($filtro_tipo !== 'todos') {
    $where[]  = "n.tipo = ?";
    $types   .= "s";
    $params[] = $filtro_tipo;
}

// Filtrar por leída / no leída
if ($filtro_leida === 'leidas') {
    $where[] = "n.leida = 1";
} elseif ($filtro_leida === 'no_leidas') {
    $where[] = "n.leida = 0";
}

// Armar SQL base
$sql = "
    SELECT 
        n.notificacion_id,
        n.usuario_id,
        n.tipo,
        n.titulo,
        n.mensaje,
        n.creada_en,
        n.leida,
        u.nombre AS usuario_nombre,
        u.rol    AS usuario_rol
    FROM notificaciones n
    INNER JOIN usuarios u ON n.usuario_id = u.user_id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY n.creada_en DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section">
    <div class="section-header">
        <h2>Notificaciones del sistema</h2>
    </div>

    <style>
        .filtros-box {
            display:flex;
            gap:15px;
            align-items:center;
            background:#fff;
            padding:10px 15px;
            border-radius:10px;
            box-shadow:0 4px 8px rgba(0,0,0,0.06);
            margin-bottom:15px;
            flex-wrap:wrap;
        }
        .filtros-box label {
            font-size:13px;
            color:#043b3d;
            font-weight:600;
        }
        .filtros-box select {
            margin-top:4px;
            padding:6px 8px;
            border-radius:8px;
            border:1px solid #ccc;
            background:#fafafa;
            font-size:13px;
            min-width:140px;
            cursor:pointer;
        }
        .filtros-box select:focus {
            outline:none;
            border-color:#043b3d;
            background:#fff;
        }

        table th:nth-child(1) { width:60px; }
        table th:nth-child(2) { width:150px; }
        table th:nth-child(3) { width:180px; }
        table th:nth-child(4) { width:260px; }
        table th:nth-child(5) { width:110px; }
        table th:nth-child(6) { width:110px; }
    </style>

    <!-- Filtros -->
    <form method="GET" class="filtros-box">
        <label>
            Tipo<br>
            <select name="tipo" onchange="this.form.submit()">
                <option value="todos" <?= $filtro_tipo==='todos'?'selected':'' ?>>Todos</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= $filtro_tipo===$t?'selected':'' ?>>
                        <?= htmlspecialchars(ucfirst($t)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Estado<br>
            <select name="leida" onchange="this.form.submit()">
                <option value="todas"     <?= $filtro_leida==='todas'?'selected':'' ?>>Todas</option>
                <option value="no_leidas" <?= $filtro_leida==='no_leidas'?'selected':'' ?>>No leídas</option>
                <option value="leidas"    <?= $filtro_leida==='leidas'?'selected':'' ?>>Leídas</option>
            </select>
        </label>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Usuario</th>
            <th>Título / Mensaje</th>
            <th>Tipo</th>
            <th>Estado</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($n = $result->fetch_assoc()): ?>
                <?php
                    $pillClass = $n['leida'] ? 'status-available' : 'status-pending';
                    $estadoTxt = $n['leida'] ? 'Leída' : 'No leída';
                ?>
                <tr>
                    <td><?= $n['notificacion_id'] ?></td>
                    <td><?= htmlspecialchars($n['creada_en']) ?></td>
                    <td>
                        <?= htmlspecialchars($n['usuario_nombre']) ?><br>
                        <small>(<?= htmlspecialchars($n['usuario_rol']) ?>)</small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($n['titulo']) ?></strong><br>
                        <small><?= htmlspecialchars($n['mensaje']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($n['tipo']) ?></td>
                    <td>
                        <span class="status-pill <?= $pillClass ?>"><?= $estadoTxt ?></span>

                        <!-- Botón para cambiar estado -->
                        <form action="marcar_leida.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $n['notificacion_id'] ?>">

                            <?php if ($n['leida'] == 0): ?>
                                <button style="
                                    margin-left:6px; 
                                    padding:4px 8px; 
                                    font-size:11px; 
                                    background:#0a7c0a; 
                                    color:#fff; 
                                    border:none; 
                                    border-radius:5px;
                                    cursor:pointer;">
                                    Marcar leída
                                </button>
                            <?php else: ?>
                                <button style="
                                    margin-left:6px; 
                                    padding:4px 8px; 
                                    font-size:11px; 
                                    background:#999; 
                                    color:#fff; 
                                    border:none; 
                                    border-radius:5px;
                                    cursor:pointer;">
                                    Marcar NO leída
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center;">No hay notificaciones registradas</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php
$stmt->close();
include './../includes/footer.php';
?>
