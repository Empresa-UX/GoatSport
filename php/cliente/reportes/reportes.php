<?php
include './../../config.php';
include './../includes/header.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: /php/login.php"); exit;
}

$userId  = (int)$_SESSION['usuario_id'];
$mensaje = '';

/* ===========================
   TRAER RESERVAS DEL USUARIO
   =========================== */
$sqlRes = "
    SELECT DISTINCT r.reserva_id,
           r.fecha, r.hora_inicio, r.hora_fin,
           c.cancha_id, c.nombre AS cancha_nombre,
           u.nombre AS club_nombre
    FROM reservas r
    JOIN canchas c ON c.cancha_id = r.cancha_id
    JOIN usuarios u ON u.user_id = c.proveedor_id
    LEFT JOIN participaciones p
           ON p.reserva_id = r.reserva_id
          AND p.jugador_id = ?
          AND p.estado = 'aceptada'
    WHERE r.creador_id = ?
       OR p.jugador_id IS NOT NULL
    ORDER BY r.fecha DESC, r.hora_inicio DESC
    LIMIT 20
";
$st = $conn->prepare($sqlRes);
$st->bind_param("ii", $userId, $userId);
$st->execute();
$reservas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

/* ===========================
   TRAER TODAS LAS CANCHAS
   =========================== */
$canchas = $conn->query("
    SELECT c.cancha_id, c.nombre, u.nombre AS club_nombre
    FROM canchas c
    JOIN usuarios u ON u.user_id = c.proveedor_id
")->fetch_all(MYSQLI_ASSOC);

/* ===========================
   GUARDAR REPORTE
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipoFalla = $_POST['tipo_falla'] ?? '';
    $nombre = trim($_POST['nombre_reporte'] ?? '');

    // Descripción según tipo
    if ($tipoFalla === 'sistema') {
        $desc = trim($_POST['descripcion_sistema'] ?? '');
    } else {
        $desc = trim($_POST['descripcion_cancha'] ?? '');
    }

    // Cancha / Reserva (solo si es falla en cancha)
    $canchaId  = ($tipoFalla === 'cancha') ? (int)($_POST['cancha_id'] ?? 0) : null;
    $reservaId = ($tipoFalla === 'cancha') ? (int)($_POST['reserva_id'] ?? 0) : null;

    if ($nombre === '' || $desc === '') {
        $mensaje = "<p class='error'>⚠️ Completa todos los campos requeridos.</p>";
    } else {

        $fecha = date('Y-m-d');
        $estado = 'Pendiente';

        $ins = $conn->prepare("
            INSERT INTO reportes
                (nombre_reporte, descripcion, usuario_id, cancha_id, reserva_id, fecha_reporte, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $canchaField  = $canchaId ?: null;
        $reservaField = $reservaId ?: null;

        $ins->bind_param("ssiiiss", $nombre, $desc, $userId, $canchaField, $reservaField, $fecha, $estado);
        $ok = $ins->execute();
        $ins->close();

        if ($ok) {
            $mensaje = "<p class='success'>✅ Reporte creado con éxito.</p>";
        } else {
            $mensaje = "<p class='error'>⚠️ No se pudo crear el reporte.</p>";
        }
    }
}
?>

<style>
/* ==========================================
   ESTILOS UNIFICADOS Y MÁRGENES CORREGIDOS
   ========================================== */

.torneo-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #043b3d;
    font-size: 15px;
}

/* Bloques (secciones) */
.section-box {
    margin-top: 20px;
    padding: 18px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #dbe4e6;
}

/* SELECT moderno */
.select-wrap {
    width: 100%;
}

.select-wrap select {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #c8d4d6;
    background: #fdfdfd;
    color: #043b3d;
    font-size: 15px;
    appearance: none;
    transition: 0.25s;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23043b3d' height='24' width='24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
}

.select-wrap select:focus {
    outline: none;
    border-color: #1bab9d;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(27,171,157,0.18);
}

/* TEXTAREA moderno */
textarea {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #c8d4d6;
    background: #fdfdfd;
    font-size: 15px;
    color: #043b3d;
    min-height: 120px;
    resize: vertical;
    transition: 0.25s;
}

