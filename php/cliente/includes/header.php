<?php
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel Cliente | GoatSport</title>
    <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
    <link rel="stylesheet" href="/css/cliente.css">
    <link rel="stylesheet" href="/css/clienteReservas.css">
    <link rel="stylesheet" href="../../../css/clienteCRUD.css">
    <link rel="stylesheet" href="/css/clienteHistorialEstadisticasRanking.css">

</head>

<body>
    <header>
        <!-- Div separado para el logo -->
        <div class="logo-container">
            <img src="/img/logotipo.png" alt="Logo Padel">
        </div>

        <nav>
            <a href="/php/cliente/home_cliente.php">Inicio</a>
            <a href="/php/cliente/historial_estadisticas/historial_estadisticas.php">Mis reservas y estadísticas</a>
            <a href="/php/cliente/ranking/ranking.php">Ranking</a>
            <a href="/php/cliente/torneos/torneos.php">Torneos</a>
            <a href="/php/logout.php">Cerrar sesión</a>
        </nav>
    </header>
    <main>