<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Cliente | Padel Alquiler</title>
    <style>
        * {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(to bottom, #054a56ff, #1bab9dff);
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        /* HEADER */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: rgba(0, 0, 0, 0.2);
        }

        header img {
            width: 120px;
        }

        nav a {
            font-size: 15px;
            margin-left: 35px;
            text-decoration: none;
            color: #f0f0f0;
            transition: color 0.3s;
        }

        nav a:hover {
            color: #d9faff;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 60px 100px;
        }

        .main-text {
            max-width: 100%;
        }

        .main-text h1 {
            width: 80%;
            font-size: 65px;
            margin-bottom: 40px;
        }

        .main-text p {
            font-size: 22px;
            margin-bottom: 40px;
        }

        .btn {
            padding: 14px 40px;
            background-color: #07566bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #0077b6;
        }

        .main-image img {
            height: 350px;
            width: 400px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        footer {
            text-align: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            font-size: 14px;
        }
    </style>
</head>
<body>

<header>
    <img src="../img/logo_padel.png" alt="Logo Padel">
    <nav>
        <a href="home_cliente.php">Inicio</a>
        <a href="reservas.php">Mis Reservas</a>
        <a href="promociones.php">Promociones</a>
        <a href="ranking.php">Ranking</a>
        <a href="logout.php">Cerrar sesión</a>
    </nav>
</header>

<main>
    <!-- COLUMNA IZQUIERDA -->
    <div class="main-text">
        <h1>Alquila tu cancha de pádel</h1>
        <p>Encuentra tu cancha de pádel ideal y reserva en unos minutos.</p>
        <a href="buscar_pistas.php" class="btn">Buscar pista</a>
    </div>

    <!-- COLUMNA DERECHA -->
    <div class="main-image">
        <img src="../img/cancha_padel_hd.png" alt="Imagen pádel">
    </div>
</main>

<footer>
    <p>Padel Alquiler © <?= date("Y"); ?> - Todos los derechos reservados</p>
</footer>

</body>
</html>
