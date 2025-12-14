<?php
// Conexión
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';

// Validar ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
   die("ID inválido");
}

if (!isset($conn)) {
   die("Error: no se encontró la conexión. Revisa ../../config.php");
}

$res = $conn->query("SELECT * FROM solicitudes_proveedores WHERE id = $id");
$sol = $res ? $res->fetch_assoc() : null;
if (!$sol) {
   die("Solicitud no encontrada");
}
?>

<style>
   .section {
      padding: 25px;
   }

   .section-header h2 {
      color: #043b3d;
      margin-bottom: 20px;
   }

   .card-solicitud {
      background: white;
      border-radius: 14px;
      padding: 25px;
      box-shadow: 0 8px 18px rgba(0, 0, 0, .08);
   }

   .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 25px;
   }

   .info-grid span,
   .descripcion-box span {
      font-size: 13px;
      color: #666;
      font-weight: 600;
      text-transform: uppercase;
   }

   .info-grid p,
   .descripcion-box p {
      margin: 4px 0 0;
      font-size: 15px;
      color: #222;
   }

   .descripcion-box {
      background: #f8fafa;
      padding: 15px 18px;
      border-radius: 10px;
      border-left: 4px solid #0a5557;
      margin-bottom: 30px;
   }

   .acciones {
      display: flex;
      gap: 15px;
   }

   .btn {
      text-decoration: none;
      padding: 12px 22px;
      border-radius: 10px;
      font-weight: bold;
      font-size: 14px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: .2s ease;
   }

   .btn.aprobar {
      background: #1aa37a;
      color: white;
   }

   .btn.aprobar:hover {
      background: #128a66;
   }

   .btn.rechazar {
      background: #e53935;
      color: white;
   }

   .btn.rechazar:hover {
      background: #c62828;
   }

   .estado {
      padding: 12px 22px;
      border-radius: 10px;
      font-weight: bold;
      font-size: 14px;
      display: inline-block;
   }

   .estado.aprobado {
      background: #e8f5e9;
      color: #2e7d32;
   }

   .estado.rechazado {
      background: #fdecea;
      color: #c62828;
   }
</style>

<div class="section">
   <div class="section-header">
      <h2>Solicitud #<?= $sol['id'] ?></h2>
   </div>

   <div class="card-solicitud">

      <div class="info-grid">
         <div>
            <span>Nombre de contacto</span>
            <p><?= htmlspecialchars($sol['nombre_contacto']) ?></p>
         </div>

         <div>
            <span>Email</span>
            <p><?= htmlspecialchars($sol['email']) ?></p>
         </div>

         <div>
            <span>Club</span>
            <p><?= htmlspecialchars($sol['nombre_club']) ?></p>
         </div>

         <div>
            <span>Teléfono</span>
            <p><?= htmlspecialchars($sol['telefono']) ?></p>
         </div>

         <div>
            <span>Dirección</span>
            <p><?= htmlspecialchars($sol['direccion']) ?></p>
         </div>

         <div>
            <span>Ciudad</span>
            <p><?= htmlspecialchars($sol['ciudad']) ?></p>
         </div>
      </div>

      <div class="descripcion-box">
         <span>Descripción</span>
         <p><?= nl2br(htmlspecialchars($sol['descripcion'])) ?></p>
      </div>

      <div class="acciones">
         <?php if ($sol['estado'] === 'pendiente'): ?>

            <a href="action.php?id=<?= $sol['id'] ?>&op=aprobar" class="btn aprobar">
               ✔ Aprobar
            </a>

            <a href="action.php?id=<?= $sol['id'] ?>&op=rechazar" class="btn rechazar">
               ✖ Rechazar
            </a>

         <?php else: ?>

            <span class="estado <?= $sol['estado'] ?>">
               <?= ucfirst($sol['estado']) ?>
            </span>

         <?php endif; ?>
      </div>


   </div>
</div>