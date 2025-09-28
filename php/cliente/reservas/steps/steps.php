<?php
function renderSteps($activeStep = 1) {
    $steps = [
        1 => "Reserva",
        2 => "Pago",
        3 => "Confirmación"
    ];
    ?>
    <div class="flow-header">
        <div class="steps-row">
            <?php foreach ($steps as $num => $label): ?>
                <div class="step <?= $activeStep == $num ? 'active' : '' ?>">
                    <div class="circle"><?= $num ?></div>
                    <div class="label"><?= $label ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
}
?>