textarea:focus {
    outline: none;
    border-color: #1bab9d;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(27,171,157,0.18);
}

/* Botón de "Ver historial" RESTAURADO */
.actions-row{
  display:flex;
  gap:10px;
  justify-content:flex-end;
  align-items:center;
  margin-top:20px;
}

.actions-row a.btn-secondary{
  text-decoration:none;
  display:inline-block;
  padding:12px 14px;
  border-radius:10px;
  border:1px solid #1bab9d;
  color:#1bab9d;
  background:#fff;
  font-weight:700;
  font-size:1rem;
  transition:background .2s, transform .1s;
}
.actions-row a.btn-secondary:hover{
  background:rgba(27,171,157,.08);
  transform:translateY(-1px);
}
</style>

<div class="page-wrap">
  <h1 class="page-title">Crear Reporte</h1>

  <?= $mensaje ?>

  <div class="card-white">
    <form method="POST" class="torneo-form">

      <!-- Tipo de falla -->
      <label for="tipo_falla">Tipo de falla</label>
      <div class="select-wrap">
        <select id="tipo_falla" name="tipo_falla" required>
            <option value="">— Seleccionar —</option>
            <option value="sistema">Falla en el sistema</option>
            <option value="cancha">Falla en la cancha</option>
        </select>
      </div>

      <!-- Nombre del reporte -->
      <label for="nombre_reporte" style="margin-top:20px;">Nombre del reporte</label>
      <input id="nombre_reporte" name="nombre_reporte" type="text" placeholder="Ej: Problema con iluminación" required>

      <!-- Falla en sistema -->
      <div id="box_sistema" class="section-box" style="display:none;">
          <label for="descripcion_sistema">Descripción del problema</label>
          <textarea id="descripcion_sistema" name="descripcion_sistema" placeholder="Describe el problema que ocurrió..."></textarea>
      </div>

      <!-- Falla en cancha -->
      <div id="box_cancha" class="section-box" style="display:none;">

          <label for="cancha_id">Selecciona la cancha</label>
          <div class="select-wrap">
              <select id="cancha_id" name="cancha_id">
                  <option value="">— Seleccionar cancha —</option>
                  <?php foreach($canchas as $c): ?>
                    <option value="<?= $c['cancha_id'] ?>">
                        <?= htmlspecialchars($c['club_nombre']." - ".$c['nombre']) ?>
                    </option>
                  <?php endforeach; ?>
              </select>
          </div>

          <label for="reserva_id" style="margin-top:18px;">Vincular a una reserva (opcional)</label>
          <div class="select-wrap">
              <select id="reserva_id" name="reserva_id">
                  <option value="0">— Sin reserva —</option>
                  <?php foreach ($reservas as $r): ?>
                      <?php
                      $label = sprintf(
                          "#%d • %s %s-%s • %s • %s",
                          $r['reserva_id'],
                          $r['fecha'],
                          substr($r['hora_inicio'],0,5),
                          substr($r['hora_fin'],0,5),
                          $r['club_nombre'],
                          $r['cancha_nombre']
                      );
                      ?>
                      <option value="<?= $r['reserva_id'] ?>"><?= $label ?></option>
                  <?php endforeach; ?>
              </select>
          </div>

          <label for="descripcion_cancha" style="margin-top:18px;">Descripción del problema</label>
          <textarea id="descripcion_cancha" name="descripcion_cancha" placeholder="Describe el problema ocurrido en esta cancha..."></textarea>

      </div>

      <div class="actions-row">
        <a class="btn-secondary" href="/php/cliente/reportes/historial_reportes.php">Ver mi historial</a>
        <button type="submit" class="btn-add">Enviar Reporte</button>
      </div>

    </form>
  </div>
</div>

<script>
document.getElementById('tipo_falla').addEventListener('change', function() {
    let tipo = this.value;

    document.getElementById('box_sistema').style.display = (tipo === "sistema") ? "block" : "none";
    document.getElementById('box_cancha').style.display  = (tipo === "cancha")  ? "block" : "none";
});
</script>

<?php include './../includes/footer.php'; ?>
