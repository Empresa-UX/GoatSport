<aside class="sidebar">
  <style>
    /* Mantener el sidebar visible al hacer scroll, sin cambiar estilos de color/tema */
    .sidebar { position: sticky; top: 0; max-height: 100vh; overflow-y: auto; }
  </style>

  <!-- Logo → home recepcionista -->
  <a href="/php/recepcionista/home_recepcionista.php" title="Ir al inicio">
    <img src="/img/logotipo.png" alt="Logo Padel">
  </a>

  <a href="/php/recepcionista/calendario/calendario.php">Calendario</a>
  <a href="/php/recepcionista/reservas/reservas.php">Reservas</a>
  <a href="/php/recepcionista/clientes/clientes.php">Registrar cliente</a>
  <a href="/php/recepcionista/partidos/partidos.php">Registrar resultado</a>
  <a href="/php/recepcionista/notificaciones/notificaciones.php">Notificaciones</a>
  <a href="/php/recepcionista/eventos/eventos.php">Eventos especiales</a>
  <a href="/php/recepcionista/promociones/promociones.php">Promociones</a>

  <a href="/php/logout.php">Cerrar sesión</a>
</aside>

<div class="main">
  <div class="main-inner">
    <div class="header">
      <div class="user">
        <a href="/php/recepcionista/configuracion/configuracion.php" 
           style="color: inherit; text-decoration: none;">
          👤 Recepcionista
        </a>
      </div>
    </div>
