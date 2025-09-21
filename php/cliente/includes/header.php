<?php
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Cliente | Padel Alquiler</title>
    <link rel="stylesheet" href="/css/cliente.css">
    <link rel="stylesheet" href="/css/clienteReservas.css">
</head>

<body>
    <header>
        <img src="./../../img/logo_padel.png" alt="Logo Padel">
        <nav>
            <a href="/php/cliente/home_cliente.php">Inicio</a>
            <a href="/php/cliente/historial_estadisticas.php">Mis reservas y estadísticas</a>
            <a href="/php/cliente/ranking.php">Ranking</a>
            <a href="/php/logout.php">Cerrar sesión</a>
        </nav>
    </header>

    <main>