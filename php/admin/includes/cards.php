<?php
include './../../config.php';

$sqlIngresos = "SELECT SUM(monto) AS total_ingresos FROM pagos WHERE estado = 'pagado'";
$result = $conn->query($sqlIngresos);
$totalIngresos = ($result && $row = $result->fetch_assoc()) ? $row['total_ingresos'] : 0;

// 2. Total reservas
$sqlTotalReservas = "SELECT COUNT(*) AS total_reservas FROM reservas";
$result = $conn->query($sqlTotalReservas);
$totalReservas = ($result && $row = $result->fetch_assoc()) ? $row['total_reservas'] : 0;

// 3. Reservas esta semana
$hoy = date('Y-m-d');
$semanaFin = date('Y-m-d', strtotime('+7 days'));
$sqlReservasSemana = "
    SELECT COUNT(*) AS reservas_semana 
    FROM reservas 
    WHERE fecha BETWEEN '$hoy' AND '$semanaFin'
";
$result = $conn->query($sqlReservasSemana);
$reservasSemana = ($result && $row = $result->fetch_assoc()) ? $row['reservas_semana'] : 0;

// 4. Cantidad de canchas
$sqlCanchas = "SELECT COUNT(*) AS total_canchas FROM canchas";
$result = $conn->query($sqlCanchas);
$totalCanchas = ($result && $row = $result->fetch_assoc()) ? $row['total_canchas'] : 0;
?>

<div class="cards">
    <div class="card"><h3>Total Ingresos</h3><p>$<?= number_format($totalIngresos, 2) ?></p></div>
    <div class="card"><h3>Total Reservas</h3><p><?= $totalReservas ?></p></div>
    <div class="card"><h3>Reservas esta semana</h3><p><?= $reservasSemana ?></p></div>
    <div class="card"><h3>Canchas</h3><p><?= $totalCanchas ?></p></div>
</div>
