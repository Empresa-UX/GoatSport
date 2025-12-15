<?php
/* =========================================================================
 * Form para cargar resultado de un partido (Proveedor)
 * - Valida que el partido pertenezca a un torneo del proveedor
 * - Permite elegir ganador (uno de los dos jugadores) + texto de resultado
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') { header('Location: ../login.php'); exit; }

$proveedor_id = (int)$_SESSION['usuario_id'];
$partido_id = (int)($_GET['partido_id'] ?? 0);
if ($partido_id<=0) { header('Location: torneos.php'); exit; }

$sql = "
  SELECT p.*, t.proveedor_id, t.nombre AS torneo_nombre,
         u1.nombre AS j1_nombre, u2.nombre AS j2_nombre
  FROM partidos p
  INNER JOIN torneos t ON t.torneo_id = p.torneo_id
  LEFT JOIN usuarios u1 ON u1.user_id = p.jugador1_id
  LEFT JOIN usuarios u2 ON u2.user_id = p.jugador2_id
  WHERE p.partido_id = ? AND t.proveedor_id = ?
  LIMIT 1
";
$st = $conn->prepare($sql);
$st->bind_param("ii",$partido_id,$proveedor_id);
$st->execute(); $res=$st->get_result();
$par = $res?$res->fetch_assoc():null; $st->close();

if (!$par) { header('Location: torneos.php'); exit; }
if (!$par['jugador1_id'] || !$par['jugador2_id']) { header("Location: torneoCronograma.php?torneo_id=".(int)$par['torneo_id']); exit; }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<div class="section">
  <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <h2 style="margin:0;">Resultado — <?= h($par['torneo_nombre']) ?></h2>
    <a class="btn-add" href="torneoCronograma.php?torneo_id=<?= (int)$par['torneo_id'] ?>">Volver</a>
  </div>

  <style>
    .box{background:#fff;border-radius:12px;padding:16px;box-shadow:0 4px 12px rgba(0,0,0,.08);max-width:640px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    label{font-size:12px;color:#586168;font-weight:700}
    input,select,textarea{padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none}
    .full{grid-column:1/-1}
    .btn-add{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;text-decoration:none;font-weight:700;font-size:14px;border-radius:10px;border:1px solid #bfd7ff;background:#e0ecff;color:#1e40af;cursor:pointer}
  </style>

  <div class="box">
    <form method="POST" action="partidosAction.php">
      <input type="hidden" name="action" value="save_result">
      <input type="hidden" name="partido_id" value="<?= (int)$partido_id ?>">

      <div class="row">
        <div>
          <label>Jugador 1</label>
          <input type="text" value="<?= h($par['j1_nombre'] ?: ('#'.$par['jugador1_id'])) ?>" disabled>
        </div>
        <div>
          <label>Jugador 2</label>
          <input type="text" value="<?= h($par['j2_nombre'] ?: ('#'.$par['jugador2_id'])) ?>" disabled>
        </div>

        <div class="full">
          <label>Marcador (libre, ej: 6-3 4-6 10-7)</label>
          <input type="text" name="resultado" value="<?= h($par['resultado'] ?? '') ?>" placeholder="Ej: 6-4 7-6">
        </div>

        <div class="full">
          <label>Ganador</label>
          <select name="ganador_id" required>
            <option value="">-- Elegí el ganador --</option>
            <option value="<?= (int)$par['jugador1_id'] ?>"><?= h($par['j1_nombre'] ?: ('#'.$par['jugador1_id'])) ?></option>
            <option value="<?= (int)$par['jugador2_id'] ?>"><?= h($par['j2_nombre'] ?: ('#'.$par['jugador2_id'])) ?></option>
          </select>
        </div>

        <div class="full">
          <button type="submit" class="btn-add">Guardar resultado</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
