<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="section-home" style="text-align:center; padding:40px;">
    <h1 style="font-size: 30px; color:#043b3d; margin-bottom: 15px;">
        👋 ¡Bienvenido de nuevo, Proveedor!
    </h1>
    <p style="font-size: 18px; color:#555; max-width:750px; margin:0 auto;">
        Administra tu <strong>club de pádel</strong> de manera fácil y rápida.
        Aquí encontrarás todo lo que necesitas para gestionar tus 
        <strong>canchas</strong>, <strong>reservas</strong>, <strong>pagos</strong>, 
        <strong>torneos</strong>, <strong>eventos especiales</strong> y 
        <strong>promociones</strong>.
    </p>

    <!-- ACCESO RÁPIDO -->
    <div
        style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-top:40px; max-width:1000px; margin-left:auto; margin-right:auto;">

        <!-- Mis canchas -->
        <a href="./canchas/canchas.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🏟️</div>
                <h3 style="color:#043b3d;">Mis canchas</h3>
                <p style="color:#666; font-size:14px;">Configura disponibilidad, precios y tipos de cancha</p>
            </div>
        </a>

        <!-- Reservas -->
        <a href="./reservas/reservas.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🎾</div>
                <h3 style="color:#043b3d;">Reservas</h3>
                <p style="color:#666; font-size:14px;">Ver y gestionar reservas de tus canchas</p>
            </div>
        </a>

        <!-- Calendario -->
        <a href="./reservas/calendario.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">📅</div>
                <h3 style="color:#043b3d;">Calendario</h3>
                <p style="color:#666; font-size:14px;">Visualiza la ocupación por día y horario</p>
            </div>
        </a>

        <!-- Pagos -->
        <a href="./pagos/pagos.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">💳</div>
                <h3 style="color:#043b3d;">Pagos</h3>
                <p style="color:#666; font-size:14px;">Pagos online y “pagar en el club”</p>
            </div>
        </a>

        <!-- Torneos -->
        <a href="./torneos/torneos.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🏅</div>
                <h3 style="color:#043b3d;">Torneos</h3>
                <p style="color:#666; font-size:14px;">Crear torneos y gestionar partidos</p>
            </div>
        </a>

        <!-- Notificaciones -->
        <a href="./notificaciones/notificaciones.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🔔</div>
                <h3 style="color:#043b3d;">Notificaciones</h3>
                <p style="color:#666; font-size:14px;">Avisos por reportes, reservas y torneos</p>
            </div>
        </a>

        <!-- Eventos especiales -->
        <a href="./eventos/eventos.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🎉</div>
                <h3 style="color:#043b3d;">Eventos especiales</h3>
                <p style="color:#666; font-size:14px;">Bloqueos, clínicas, torneos internos, etc.</p>
            </div>
        </a>

        <!-- Reportes -->
        <a href="./reportes/reportes.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">📣</div>
                <h3 style="color:#043b3d;">Reportes</h3>
                <p style="color:#666; font-size:14px;">Ver quejas y sugerencias sobre tus canchas</p>
            </div>
        </a>

        <!-- Ranking -->
        <a href="./ranking/ranking.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🏆</div>
                <h3 style="color:#043b3d;">Ranking</h3>
                <p style="color:#666; font-size:14px;">Consultar el ranking de jugadores de tu club</p>
            </div>
        </a>

        <!-- Promociones -->
        <a href="./promociones/promociones.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🔥</div>
                <h3 style="color:#043b3d;">Promociones</h3>
                <p style="color:#666; font-size:14px;">Horas valle, descuentos y beneficios</p>
            </div>
        </a>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
