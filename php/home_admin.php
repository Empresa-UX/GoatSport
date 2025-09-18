<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador | Padel Alquiler</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f4f6f8;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 220px;
            background: linear-gradient(to bottom, #054a56ff, #1bab9dff);
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 20px 0;
        }

        .sidebar img {
            width: 120px;
            margin: 0 auto 30px auto;
            display: block;
        }

        .sidebar a {
            padding: 15px 25px;
            display: block;
            text-decoration: none;
            color: #dcdcdc;
            transition: background 0.3s, 054a56ff 0.3s;
            font-size: 15px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #065e60;
            color: #fff;
        }

        /* ===== MAIN CONTENT ===== */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 25px;
        }

        .header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 25px;
        }

        .header .user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            color: #043b3d;
        }

        /* DASHBOARD CARDS */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .card h3 {
            font-size: 15px;
            color: #555;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 22px;
            font-weight: bold;
            color: #043b3d;
        }

        /* TABLES */
        .section {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .section h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #043b3d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f0f0f0;
        }

        .status-available {
            color: green;
            font-weight: bold;
        }

        .status-booked {
            color: red;
            font-weight: bold;
        }

        footer {
            text-align: center;
            font-size: 13px;
            color: #555;
            margin-top: auto;
            padding: 15px;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <img src="../img/logo_padel.png" alt="Logo Padel">
        <a href="home_admin.php" class="active">Dashboard</a>
        <a href="usuarios.php">Usuarios</a>
        <a href="proveedores.php">Proveedores</a>
        <a href="reportes.php">Reportes</a>
        <a href="logout.php">Cerrar sesión</a>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="header">
            <div class="user">
                <span>👤 Admin</span>
            </div>
        </div>

        <!-- CARDS -->
        <div class="cards">
            <div class="card">
                <h3>Total Ingresos</h3>
                <p>$8.450</p>
            </div>
            <div class="card">
                <h3>Total Reservas</h3>
                <p>1.245</p>
            </div>
            <div class="card">
                <h3>Reservas esta semana</h3>
                <p>75</p>
            </div>
            <div class="card">
                <h3>Canchas</h3>
                <p>6</p>
            </div>
        </div>

        <!-- COURTS SECTION -->
        <div class="section">
            <h2>Canchas</h2>
            <table>
                <tr>
                    <th>Cancha</th>
                    <th>Tipo</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                </tr>
                <tr>
                    <td>Cancha 1</td>
                    <td>Sintética</td>
                    <td>Central</td>
                    <td class="status-available">Disponible</td>
                </tr>
                <tr>
                    <td>Cancha 2</td>
                    <td>Sintética</td>
                    <td>Este</td>
                    <td class="status-booked">Reservada</td>
                </tr>
            </table>
        </div>

        <!-- USERS SECTION -->
        <div class="section">
            <h2>Usuarios</h2>
            <table>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                </tr>
                <tr>
                    <td>Juan Pérez</td>
                    <td>juan@example.com</td>
                    <td>Admin</td>
                    <td class="status-available">Activo</td>
                </tr>
                <tr>
                    <td>María López</td>
                    <td>maria@example.com</td>
                    <td>Cliente</td>
                    <td class="status-available">Activo</td>
                </tr>
            </table>
        </div>

        <footer>
            <p>Padel Alquiler © <?= date("Y"); ?> - Todos los derechos reservados</p>
        </footer>
    </div>

</body>
</html>
