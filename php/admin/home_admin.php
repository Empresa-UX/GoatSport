<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- CONTENIDO DEL HOME ADMIN -->
<div class="section-home" style="text-align:center; padding:40px;">
    <h1 style="font-size: 30px; color:#043b3d; margin-bottom: 15px;">
        👋 ¡Bienvenido de nuevo, Administrador!
    </h1>
    <p style="font-size: 18px; color:#555; max-width:750px; margin:0 auto;">
        Administra tu club de manera fácil y rápida.
        Aquí encontrarás todo lo que necesitas para mantener <strong>canchas</strong>,
        <strong>usuarios</strong>, <strong>proveedores</strong>,  y demás bajo control.
    </p>

    <!-- ACCESO RÁPIDO -->
    <div
        style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-top:40px; max-width:900px; margin-left:auto; margin-right:auto;">
        <a href="./canchas/canchas.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🏟️</div>
                <h3 style="color:#043b3d;">Gestionar Canchas</h3>
                <p style="color:#666; font-size:14px;">Ver disponibilidad y estados</p>
            </div>
        </a>

        <a href="./usuarios/usuarios.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">👥</div>
                <h3 style="color:#043b3d;">Usuarios</h3>
                <p style="color:#666; font-size:14px;">Administrar clientes y administradores</p>
            </div>
        </a>

        <a href="./proveedores/proveedores.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🏢</div>
                <h3 style="color:#043b3d;">Proveedores</h3>
                <p style="color:#666; font-size:14px;">Gestión de proveedores del club</p>
            </div>
        </a>

        <a href="./reportes/reportes.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">📊</div>
                <h3 style="color:#043b3d;">Reportes</h3>
                <p style="color:#666; font-size:14px;">Ver estadísticas y rendimiento</p>
            </div>
        </a>

        <a href="./ranking/ranking.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🏆</div>
                <h3 style="color:#043b3d;">Ranking</h3>
                <p style="color:#666; font-size:14px;">Ver ranking de jugadores</p>
            </div>
        </a>

        <a href="./pagos/pagos.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">💳</div>
                <h3 style="color:#043b3d;">Pagos</h3>
                <p style="color:#666; font-size:14px;">Ver lista de pagos</p>
            </div>
        </a>
        
        <a href="./reservas/reservas.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🎾</div>
                <h3 style="color:#043b3d;">Reservas</h3>
                <p style="color:#666; font-size:14px;">Ver reservas de canchas</p>
            </div>
        </a>

        <a href="./partidos/partidos.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🏃🏻</div>
                <h3 style="color:#043b3d;">Partidos</h3>
                <p style="color:#666; font-size:14px;">Ver partidos jugados</p>
            </div>
        </a>

        <a href="./torneos/torneos.php" style="text-decoration:none;">
            <div
                style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
                <div style="font-size:40px; margin-bottom:10px;">🏅</div>
                <h3 style="color:#043b3d;">Torneos</h3>
                <p style="color:#666; font-size:14px;">Ver todos los torneos</p>
            </div>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>