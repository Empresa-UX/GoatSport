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
    ORDER BY u.nombre ASC, c.nombre ASC
")->fetch_all(MYSQLI_ASSOC);

/* ===========================
   GUARDAR REPORTE
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipoFalla = $_POST['tipo_falla'] ?? '';                        // 'sistema' | 'cancha'
    $nombre    = trim($_POST['nombre_reporte'] ?? '');
    $desc      = trim($_POST['descripcion'] ?? '');

    // Cancha / Reserva (solo si es falla en cancha)
    $canchaId  = ($tipoFalla === 'cancha') ? (int)($_POST['cancha_id'] ?? 0) : 0;
    $reservaId = ($tipoFalla === 'cancha') ? (int)($_POST['reserva_id'] ?? 0) : 0;

    if ($nombre === '' || $desc === '' || ($tipoFalla !== 'sistema' && $tipoFalla !== 'cancha')) {
        $mensaje = "<p class='error'>⚠️ Completa todos los campos requeridos.</p>";
    } else {
        $fecha = date('Y-m-d');
        $estado = 'Pendiente';

        // Normalizar nulos
        $canchaField  = ($tipoFalla === 'cancha' && $canchaId > 0) ? $canchaId : null;
        $reservaField = ($tipoFalla === 'cancha' && $reservaId > 0) ? $reservaId : null;

        $ins = $conn->prepare("
            INSERT INTO reportes
                (nombre_reporte, descripcion, usuario_id, cancha_id, reserva_id, fecha_reporte, estado, tipo_falla)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        // tipos: s s i i i s s s
        $ins->bind_param("ssiissss", $nombre, $desc, $userId, $canchaField, $reservaField, $fecha, $estado, $tipoFalla);
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
/* ===== UI consistente con Reservas ===== */
.page-wrap{ padding:24px 16px 40px; }
.card-white{ max-width:960px; margin:0 auto 24px auto; }
h1.page-title{ text-align:center; }

