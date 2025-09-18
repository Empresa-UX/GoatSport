<?php
session_start();

// Redirigir al login si no está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$rol = $_SESSION['rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GoatSport</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h2>GoatSport - Sistema de Gestión</h2>
        <nav>
            <ul>
                <?php if ($rol === 'admin'): ?>
                    <li><a href="home_admin.php">Inicio</a></li>
                    <li><a href="gestionar_usuarios.php">Usuarios</a></li>
                    <li><a href="reportes.php">Reportes</a></li>
                <?php elseif ($rol === 'proveedor'): ?>
                    <li><a href="home_proveedor.php">Inicio</a></li>
                    <li><a href="mis_productos.php">Mis Productos</a></li>
                <?php else: ?>
                    <li><a href="home_cliente.php">Inicio</a></li>
                    <li><a href="reservar.php">Reservar Cancha</a></li>
                    <li><a href="mis_reservas.php">Mis Reservas</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Salir</a></li>
            </ul>
        </nav>
    </header>
    <main>
