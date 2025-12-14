<?php
/* =========================================================================
 * file: php/recepcionista/partidos/partidos.php  (REEMPLAZA COMPLETO)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';

$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);

// Filtros
$fecha        = $_GET['fecha']        ?? date('Y-m-d');
$estado       = $_GET['estado']       ?? 'pendientes';
$cancha_f     = isset($_GET['cancha_id']) ? (int)$_GET['cancha_id'] : 0;
$tipo_f       = $_GET['tipo_reserva'] ?? '';
$qJugador     = trim($_GET['q'] ?? '');

// Canchas (solo activas del proveedor)
$canchas = [];
$stc = $conn->prepare("SELECT cancha_id, nombre FROM canchas WHERE proveedor_id=? AND activa=1 ORDER BY nombre");
$stc->bind_param("i", $proveedor_id);
$stc->execute();
$resc = $stc->get_result();
while($r = $resc->fetch_assoc()) $canchas[] = $r;
$stc->close();

// Consulta
$sql = "
SELECT
  p.partido_id, p.torneo_id, p.jugador1_id, p.jugador2_id, p.fecha, p.resultado, p.ganador_id, p.reserva_id,
  t.proveedor_id AS prov_torneo,
  u1.nombre AS jugador1_nombre, u2.nombre AS jugador2_nombre,
  c.nombre  AS cancha_nombre, c.proveedor_id AS prov_cancha,
  r.hora_inicio, r.hora_fin, r.cancha_id AS r_cancha_id, r.tipo_reserva
FROM partidos p
LEFT JOIN torneos t   ON t.torneo_id = p.torneo_id
LEFT JOIN usuarios u1 ON u1.user_id = p.jugador1_id
LEFT JOIN usuarios u2 ON u2.user_id = p.jugador2_id
LEFT JOIN reservas r  ON r.reserva_id = p.reserva_id
LEFT JOIN canchas  c  ON c.cancha_id  = r.cancha_id
WHERE DATE(p.fecha) = ?
  AND ( (t.proveedor_id IS NOT NULL AND t.proveedor_id = ?) OR (c.proveedor_id IS NOT NULL AND c.proveedor_id = ?) )
";
$params = [$fecha, $proveedor_id, $proveedor_id];
$types  = "sii";

if ($estado === 'pendientes') { $sql .= " AND (p.resultado IS NULL OR p.ganador_id IS NULL) "; }
if ($cancha_f > 0) { $sql .= " AND r.cancha_id = ? "; $params[]=$cancha_f; $types.="i"; }
if ($tipo_f === 'individual' || $tipo_f === 'equipo') { $sql .= " AND r.tipo_reserva = ? "; $params[]=$tipo_f; $types.="s"; }
if ($qJugador !== '') {
  $like = '%'.$qJugador.'%';
  $sql .= " AND (u1.nombre LIKE ? OR u2.nombre LIKE ?) ";
  $params[]=$like; $params[]=$like; $types.="ss";
}
$sql .= " ORDER BY COALESCE(r.hora_inicio, TIME(p.fecha)) ASC, p.partido_id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$partidos = $stmt->get_result();
$stmt->close();
?>
<main>
  <?php if (isset($_GET['ok'])): ?>
    <script> alert('<?= addslashes($_GET["ok"]) ?>'); if(history.replaceState){const u=new URL(location.href);u.search='';history.replaceState(null,'',u.toString());} </script>
  <?php elseif (isset($_GET['err'])): ?>
    <script> alert('<?= addslashes($_GET["err"]) ?>'); if(history.replaceState){const u=new URL(location.href);u.search='';history.replaceState(null,'',u.toString());} </script>
  <?php endif; ?>

  <style>
    :root{
      /* ======= Anchos manipulables ======= */
      --col-id:     30px;
      --col-hora:   110px;
      --col-cancha: 180px;
      --col-j1:     140px;
      --col-j2:     140px;
      --col-res:    80px;
      --col-gana:   140px;
      --col-estado: 90px;
      --col-acc:    220px;
    }

    .filterbar{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin:14px 0 16px}
    .f-field{display:flex;flex-direction:column;gap:6px;min-width:200px}
    .f-field.tiny{min-width:180px}
    .f-label{font-size:12px;color:#586168;font-weight:600;letter-spacing:.3px}
    .f-input,.f-select,.f-date{width:100%;padding:10px 12px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;box-shadow:0 1px 0 rgba(0,0,0,.03)}
    .f-input:focus,.f-select:focus,.f-date:focus{border-color:#1bab9d;box-shadow:0 0 0 3px rgba(27,171,157,.12)}

    table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border-radius:12px;overflow:hidden;table-layout:fixed}
    thead th{position:sticky;top:0;background:#f8fafc;z-index:1;text-align:left;font-weight:700;padding:10px 12px;font-size:13px;color:#334155;border-bottom:1px solid #e5e7eb;}
    tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top;}

    th.col-id,      td.col-id      { width:var(--col-id); }
    th.col-hora,    td.col-hora    { width:var(--col-hora); }
    th.col-cancha,  td.col-cancha  { width:var(--col-cancha); }
    th.col-j1,      td.col-j1      { width:var(--col-j1); }
    th.col-j2,      td.col-j2      { width:var(--col-j2); }
    th.col-res,     td.col-res     { width:var(--col-res); }
    th.col-gana,    td.col-gana    { width:var(--col-gana); }
    th.col-estado,  td.col-estado  { width:var(--col-estado); }
    th.col-acc,     td.col-acc     { width:var(--col-acc); }

    .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;white-space:nowrap}
    .pill-ok{background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9}
    .pill-pend{background:#ffebee;color:#c62828;border:1px solid #ffcdd2}

    /* Botones azulsito/rojito */
    .action-buttons{display:flex;gap:6px;flex-wrap:wrap;align-items:center;}
    .btn-action{appearance:none;border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-weight:500;text-decoration:none;font-size:13px;display:inline-flex;align-items:center;justify-content:center}
    .btn-action.edit{background:#e0ecff;border:1px solid #bfd7ff;color:#1e40af;}     /* Azul: Cargar/Editar */
    .btn-action.delete-hard{background:#fee2e2;border:1px solid #fca5a5;color:#7f1d1d;} /* Rojo fuerte: Eliminar partido */

    .truncate{display:block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  </style>

  <div class="section">
    <div class="section-header"><h2>Partidos amistosos</h2></div>

    <form method="GET" class="filterbar" id="filtersForm">
      <div class="f-field"><label class="f-label">Fecha</label><input class="f-date" type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>"></div>
      <div class="f-field">
        <label class="f-label">Estado</label>
        <select class="f-select" name="estado">
          <option value="pendientes" <?= $estado==='pendientes'?'selected':'' ?>>Pendientes</option>
          <option value="todos"      <?= $estado==='todos'?'selected':'' ?>>Todos</option>
        </select>
      </div>
      <div class="f-field">
        <label class="f-label">Cancha</label>
        <select class="f-select" name="cancha_id">
          <option value="0">Todas</option>
          <?php foreach ($canchas as $c): ?>
            <option value="<?= (int)$c['cancha_id'] ?>" <?= $cancha_f===(int)$c['cancha_id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="f-field">
        <label class="f-label">Tipo</label>
        <select class="f-select" name="tipo_reserva">
          <option value="" <?= $tipo_f===''?'selected':'' ?>>Todos</option>
          <option value="individual" <?= $tipo_f==='individual'?'selected':'' ?>>Individual</option>
          <option value="equipo"     <?= $tipo_f==='equipo'?'selected':'' ?>>Equipo</option>
        </select>
      </div>
      <div class="f-field tiny">
        <label class="f-label">Jugador (buscar)</label>
        <input class="f-input" type="text" name="q" value="<?= htmlspecialchars($qJugador) ?>" placeholder="Nombre jugador">
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th class="col-id">ID</th>
          <th class="col-hora">Hora</th>
          <th class="col-cancha">Cancha</th>
          <th class="col-j1">Jugador 1</th>
          <th class="col-j2">Jugador 2</th>
          <th class="col-res">Resultado</th>
          <th class="col-gana">Ganador</th>
          <th class="col-estado">Estado</th>
          <th class="col-acc">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($partidos->num_rows): while($p=$partidos->fetch_assoc()):
        $hora_ini = !empty($p['hora_inicio']) ? substr($p['hora_inicio'],0,5) : date('H:i', strtotime($p['fecha']));
        $hora_fin = !empty($p['hora_fin'])    ? substr($p['hora_fin'],0,5)    : null;
        $hora     = $hora_fin ? ($hora_ini.' - '.$hora_fin) : $hora_ini;

        $ganadorTxt='-';
        if (!empty($p['ganador_id'])) {
          $ganadorTxt = ((int)$p['ganador_id']===(int)$p['jugador1_id']) ? ($p['jugador1_nombre'] ?: 'J1')
                      : (((int)$p['ganador_id']===(int)$p['jugador2_id']) ? ($p['jugador2_nombre'] ?: 'J2') : ('ID '.$p['ganador_id']));
        }
        $cargado = (!empty($p['resultado']) && !empty($p['ganador_id']));
        $estadoPill = $cargado ? '<span class="pill pill-ok">Cargado</span>' : '<span class="pill pill-pend">Pendiente</span>';
        $fechaYmd = date('Y-m-d', strtotime($p['fecha']));
      ?>
        <tr>
          <td class="col-id"><?= (int)$p['partido_id'] ?></td>
          <td class="col-hora"><?= htmlspecialchars($hora) ?></td>
          <td class="col-cancha"><span class="truncate"><?= htmlspecialchars($p['cancha_nombre'] ?? '-') ?></span></td>
          <td class="col-j1"><span class="truncate"><?= htmlspecialchars($p['jugador1_nombre'] ?? ('#'.$p['jugador1_id'])) ?></span></td>
          <td class="col-j2"><span class="truncate"><?= htmlspecialchars($p['jugador2_nombre'] ?? ('#'.$p['jugador2_id'])) ?></span></td>
          <td class="col-res"><?= htmlspecialchars($p['resultado'] ?? '-') ?></td>
          <td class="col-gana"><span class="truncate"><?= htmlspecialchars($ganadorTxt) ?></span></td>
          <td class="col-estado"><?= $estadoPill ?></td>
          <td class="col-acc">
            <div class="action-buttons">
              <?php if (!$cargado): ?>
                <a class="btn-action edit" href="partidosForm.php?partido_id=<?= (int)$p['partido_id'] ?>">Cargar resultado</a>
                <!-- Eliminar PARTIDO -->
                <form method="POST" action="partidosAction.php"
                      onsubmit="return confirm('¿Eliminar el partido #<?= (int)$p['partido_id'] ?>? Se quitará de la lista.');" style="display:inline-block">
                  <input type="hidden" name="action" value="delete_match">
                  <input type="hidden" name="partido_id" value="<?= (int)$p['partido_id'] ?>">
                  <button type="submit" class="btn-action delete-hard">Eliminar</button>
                </form>
              <?php else: ?>
                <a class="btn-action edit" href="partidosForm.php?partido_id=<?= (int)$p['partido_id'] ?>">Editar</a>
                <!-- Eliminar PARTIDO -->
                <form method="POST" action="partidosAction.php"
                      onsubmit="return confirm('¿Eliminar el partido #<?= (int)$p['partido_id'] ?> por completo?');" style="display:inline-block">
                  <input type="hidden" name="action" value="delete_match">
                  <input type="hidden" name="partido_id" value="<?= (int)$p['partido_id'] ?>">
                  <button type="submit" class="btn-action delete-hard">Eliminar</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="9" style="text-align:center;">Sin partidos para los filtros.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
(function(){
  const form=document.getElementById('filtersForm'); if(!form) return;
  const submit=()=>{ if(form.requestSubmit) form.requestSubmit(); else form.submit(); };
  form.querySelectorAll('select').forEach(el=>el.addEventListener('change',submit));
  const date=form.querySelector('input[type="date"]');
  if(date){ date.addEventListener('change',submit); date.addEventListener('input',()=>{ if(date.value) submit(); }); }
  const q=form.querySelector('input[name="q"]');
  if(q){ q.addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); submit(); } }); }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