/* Etiquetas */
.form-label{ display:block; margin:0 0 6px; font-weight:600; color:#043b3d; font-size:15px; }

/* Inputs base */
input[type="text"], textarea, select{
  width:100%; padding:12px 14px; border-radius:10px; border:1px solid #c8d4d6; background:#fdfdfd;
  font-size:15px; color:#043b3d; transition:.25s;
}
input[type="text"]:focus, textarea:focus, select:focus{
  outline:none; border-color:#1bab9d; background:#fff; box-shadow:0 0 0 3px rgba(27,171,157,.18);
}
textarea{ min-height:140px; resize:vertical; }

/* Grid simple */
.form-grid{ display:grid; grid-template-columns:1fr; gap:16px; }

/* Pills de razón (checkbox-look pero exclusivas) */
.reason-wrap{ display:flex; gap:8px; flex-wrap:wrap; }
.reason-pill{
  display:inline-flex; align-items:center; gap:8px; padding:10px 12px; border-radius:999px;
  border:1.5px solid #c8d4d6; background:#fff; color:#043b3d; cursor:pointer; font-weight:700;
  user-select:none;
}
.reason-pill input{ display:none; }
.reason-pill.active{ border-color:#1bab9d; background:rgba(27,171,157,.08); }

/* Campos dependientes */
.dependent .hint{ font-size:12px; color:#5a6b6c; margin-top:4px; }
.dependent.disabled{ opacity:.6; }
.dependent.disabled select{ pointer-events:none; background:#f5f7f7; }

/* Acciones */
.actions-row{
  display:flex; gap:10px; justify-content:flex-end; align-items:center; margin-top:8px;
}
.actions-row a.btn-secondary{
  text-decoration:none; display:inline-block; padding:12px 14px; border-radius:10px;
  border:1.5px solid #1bab9d; color:#1bab9d; background:#fff; font-weight:700; font-size:1rem;
  transition:background .2s, transform .1s;
}
.actions-row a.btn-secondary:hover{ background:rgba(27,171,157,.08); transform:translateY(-1px); }
.btn-add{ display:inline-block; padding:12px 14px; border-radius:10px; background:#07566b; color:#fff; font-weight:700; border:none; cursor:pointer; }

/* Mensajes */
.success{ color:#0c7a5a; font-weight:700; }
.error{ color:#a61b1b; font-weight:700; }
</style>

<div class="page-wrap">
  <h1 class="page-title">Crear reporte</h1>

  <?= $mensaje ?>

  <div class="card-white">
    <form method="POST" class="form-grid" id="reporteForm" novalidate>
      <!-- Título -->
      <div>
        <label class="form-label" for="nombre_reporte">Título del reporte</label>
        <input id="nombre_reporte" name="nombre_reporte" type="text" placeholder="Ej: Problema con iluminación" required>
      </div>

      <!-- Razón -->
      <div>
        <span class="form-label">Razón del reporte</span>
        <div class="reason-wrap" id="reasonWrap">
          <label class="reason-pill" data-value="sistema">
            <input type="checkbox" id="r_sistema"><span>Sistema</span>
          </label>
          <label class="reason-pill" data-value="cancha">
            <input type="checkbox" id="r_cancha"><span>Cancha</span>
          </label>
        </div>
        <input type="hidden" name="tipo_falla" id="tipo_falla" value="">
      </div>

      <!-- Cancha afectada (si es “cancha”) -->
      <div class="dependent" id="blockCancha">
        <label class="form-label" for="cancha_id">Cancha afectada</label>
        <select id="cancha_id" name="cancha_id">
          <option value="">— Seleccionar cancha —</option>
          <?php foreach($canchas as $c): ?>
            <option value="<?= (int)$c['cancha_id'] ?>">
              <?= htmlspecialchars($c['club_nombre']." - ".$c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="hint">Si el reporte es de “Sistema”, este campo queda deshabilitado.</div>
      </div>

      <!-- Reserva (opcional, solo “cancha”) -->
      <div class="dependent" id="blockReserva">
        <label class="form-label" for="reserva_id">Vincular a una reserva (opcional)</label>
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
            <option value="<?= (int)$r['reserva_id'] ?>"><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Descripción -->
      <div>
        <label class="form-label" for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" placeholder="Describe el problema con el máximo de contexto posible..." required></textarea>
      </div>

      <!-- Acciones -->
      <div class="actions-row">
        <a class="btn-secondary" href="/php/cliente/reportes/historial_reportes.php">Ver mi historial</a>
        <button type="submit" class="btn-add">Enviar reporte</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const wrap = document.getElementById('reasonWrap');
  const pills = wrap.querySelectorAll('.reason-pill');
  const hidden = document.getElementById('tipo_falla');
  const blockCancha = document.getElementById('blockCancha');
  const blockReserva = document.getElementById('blockReserva');
  const selCancha = document.getElementById('cancha_id');
  const selReserva = document.getElementById('reserva_id');

  function updateUI(value){
    // marcar visualmente
    pills.forEach(p => p.classList.toggle('active', p.dataset.value === value));

    // habilitar/deshabilitar dependientes
    const isSistema = (value === 'sistema');
    [selCancha, selReserva].forEach(el => {
      el.disabled = isSistema;
      if (isSistema) el.value = (el === selReserva ? '0' : '');
    });
    blockCancha.classList.toggle('disabled', isSistema);
    blockReserva.classList.toggle('disabled', isSistema);
  }

  pills.forEach(p => {
    p.addEventListener('click', function(e){
      e.preventDefault();
      const v = this.dataset.value;
      // Exclusivo: si ya está elegido, no lo des-seleccionamos para forzar una única opción
      hidden.value = v;
      updateUI(v);
    });
  });

  // Estado inicial: pedir uno (si vienes de validación server)
  if (!hidden.value) {
    hidden.value = 'sistema'; // default
  }
  updateUI(hidden.value);
})();
</script>

<?php include './../includes/footer.php'; ?>
