<?php
/* =========================================================================
 * file: php/recepcionista/partidos/partidosForm.php  (REEMPLAZO COMPLETO)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);
$partido_id   = (int)($_GET['partido_id'] ?? 0);

if ($partido_id <= 0) { echo "<main><div class='section'><p>Partido inválido.</p></div></main>"; include __DIR__ . '/../includes/footer.php'; exit; }

$sql = "
SELECT
  p.partido_id, p.torneo_id, p.jugador1_id, p.jugador2_id, p.fecha AS p_fecha, p.resultado, p.ganador_id, p.reserva_id,
  t.nombre AS torneo_nombre, t.proveedor_id AS prov_torneo,
  u1.nombre AS jugador1_nombre, u2.nombre AS jugador2_nombre,
  c.nombre  AS cancha_nombre, c.proveedor_id AS prov_cancha,
  r.fecha AS r_fecha, r.hora_inicio AS r_hora_inicio, r.hora_fin AS r_hora_fin, r.tipo_reserva
FROM partidos p
LEFT JOIN torneos t   ON t.torneo_id = p.torneo_id
LEFT JOIN usuarios u1 ON u1.user_id = p.jugador1_id
LEFT JOIN usuarios u2 ON u2.user_id = p.jugador2_id
LEFT JOIN reservas r  ON r.reserva_id = p.reserva_id
LEFT JOIN canchas  c  ON c.cancha_id  = r.cancha_id
WHERE p.partido_id = ? LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $partido_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();
$stmt->close();

$allowed=false;
if ($match) {
  $prov_t=(int)($match['prov_torneo']??0);
  $prov_c=(int)($match['prov_cancha']??0);
  $allowed=($prov_t===$proveedor_id)||($prov_c===$proveedor_id);
}
if (!$match || !$allowed) {
  echo "<main><div class='section'><p>No autorizado o partido inexistente.</p></div></main>";
  include __DIR__ . '/../includes/footer.php'; exit;
}

/* Fechas/horas legibles */
$fechaYmd  = date('Y-m-d', strtotime($match['p_fecha']));
$fechaLeg  = date('d/m/Y', strtotime($match['p_fecha']));
$hora_ini  = !empty($match['r_hora_inicio']) ? substr($match['r_hora_inicio'],0,5) : date('H:i', strtotime($match['p_fecha']));
$hora_fin  = !empty($match['r_hora_fin'])    ? substr($match['r_hora_fin'],0,5)    : null;
$horario   = $hora_fin ? ($hora_ini . ' - ' . $hora_fin) : $hora_ini;

/* Inicio real para bloqueo */
$startStr = (!empty($match['r_fecha']) && !empty($match['r_hora_inicio']))
  ? ($match['r_fecha'].' '.$match['r_hora_inicio'])
  : $match['p_fecha'];
$now   = new DateTimeImmutable('now');
$start = new DateTimeImmutable($startStr);
$lockedByTime = ($now < $start); // why: no cargar antes de que inicie

$j1id=(int)($match['jugador1_id'] ?? 0);
$j2id=(int)($match['jugador2_id'] ?? 0);
$j1n  = htmlspecialchars($match['jugador1_nombre'] ?? 'Jugador 1');
$j2n  = htmlspecialchars($match['jugador2_nombre'] ?? 'Jugador 2');
$gId  = (int)($match['ganador_id'] ?? 0);
$isEquipo = ($match['tipo_reserva'] ?? '') === 'equipo';
$lblJugadores = $isEquipo ? 'Equipos' : 'Jugadores';

