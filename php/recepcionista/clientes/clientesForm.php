<?php
/* =====================================================================
 * file: php/recepcionista/clientes/clientesForm.php
 * ===================================================================== */
?>
<style>
  .form-actions{display:flex;gap:10px;align-items:center;margin-top:8px}
  /* Variante outline: conserva el tamaño de .btn-add */
  .btn-add.btn-outline{
    background:#fff !important;          /* por qué: mantener tamaño, cambiar look */
    color:#043b3d !important;
    border:1px solid #ccc !important;
  }
  .btn-add.btn-outline:hover{
    background:#f7f7f7 !important;
  }
  .btn-add, .btn-add.btn-outline { display:inline-block; text-decoration:none; }
</style>

<form method="POST" action="clientesAction.php">
  <label>Nombre:</label>
  <input type="text" name="nombre" placeholder="Nombre y apellido" required>

  <label>Email:</label>
  <input type="email" name="email" placeholder="correo@ejemplo.com" required>

  <label>Contraseña:</label>
  <input type="password" name="password" placeholder="Mínimo 6 caracteres" minlength="6" required>

  <label>Teléfono (opcional):</label>
  <input type="text" name="telefono" placeholder="Ej: 11 5555-5555">

  <div class="form-actions">
    <button type="submit" class="btn-add">Crear cliente</button>
    <a href="/php/recepcionista/home_recepcionista.php" class="btn-add btn-outline">Cancelar</a>
  </div>
</form>
