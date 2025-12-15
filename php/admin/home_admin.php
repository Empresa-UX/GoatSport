<?php
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- HOME ADMIN -->
<div class="section-home" style="text-align:center; padding:40px;">
  <h1 style="font-size:30px; color:#043b3d; margin-bottom:15px;">
    👋 ¡Bienvenido de nuevo, Administrador!
  </h1>
  <p style="font-size:18px; color:#555; max-width:750px; margin:0 auto;">
    Gestioná todo el sistema de forma centralizada: <strong>usuarios</strong>, 
    <strong>canchas</strong>, <strong>reservas</strong>, <strong>pagos</strong>,
    <strong>torneos</strong>, <strong>partidos</strong>, <strong>reportes</strong> y 
    <strong>notificaciones</strong>.
  </p>

  <!-- ACCESO RÁPIDO -->
  <div
    style="
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
      gap:20px;
      margin-top:40px;
      max-width:1000px;
      margin-left:auto;
      margin-right:auto;
    ">

    <!-- Usuarios -->
    <a href="./usuarios/usuarios.php" style="text-decoration:none;">
      <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
        <div style="font-size:40px; margin-bottom:10px;">👥</div>
        <h3 style="color:#043b3d;">Usuarios</h3>
        <p style="color:#666; font-size:14px;">Administrar clientes, proveedores y admins</p>
      </div>
    </a>

    <!-- Canchas -->
    <a href="./canchas/canchas.php" style="text-decoration:none;">
      <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
        <div style="font-size:40px; margin-bottom:10px;">🏟️</div>
        <h3 style="color:#043b3d;">Canchas</h3>
        <p style="color:#666; font-size:14px;">Estados, asignaciones y disponibilidad</p>
      </div>
    </a>

    <!-- Reservas -->
    <a href="./reservas/reservas.php" style="text-decoration:none;">
      <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
        <div style="font-size:40px; margin-bottom:10px;">🎾</div>
        <h3 style="color:#043b3d;">Reservas</h3>
        <p style="color:#666; font-size:14px;">Ver y gestionar reservas de las canchas</p>
      </div>
    </a>

    <!-- Pagos -->
    <a href="./pagos/pagos.php" style="text-decoration:none;">
      <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
        <div style="font-size:40px; margin-bottom:10px;">💳</div>
        <h3 style="color:#043b3d;">Pagos</h3>
        <p style="color:#666; font-size:14px;">Listado, estados y conciliación</p>
      </div>
    </a>

    <!-- Torneos -->
    <a href="./torneos/torneos.php" style="text-decoration:none;">
      <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
        <div style="font-size:40px; margin-bottom:10px;">🏅</div>
        <h3 style="color:#043b3d;">Torneos</h3>
        <p style="color:#666; font-size:14px;">Alta, seguimiento y control</p>
      </div>
    </a>

    <!-- Partidos -->
    <a href="./partidos/partidos.php" style="text-decoration:none;">
      <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
        <div style="font-size:40px; margin-bottom:10px;">🏃🏻</div>
        <h3 style="color:#043b3d;">Partidos</h3>
        <p style="color:#666; font-size:14px;">Resultados y auditoría</p>
      </div>
    </a>

    <!-- Reportes -->
    <a href="./reportes/reportes.php" style="text-decoration:none;">
      <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
        <div style="font-size:40px; margin-bottom:10px;">📊</div>
        <h3 style="color:#043b3d;">Reportes</h3>
        <p style="color:#666; font-size:14px;">Incidencias y métricas</p>
      </div>
    </a>

    <!-- Notificaciones -->
    <a href="./notificaciones/notificaciones.php" style="text-decoration:none;">
      <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; transition:0.2s ease; cursor:pointer;">
        <div style="font-size:40px; margin-bottom:10px;">🔔</div>
        <h3 style="color:#043b3d;">Notificaciones</h3>
        <p style="color:#666; font-size:14px;">Avisos del sistema y actividad</p>
      </div>
    </a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
