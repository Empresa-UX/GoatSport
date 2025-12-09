<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main>
<div class="section-home" style="text-align:center; padding:40px;">
    <h1 style="font-size: 30px; color:#043b3d; margin-bottom: 15px;">
        👋 ¡Bienvenido/a, Recepcionista!
    </h1>
    <p style="font-size: 18px; color:#555; max-width:750px; margin:0 auto;">
        Gestioná clientes de mostrador, reservas, pagos en club, resultados y eventos del club.
    </p>

    <div
      style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-top:32px; max-width:1000px; margin-left:auto; margin-right:auto;">

      <!-- Calendario -->
      <a href="/php/recepcionista/calendario/calendario.php" style="text-decoration:none;">
        <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; cursor:pointer;">
          <div style="font-size:40px; margin-bottom:10px;">📆</div>
          <h3 style="color:#043b3d;">Calendario</h3>
          <p style="color:#666; font-size:14px;">Vista diaria de las reservas</p>
        </div>
      </a>

      <!-- Reservas -->
      <a href="/php/recepcionista/reservas/reservas.php" style="text-decoration:none;">
        <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; cursor:pointer;">
          <div style="font-size:40px; margin-bottom:10px;">🗓️</div>
          <h3 style="color:#043b3d;">Reservas</h3>
          <p style="color:#666; font-size:14px;">Listado y gestión de reservas</p>
        </div>
      </a>

      <!-- Registrar cliente -->
      <a href="/php/recepcionista/clientes/clientes.php" style="text-decoration:none;">
        <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; cursor:pointer;">
          <div style="font-size:40px; margin-bottom:10px;">🧾</div>
          <h3 style="color:#043b3d;">Registrar cliente</h3>
          <p style="color:#666; font-size:14px;">Alta rápida en mostrador</p>
        </div>
      </a>

      <!-- Registrar resultado -->
      <a href="/php/recepcionista/partidos/partidos.php" style="text-decoration:none;">
        <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; cursor:pointer;">
          <div style="font-size:40px; margin-bottom:10px;">🎾</div>
          <h3 style="color:#043b3d;">Registrar resultado</h3>
          <p style="color:#666; font-size:14px;">Alta de resultados</p>
        </div>
      </a>

      <!-- Notificaciones -->
      <a href="/php/recepcionista/notificaciones/notificaciones.php" style="text-decoration:none;">
        <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; cursor:pointer;">
          <div style="font-size:40px; margin-bottom:10px;">🔔</div>
          <h3 style="color:#043b3d;">Notificaciones</h3>
          <p style="color:#666; font-size:14px;">Reservas y pagos en club</p>
        </div>
      </a>

      <!-- Eventos especiales -->
      <a href="/php/recepcionista/eventos/eventos.php" style="text-decoration:none;">
        <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; cursor:pointer;">
          <div style="font-size:40px; margin-bottom:10px;">🎉</div>
          <h3 style="color:#043b3d;">Eventos especiales</h3>
          <p style="color:#666; font-size:14px;">Listado de bloqueos/eventos</p>
        </div>
      </a>

      <!-- Promociones -->
      <a href="/php/recepcionista/promociones/promociones.php" style="text-decoration:none;">
        <div style="background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:25px; cursor:pointer;">
          <div style="font-size:40px; margin-bottom:10px;">🏷️</div>
          <h3 style="color:#043b3d;">Promociones</h3>
          <p style="color:#666; font-size:14px;">Descuentos para reservas</p>
        </div>
      </a>

    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
