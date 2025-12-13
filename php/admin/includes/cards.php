<?php
// File: admin/partials/cards.php
include './../../config.php';

/**
 * Ejecuta un COUNT(*) y retorna entero; 0 si falla.
 * Motivo: evitar romper la UI por errores/NULLs.
 */
function fetch_int(mysqli $conn, string $sql): int {
    $res = $conn->query($sql);
    if (!$res) { return 0; }
    $row = $res->fetch_row();
    return isset($row[0]) ? (int)$row[0] : 0;
}

// 1) Proveedores totales
$totalProveedores = fetch_int($conn, "
    SELECT COUNT(*) FROM usuarios WHERE rol = 'proveedor'
");

// 2) Canchas totales (todas las de todos los proveedores)
$totalCanchas = fetch_int($conn, "
    SELECT COUNT(*) FROM canchas
");

// 3) Reservas totales (excluye canceladas)
$totalReservasActivas = fetch_int($conn, "
    SELECT COUNT(*) FROM reservas WHERE estado <> 'cancelada'
");

// 4) Torneos totales (sin contar los que ya pasaron)
$totalTorneosVigentes = fetch_int($conn, "
    SELECT COUNT(*) FROM torneos WHERE fecha_fin >= CURDATE()
");
?>

<div class="cards">
    <div class="card">
        <h3>Proveedores totales</h3>
        <p><?= number_format($totalProveedores, 0, ',', '.') ?></p>
    </div>
    <div class="card">
        <h3>Canchas activas</h3>
        <p><?= number_format($totalCanchas, 0, ',', '.') ?></p>
    </div>
    <div class="card">
        <h3>Reservas totales</h3>
        <p><?= number_format($totalReservasActivas, 0, ',', '.') ?></p>
    </div>
    <div class="card">
        <h3>Próximos torneos</h3>
        <p><?= number_format($totalTorneosVigentes, 0, ',', '.') ?></p>
    </div>
</div>
