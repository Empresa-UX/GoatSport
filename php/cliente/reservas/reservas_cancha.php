<?php
require './../../config.php';

try {
    $sql = "SELECT cancha_id, nombre, ubicacion, tipo, capacidad, precio FROM canchas";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }

    $canchas = [];
    while ($row = $result->fetch_assoc()) {
        $canchas[] = $row;
    }

    // Clasificar por tipo
    $clasica    = array_values(array_filter($canchas, fn($c) => $c["tipo"] === "clasica"));
    $cubierta   = array_values(array_filter($canchas, fn($c) => $c["tipo"] === "cubierta"));
    $panoramica = array_values(array_filter($canchas, fn($c) => $c["tipo"] === "panoramica"));

} catch (Exception $e) {
    die("Error al cargar canchas: " . $e->getMessage());
}
?>

<?php include './../includes/header.php'; ?>

<div class="page-wrap">
    <h1 style="color:white; text-align:center; margin-bottom:30px;">Seleccionar Cancha</h1>

    <div class="grid">
        <!-- CLÁSICAS -->
        <div class="card">
            <img src="./../../../img/canchas/clasica.png" alt="Cancha Clásica">
            <h2>Cancha Clásica</h2>
            <select onchange="mostrarInfo(this, 'clasica')">
                <option value="">Seleccione una cancha</option>
                <?php foreach ($clasica as $c): ?>
                    <option value='<?= json_encode($c) ?>'>
                        <?= htmlspecialchars($c["nombre"]) ?> - <?= htmlspecialchars($c["ubicacion"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="info-box" id="info-clasica"></div>
        </div>

        <!-- CUBIERTAS -->
        <div class="card">
            <img src="./../../../img/canchas/techada.png" alt="Cancha Techada">
            <h2>Cancha Cubierta</h2>
            <select onchange="mostrarInfo(this, 'cubierta')">
                <option value="">Seleccione una cancha</option>
                <?php foreach ($cubierta as $c): ?>
                    <option value='<?= json_encode($c) ?>'>
                        <?= htmlspecialchars($c["nombre"]) ?> - <?= htmlspecialchars($c["ubicacion"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="info-box" id="info-cubierta"></div>
        </div>

        <!-- PANORÁMICAS -->
        <div class="card">
            <img src="./../../../img/canchas/panoramica.png" alt="Cancha Panorámica">
            <h2>Cancha Panorámica</h2>
            <select onchange="mostrarInfo(this, 'panoramica')">
                <option value="">Seleccione una cancha</option>
                <?php foreach ($panoramica as $c): ?>
                    <option value='<?= json_encode($c) ?>'>
                        <?= htmlspecialchars($c["nombre"]) ?> - <?= htmlspecialchars($c["ubicacion"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="info-box" id="info-panoramica"></div>
        </div>
    </div>
</div>

<?php include './../includes/footer.php'; ?>

<script>
function mostrarInfo(select, tipo){
    const infoBox = document.getElementById('info-' + tipo);
    if(!select.value){
        infoBox.classList.remove("show");
        infoBox.innerHTML = "";
        return;
    }
    const cancha = JSON.parse(select.value);
    infoBox.innerHTML = `
        <p><strong>Tipo:</strong> Cancha de pádel ${cancha.tipo}</p>
        <p><strong>Capacidad:</strong> ${cancha.capacidad} jugadores</p>
        <p><strong>Precio:</strong> $ ${parseFloat(cancha.precio).toLocaleString()}</p>
        <button class="btn-select" onclick="seleccionarCancha(${cancha.cancha_id})">Seleccionar</button>
    `;
    infoBox.classList.add("show");
}
function seleccionarCancha(id){
    window.location.href = 'reservas.php?cancha=' + id;
}
</script>
