<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$ultimas_reservas = [
    ["fecha" => "21/04/2024", "horario" => "09:00 - 10:00", "ubicacion" => "Av. Corrientes 12, Buenos Aires", "cancha_id" => 1],
    ["fecha" => "19/04/2024", "horario" => "17:00 - 18:00", "ubicacion" => "Av. Libertador 555, Buenos Aires", "cancha_id" => 4],
    ["fecha" => "18/04/2024", "horario" => "14:00 - 15:00", "ubicacion" => "Av. Rivadavia 9000, Buenos Aires", "cancha_id" => 11],
];

$estadisticas = [
    ["label" => "Partidos jugados", "valor" => 25],
    ["label" => "Victorias", "valor" => 18],
    ["label" => "Porcentaje de victorias", "valor" => "72"]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial y Estadísticas | Padel Alquiler</title>
    <style>
        :root {
            --teal-700: #054a56;
            --teal-500: #1bab9d;
            --white: #ffffff;
            --text-dark: #043b3d;
            --text-muted: #5a6b6c;
        }

        * {
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(to bottom, var(--teal-700), var(--teal-500));
            color: var(--white);
            display: flex;
            flex-direction: column;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 40px;
            background: rgba(0, 0, 0, 0.12);
        }

        header img {
            width: 120px;
        }

        nav a {
            margin-left: 30px;
            text-decoration: none;
            color: #f0f0f0;
            font-size: 15px;
        }

        nav a:hover {
            color: #d9faff;
        }

        nav a.active {
            font-weight: 700;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 50px 80px;
        }

        .page-wrap {
            width: 100%;
            max-width: 1100px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .page-title {
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
        }

        .stats-container {
            display: grid;
            grid-template-columns: 1.3fr 0.7fr; /* Historial más ancho que Estadísticas */
            gap: 40px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 14px;
            color: #fff;
        }

        /* Tarjetas blancas */
        .card-white {
            background: var(--white);
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
            color: var(--text-dark);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 17px;
        }

        th, td {
            text-align: left;
            padding: 12px 14px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        th {
            font-weight: 700;
        }

        /* Estilos para estadísticas */
        .label-stat {
            font-weight: 600;
        }

        .value-stat {
            text-align: right;
        }

        @media (max-width: 900px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<header>
    <img src="../img/logo_padel.png" alt="Logo Padel">
    <nav>
        <a href="home_cliente.php">Inicio</a>
        <a href="reservas.php">Mis Reservas</a>
        <a href="historial_estadisticas.php" class="active">Historial</a>
        <a href="promociones.php">Promociones</a>
        <a href="ranking.php">Ranking</a>
        <a href="logout.php">Cerrar sesión</a>
    </nav>
</header>

<main>
    <div class="page-wrap">
        <h1 class="page-title">Historial y estadísticas</h1>

        <div class="stats-container">
            <!-- Historial -->
            <div>
                <h2 class="section-title">Últimas reservas</h2>
                <div class="card-white">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Horario</th>
                                <th>Ubicación</th>
                                <th>N° Cancha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas_reservas as $reserva): ?>
                                <tr>
                                    <td><?= htmlspecialchars($reserva['fecha']) ?></td>
                                    <td><?= htmlspecialchars($reserva['horario']) ?></td>
                                    <td><?= htmlspecialchars($reserva['ubicacion']) ?></td>
                                    <td>#<?= htmlspecialchars($reserva['cancha_id']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Estadísticas -->
            <div>
                <h2 class="section-title">Estadísticas</h2>
                <div class="card-white">
                    <table>
                        <tbody>
                            <?php foreach ($estadisticas as $stat): ?>
                                <tr>
                                    <td class="label-stat"><?= htmlspecialchars($stat['label']) ?></td>
                                    <td class="value-stat"><?= htmlspecialchars($stat['valor']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<footer style="text-align:center; padding:16px; background: rgba(0,0,0,0.12); font-size:14px;">
    <p>Padel Alquiler © <?= date("Y"); ?> - Todos los derechos reservados</p>
</footer>
</body>
</html>
