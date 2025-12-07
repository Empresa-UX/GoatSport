<?php
include 'auth.php';

/* Traer nombre del usuario (y puntos) para el chip del header.
   Hacemos include de config si no existe $conn (caso home_cliente). */
if (!isset($conn)) {
    include __DIR__ . '/../../config.php';
}

$userId   = (int)($_SESSION['usuario_id'] ?? 0);
$userName = 'Mi Perfil';

if ($userId > 0 && isset($conn)) {
    if ($stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE user_id=? LIMIT 1")) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($res && !empty($res['nombre'])) {
            $userName = $res['nombre'];
        }
    }
}

/* Inicial para avatar (letra) */
$avatarInitial = strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));
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
    <link rel="stylesheet" href="/css/clienteHistorialEstadisticasRanking.css">

    <style>
        /* Chip de perfil */
        .profile-chip {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 999px;
            background: rgba(255,255,255,0.10);
            text-decoration: none; color: #fff;
            transition: background .2s, transform .1s;
        }
        .profile-chip:hover { background: rgba(255,255,255,0.18); transform: translateY(-1px); }
        .profile-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: #ffffff; color: #054a56; font-weight: 800;
            display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,.18);
        }
        .profile-name { font-weight: 700; white-space: nowrap; max-width: 160px; overflow: hidden; text-overflow: ellipsis; }
        .header-right { display: flex; align-items: center; gap: 18px; }
        /* Ajuste del header existente */
        header { gap: 18px; }
        nav a:last-child { margin-right: 0; }
        @media (max-width:900px){
            .profile-name { max-width: 120px; }
            nav { gap: 20px; }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="/img/logotipo.png" alt="Logo Padel">
        </div>

        <div class="header-right">
            <nav>
                <a href="/php/cliente/home_cliente.php">Inicio</a>
                <a href="/php/cliente/historial_estadisticas/historial_estadisticas.php">Mis reservas y estadísticas</a>
                <a href="/php/cliente/ranking/ranking.php">Ranking</a>
                <a href="/php/cliente/torneos/torneos.php">Torneos</a>
                <a href="/php/cliente/reportes/reportes.php">Reportes</a>
                <!-- Quitamos "Cerrar sesión" directo del header -->
            </nav>

            <!-- Chip de perfil → Perfil del jugador -->
            <a class="profile-chip" href="/php/cliente/configuracion/configuracion.php" title="Ver mi perfil">
                <span class="profile-avatar"><?= htmlspecialchars($avatarInitial) ?></span>
                <span class="profile-name"><?= htmlspecialchars($userName) ?></span>
            </a>
        </div>
    </header>
    <main>
