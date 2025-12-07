<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\reportes\reportes.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: /php/login.php"); exit;
}

$userId  = (int)$_SESSION['usuario_id'];
$mensaje = '';

/* Últimas reservas del usuario (creador o participante aceptado) para el select */
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

/* Crear reporte */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_reporte'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    $sel    = isset($_POST['reserva_id']) ? (int)$_POST['reserva_id'] : 0;

    if ($nombre === '' || $desc === '') {
        $mensaje = "<p class='error'>⚠️ Completa nombre y descripción.</p>";
    } else {
        $fecha       = date('Y-m-d');
        $estado      = 'Pendiente';
        $canchaId    = null;
        $proveedorId = null;

        if ($sel > 0) {
            // Valida pertenencia de la reserva
            $chk = $conn->prepare("
                SELECT r.cancha_id, c.proveedor_id
                FROM reservas r
                JOIN canchas c ON c.cancha_id = r.cancha_id
                LEFT JOIN participaciones p
                       ON p.reserva_id = r.reserva_id
                      AND p.jugador_id = ?
                WHERE r.reserva_id = ?
                  AND (r.creador_id = ? OR (p.jugador_id IS NOT NULL AND p.estado IN ('aceptada','pendiente')))
                LIMIT 1
            ");
            $chk->bind_param("iii", $userId, $sel, $userId);
            $chk->execute();
            $own = $chk->get_result()->fetch_assoc();
            $chk->close();

            if (!$own) {
                $mensaje = "<p class='error'>⚠️ Esa reserva no te pertenece.</p>";
            } else {
                $canchaId    = (int)$own['cancha_id'];
                $proveedorId = (int)$own['proveedor_id'];
            }
        }

        if ($mensaje === '') {
            $ins = $conn->prepare("
                INSERT INTO reportes
                    (nombre_reporte, descripcion, usuario_id, cancha_id, reserva_id, fecha_reporte, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $reservaId = ($sel > 0) ? $sel : null;
            $ins->bind_param("ssiiiss", $nombre, $desc, $userId, $canchaId, $reservaId, $fecha, $estado);
            $ok = $ins->execute();
            $newId = $ins->insert_id ?? 0;
            $ins->close();

            if ($ok) {
                // Notificación al cliente
                $tituloC  = "Reporte creado (#$newId)";
                $mensajeC = "Tu reporte \"".$nombre."\" fue registrado y está en estado Pendiente.";
                $n = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'reporte', ?, ?)");
                $n->bind_param("iss", $userId, $tituloC, $mensajeC);
                $n->execute(); $n->close();

                // Notificación a proveedor o admins
                if (!empty($proveedorId)) {
                    $u = $conn->prepare("SELECT nombre FROM usuarios WHERE user_id=? LIMIT 1");
                    $u->bind_param("i", $userId);
                    $u->execute();
                    $me = $u->get_result()->fetch_assoc();
                    $u->close();

                    $tituloP  = "Nuevo reporte (#$newId)";
                    $mensajeP = ($me ? $me['nombre'] : 'Un jugador') . " creó un reporte: \"".$nombre."\".";
                    $np = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'reporte', ?, ?)");
                    $np->bind_param("iss", $proveedorId, $tituloP, $mensajeP);
                    $np->execute(); $np->close();
                } else {
                    $admins = $conn->query("SELECT user_id FROM usuarios WHERE rol='admin'");
                    while ($a = $admins->fetch_assoc()) {
                        $admId = (int)$a['user_id'];
                        $tituloA = "Nuevo reporte (#$newId)";
                        $mensajeA = "Se creó el reporte \"".$nombre."\".";
                        $na = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'reporte', ?, ?)");
                        $na->bind_param("iss", $admId, $tituloA, $mensajeA);
                        $na->execute(); $na->close();
                    }
                }

                $mensaje = "<p class='success'>✅ Reporte creado con éxito.</p>";
            } else {
                $mensaje = "<p class='error'>⚠️ Error al crear el reporte.</p>";
            }
        }
    }
}
?>
<style>
/* ====== Mejora del SELECT (coherente con tu look) ====== */
.select-wrap{ position:relative; }
.torneo-form select{
  -webkit-appearance:none; -moz-appearance:none; appearance:none;
  width:100%;
  padding:12px 42px 12px 14px;
  border:1px solid #ccc;
  border-radius:10px;
  background: rgba(255,255,255,0.9) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\"><path fill=\"%23666\" d=\"M7 10l5 5 5-5z\"/></svg>') no-repeat right 12px center;
  font-size:1rem;
  color:#043b3d;
  transition:border-color .2s, box-shadow .2s, background-color .2s;
}
.torneo-form select:focus{
  outline:none;
  border-color:#1bab9d;
  box-shadow:0 0 0 3px rgba(27,171,157,0.2);
  background-color:#fff;
}
.torneo-form select option{ color:#000; }

/* Fila de acciones (historial + submit) */
.actions-row{display:flex;gap:10px;justify-content:flex-end;align-items:center;margin-top:10px}
.actions-row a.btn-secondary{
  text-decoration:none;display:inline-block;padding:12px 14px;border-radius:10px;
  border:1px solid #1bab9d;color:#1bab9d;background:#fff;font-weight:700;font-size:1rem;
  transition:background .2s, transform .1s;
}
.actions-row a.btn-secondary:hover{ background:rgba(27,171,157,.08); transform:translateY(-1px); }
/* Mantener el look del submit existente (.btn-add) */
</style>

<div class="page-wrap">
  <h1 class="page-title">Crear Reporte</h1>

  <?= $mensaje ?>

  <div class="card-white">
    <form method="POST" class="torneo-form">
      <label for="nombre_reporte">Nombre del reporte</label>
      <input id="nombre_reporte" name="nombre_reporte" type="text" placeholder="Ej: Falla en la iluminación" required>

      <label for="reserva_id">Vincular a una reserva (opcional)</label>
      <div class="select-wrap">
        <select id="reserva_id" name="reserva_id">
          <option value="0">— Sin reserva —</option>
          <?php foreach ($reservas as $r): ?>
            <?php
              $label = sprintf("#%d • %s %s-%s • %s • %s",
                (int)$r['reserva_id'],
                htmlspecialchars($r['fecha']),
                htmlspecialchars(substr($r['hora_inicio'],0,5)),
                htmlspecialchars(substr($r['hora_fin'],0,5)),
                htmlspecialchars($r['club_nombre']),
                htmlspecialchars($r['cancha_nombre'])
              );
            ?>
            <option value="<?= (int)$r['reserva_id'] ?>"><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <label for="descripcion">Descripción</label>
      <textarea id="descripcion" name="descripcion" rows="6" placeholder="Describe el problema o sugerencia con detalle..." required></textarea>

      <p class="info">Sugerencia: si no asocias una reserva, indica club, cancha y fecha en la descripción.</p>

      <div class="actions-row">
        <a class="btn-secondary" href="/php/cliente/reportes/historial_reportes.php">Ver mi historial</a>
        <button type="submit" class="btn-add">Enviar Reporte</button>
      </div>
    </form>
  </div>
</div>

<?php include './../includes/footer.php'; ?>
