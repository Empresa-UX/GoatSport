<?php
require './../../config.php';

try {
    $result = $conn->query("SELECT cancha_id, nombre, ubicacion, tipo, capacidad, precio FROM canchas");
    if (!$result) throw new Exception($conn->error);

    $canchasPorTipo = [];
    while ($row = $result->fetch_assoc()) {
        $canchasPorTipo[$row['tipo']][] = $row;
    }
} catch (Exception $e) {
    die("Error al cargar canchas: " . $e->getMessage());
}
?>

<?php include './../includes/header.php'; ?>

<div class="page-wrap">
    <h1 style="color:white; text-align:center; margin-bottom:30px;" class=".bton">Seleccione una cancha</h1>

    <div class="grid">
        <?php
        $tipos = [
            'clasica'    => ['Cancha Clásica', './../../../img/canchas/clasica.png'],
            'cubierta'   => ['Cancha Cubierta', './../../../img/canchas/techada.png'],
            'panoramica' => ['Cancha Panorámica', './../../../img/canchas/panoramica.png'],
        ];

        foreach ($tipos as $tipo => [$label, $img]): ?>
            <div class="card">
                <img src="<?= $img ?>" alt="<?= $label ?>">
                <h2><?= $label ?></h2>
                <select onchange="mostrarInfo(this, '<?= $tipo ?>')">
                    <option value="">Seleccione una cancha</option>
                    <?php foreach ($canchasPorTipo[$tipo] ?? [] as $c): ?>
                        <option 
                            value="<?= $c['cancha_id'] ?>"
                            data-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                            data-ubicacion="<?= htmlspecialchars($c['ubicacion']) ?>"
                            data-capacidad="<?= $c['capacidad'] ?>"
                            data-precio="<?= $c['precio'] ?>"
                            data-tipo="<?= $c['tipo'] ?>"
                        >
                            <?= htmlspecialchars($c['nombre']) ?> - <?= htmlspecialchars($c['ubicacion']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="info-box" id="info-<?= $tipo ?>"></div>
            </div>
        <?php endforeach; ?>
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
    const c = select.options[select.selectedIndex].dataset;
    infoBox.innerHTML = `
        <p><strong>Tipo:</strong> Cancha de pádel ${c.tipo}</p>
        <p><strong>Capacidad:</strong> ${c.capacidad} jugadores</p>
        <p><strong>Precio:</strong> $ ${parseFloat(c.precio).toLocaleString()}</p>
        <button class="btn-select" onclick="seleccionarCancha(${select.value})">Seleccionar</button>
    `;
    infoBox.classList.add("show");
}
function seleccionarCancha(id){
    window.location.href = 'reservas.php?cancha=' + id;
}
</script>