/* Bloqueos previos */
if ($j1id<=0 || $j2id<=0) {
  echo "<main><div class='section'><p>Este partido todavía no tiene ambos jugadores definidos (fixture pendiente). Volvé más tarde.</p>
        <p><a href='partidos.php?fecha=".urlencode($fechaYmd)."'>Volver</a></p></div></main>";
  include __DIR__ . '/../includes/footer.php'; exit;
}
if ($lockedByTime) {
  echo "<main><div class='section'><p>Este partido empieza a las <strong>".htmlspecialchars($hora_ini)."</strong>. Aún no podés cargar el resultado.</p>
        <p><a href='partidos.php?fecha=".urlencode($fechaYmd)."'>Volver</a></p></div></main>";
  include __DIR__ . '/../includes/footer.php'; exit;
}
?>
<main>
  <style>
    :root{ --text:#043b3d; --muted:#6b7a80; --border:#d6dadd; --primary:#1bab9d; --primary-600:#159788; --ring:rgba(27,171,157,.12); }
    .gs-card{max-width:460px;margin:24px auto;background:#fff;border-radius:16px;box-shadow:0 10px 28px rgba(0,0,0,.10);padding:22px;border:1px solid #eef2f3}
    .gs-title{font-size:18px;font-weight:750;color:var(--text);text-align:center;margin:6px 0 4px}
    .gs-sub{ text-align:center;color:var(--muted);margin:0 0 16px;font-size:14px}
    .info-grid{display:grid;grid-template-columns:1fr 1fr;grid-template-areas:"partido fecha" "horario torneo" "cancha jugadores";gap:10px 18px;border:1px dashed #e7ecef;border-radius:12px;padding:14px 16px;margin-bottom:16px}
    .info-grid .lbl{font-size:12px;color:var(--muted);font-weight:700;letter-spacing:.2px}
    .info-grid .val{font-size:15px;color:#2f3a3d;font-weight:600}
    .i-partido{grid-area:partido}.i-fecha{grid-area:fecha}.i-horario{grid-area:horario}.i-torneo{grid-area:torneo}.i-cancha{grid-area:cancha}.i-jugadores{grid-area:jugadores}
    label.field{display:block;margin:12px 0 6px;font-weight:700;color:#3a4a50;font-size:14px}
    .gs-input{width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;outline:none;transition:border-color .2s,box-shadow .2s;background:#fff}
    .gs-input:focus{border-color:var(--primary);box-shadow:0 0 0 4px var(--ring)}
    .radio-pills{display:flex;gap:12px;flex-wrap:wrap;margin-top:4px}
    .pill{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;width:48%;border:1px solid #cfd8dc;background:#fff;cursor:pointer;user-select:none;transition:box-shadow .15s,border-color .15s}
    .pill input{accent-color:var(--primary)}
    .pill:hover{border-color:var(--primary);box-shadow:0 0 0 3px var(--ring)}
    .pill.active{border-color:var(--primary);background:#f0fbf9}
    .actions{display:flex;gap:12px;margin-top:16px}
    .btn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:10px;padding:12px 16px;font-weight:700;border:1px solid transparent;cursor:pointer;transition:background-color .15s,box-shadow .15s,transform .02s;font-size:16px;text-decoration:none}
    .btn:active{transform:translateY(1px)}
    .btn-primary{background:var(--primary);color:#fff}
    .btn-primary:hover{background:var(--primary-600);box-shadow:0 10px 20px rgba(27,171,157,.22)}
    .btn-outline{background:transparent;color:var(--primary);border-color:var(--primary)}
    .btn-outline:hover{background:rgba(27,171,157,.08)}
    @media (max-width:560px){.info-grid{grid-template-columns:1fr;grid-template-areas:"partido" "fecha" "horario" "torneo" "cancha" "jugadores"}}
  </style>

  <div class="gs-card" role="region" aria-labelledby="title">
    <h2 id="title" class="gs-title"><?= $gId? 'Editar resultado':'Cargar resultado' ?></h2>
    <p class="gs-sub">Revisa la información y completa el resultado del partido</p>

    <div class="info-grid">
      <div class="i-partido"><div class="lbl">Partido</div><div class="val">#<?= (int)$match['partido_id'] ?></div></div>
      <div class="i-fecha"><div class="lbl">Fecha</div><div class="val"><?= htmlspecialchars($fechaLeg) ?></div></div>
      <div class="i-horario"><div class="lbl">Horario</div><div class="val"><?= htmlspecialchars($horario) ?></div></div>
      <div class="i-torneo"><div class="lbl">Torneo</div><div class="val"><?= htmlspecialchars($match['torneo_nombre'] ?? '-') ?></div></div>
      <div class="i-cancha"><div class="lbl">Cancha</div><div class="val"><?= htmlspecialchars($match['cancha_nombre'] ?? '-') ?></div></div>
      <div class="i-jugadores">
        <div class="lbl"><?= $lblJugadores ?></div>
        <div class="val"><strong><?= $isEquipo?'1':'J1' ?>:</strong> <?= $j1n ?><br><strong><?= $isEquipo?'2':'J2' ?>:</strong> <?= $j2n ?></div>
      </div>
    </div>

    <form method="POST" action="partidosAction.php" id="frmResultado" novalidate>
      <input type="hidden" name="action" value="save_result">
      <input type="hidden" name="partido_id" value="<?= (int)$match['partido_id'] ?>">

      <label class="field" for="resultado">Resultado (sets)</label>
      <input class="gs-input" id="resultado" type="text" name="resultado"
             placeholder="Ej: 6-4 6-3  |  6-7 7-6 10-8"
             value="<?= htmlspecialchars($match['resultado'] ?? '') ?>"
             maxlength="50"
             pattern="^([0-9]{1,2}-[0-9]{1,2})(\\s+[0-9]{1,2}-[0-9]{1,2}){1,4}$"
             aria-describedby="resultadoHint" required>

      <label class="field">Ganador</label>
      <div class="radio-pills" id="winnerPills" role="radiogroup" aria-label="Selecciona ganador">
        <label class="pill <?= $gId===$j1id ? 'active':'' ?>">
          <input type="radio" name="ganador_id" value="<?= $j1id ?>" <?= $gId===$j1id?'checked':''; ?> required> <?= $isEquipo?'E1':'J1' ?>: <?= $j1n ?>
        </label>
        <label class="pill <?= $gId===$j2id ? 'active':'' ?>">
          <input type="radio" name="ganador_id" value="<?= $j2id ?>" <?= $gId===$j2id?'checked':''; ?> required> <?= $isEquipo?'E2':'J2' ?>: <?= $j2n ?>
        </label>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary"><?= $gId? 'Guardar cambios': 'Guardar' ?></button>
        <a href="partidos.php?fecha=<?= urlencode($fechaYmd) ?>" class="btn btn-outline" role="button" aria-label="Cancelar y volver">Cancelar</a>
      </div>
    </form>
  </div>
</main>

<script>
(function(){
  const box=document.getElementById('winnerPills'); if(!box) return;
  box.addEventListener('change',e=>{
    if(e.target.name!=='ganador_id') return;
    box.querySelectorAll('.pill').forEach(p=>p.classList.remove('active'));
    e.target.closest('.pill').classList.add('active');
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
