<?php
/* =========================================================================
 * file: php/proveedor/canchas/canchas.php
 * Lista de canchas del proveedor con filtros y acciones (editar/eliminar).
 * Aprobadas: Editar + Eliminar. Pendientes: solo Eliminar.
 * Ubicación desde proveedores_detalle.
 * ========================================================================= */
include '../includes/header.php';
include '../includes/sidebar.php';
include './../includes/cards.php';
include '../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../login.php'); exit;
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

$proveedor_id = (int)$_SESSION['usuario_id'];

/* === Datos === */
$sql = "
  SELECT 
    c.cancha_id, c.nombre, c.descripcion, c.tipo, c.capacidad, c.precio,
    c.activa, c.hora_apertura, c.hora_cierre, c.duracion_turno, c.estado,
    CONCAT_WS(', ', pd.direccion, pd.barrio, pd.ciudad) AS ubicacion
  FROM canchas c
  LEFT JOIN proveedores_detalle pd ON pd.proveedor_id = c.proveedor_id
  WHERE c.proveedor_id = ?
  ORDER BY c.nombre ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $proveedor_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

/* === Filtros (catálogos) === */
$tipos=[]; foreach ($rows as $r) { if (!empty($r['tipo'])) $tipos[$r['tipo']] = true; }
ksort($tipos, SORT_NATURAL|SORT_FLAG_CASE);

/* === Helpers === */
function horaCorta(?string $t): string { return $t ? substr($t, 0, 5) : '--:--'; }
function capacidadLabel($cap): array {
  $n=(int)$cap; if($n===2)return['Individual','pt']; if($n===4)return['Equipo','pk']; return[$cap!==null?(string)$n:'—','pb'];
}
?>
<div class="section">
  <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <h2 style="margin:0;">Mis canchas</h2>
    <a href="canchasForm.php" class="btn-add"><span>Alquilar una cancha nueva</span></a>
  </div>

  <style>
    .btn-add{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;text-decoration:none;font-weight:500;font-size:14px;transition:filter .15s ease,transform .03s ease;white-space:nowrap;}
    .btn-add:hover{background:#139488;}

    .fbar{display:grid;grid-template-columns:minmax(280px,1fr) minmax(160px,200px) minmax(160px,200px) minmax(200px,240px);
      gap:12px;align-items:end;background:#fff;padding:14px 16px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);margin-bottom:12px;}
    @media (max-width:960px){.fbar{grid-template-columns:repeat(2,minmax(220px,1fr));}}
    @media (max-width:560px){.fbar{grid-template-columns:1fr;}}
    .f{display:flex;flex-direction:column;gap:6px;}
    .f label{font-size:12px;color:#586168;font-weight:700;}
    .f input[type="text"],.f select{padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;}
    .sub{color:#64748b;font-size:12px;}

    table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border-radius:12px;overflow:hidden;table-layout:fixed;}
    thead th{position:sticky;top:0;background:#f8fafc;z-index:1;text-align:left;font-weight:700;padding:10px 12px;font-size:13px;color:#334155;border-bottom:1px solid #e5e7eb;}
    tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top;}
    tbody tr:hover{background:#f7fbfd;}

    .col-nombre{width:180px;}
    .col-ubic{width:240px;}
    .col-tipo{width:120px;text-align:center;}
    .col-cap{width:90px;text-align:center;}
    .col-precio{width:100px;}
    .col-hor{width:110px;}
    .col-acc{width:170px;text-align:center;}

    .truncate{display:block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .money{white-space:nowrap;}

    .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid transparent;white-space:nowrap;}
    .pk{background:#e6f7f4;border-color:#c8efe8;color:#0f766e}
    .pb{background:#fde8e8;border-color:#f8c9c9;color:#7f1d1d}
    .pt{background:#e0ecff;border-color:#bfd7ff;color:#1e40af}

    .btn-action{appearance:none;border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-weight:700;}
    .btn-action.edit{background:#e0ecff;border:1px solid #bfd7ff;color:#1e40af;}
    .btn-action.delete{background:#fde8e8;border:1px solid #f8c9c9;color:#7f1d1d;}
    .actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center;justify-content:center;}
  </style>

  <!-- Filtros -->
  <div class="fbar" id="filters">
    <div class="f"><label>Buscar (nombre o ubicación)</label><input type="text" id="f-q" placeholder="Ej: Parque / Centro / Avellaneda"></div>
    <div class="f">
      <label>Tipo</label>
      <select id="f-tipo">
        <option value="">Todos</option>
        <?php foreach (array_keys($tipos) as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="f">
      <label>Capacidad</label>
      <select id="f-cap">
        <option value="">Todas</option>
        <option value="2">Individual</option>
        <option value="4">Equipo</option>
      </select>
    </div>
    <div class="f">
      <label>Estado de aprobación</label>
      <select id="f-estado">
        <option value="aprobado" selected>Aprobadas</option>
        <option value="">Todas</option>
        <option value="pendiente">Pendientes</option>
        <option value="denegado">Rechazadas</option>
      </select>
    </div>
  </div>

  <!-- Tabla -->
  <table id="tablaCanchasProv">
    <thead>
      <tr>
        <th class="col-nombre">Nombre</th>
        <th class="col-ubic">Ubicación</th>
        <th class="col-tipo">Tipo</th>
        <th class="col-cap">Capacidad</th>
        <th class="col-precio">Precio p/h</th>
        <th class="col-hor">Horario</th>
        <th class="col-acc">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" style="text-align:center;">No tienes canchas cargadas.</td></tr>
      <?php else: foreach ($rows as $c):
        $hora_ap = horaCorta($c['hora_apertura']); $hora_ci = horaCorta($c['hora_cierre']);
        [$capLbl,$capPill] = capacidadLabel($c['capacidad']);
        $ubic = $c['ubicacion'] ?? '';
      ?>
        <tr
          data-nombre="<?= htmlspecialchars(mb_strtolower(($c['nombre'] ?? '').' '.($ubic ?? ''),'UTF-8')) ?>"
          data-tipo="<?= htmlspecialchars(mb_strtolower($c['tipo'] ?? '','UTF-8')) ?>"
          data-capacidad="<?= (int)$c['capacidad'] ?>"
          data-estado="<?= htmlspecialchars($c['estado'] ?? '') ?>"
        >
          <td class="col-nombre">
            <div class="truncate"><strong><?= htmlspecialchars($c['nombre']) ?></strong></div>
            <?php if (!empty($c['descripcion'])): ?><div class="sub truncate"><?= htmlspecialchars($c['descripcion']) ?></div><?php endif; ?>
          </td>
          <td class="col-ubic"><span class="truncate"><?= htmlspecialchars($ubic ?: '—') ?></span></td>
          <td class="col-tipo">
            <?php if (!empty($c['tipo'])): ?>
              <span class="pill pt truncate" title="<?= htmlspecialchars($c['tipo']) ?>"><?= htmlspecialchars($c['tipo']) ?></span>
            <?php else: ?>
              <span class="sub">—</span>
            <?php endif; ?>
          </td>
          <td class="col-cap"><span class="pill <?= $capPill ?>"><?= htmlspecialchars($capLbl) ?></span></td>
          <td class="col-precio money">$<?= number_format((float)$c['precio'], 2, ',', '.') ?></td>
          <td class="col-hor"><?= $hora_ap ?> - <?= $hora_ci ?><br><span class="sub"><?= (int)$c['duracion_turno'] ?> min</span></td>
          <td class="col-acc">
            <?php
                $estado = $c['estado'] ?? '';

                if ($estado === 'pendiente') {
                $btnText = 'Cancelar cancha';
                $confirmText = 'Cancelar';
                } elseif ($estado === 'denegado') {
                $btnText = 'Eliminar rechazo';
                $confirmText = 'Eliminar el rechazo de';
                } else { // aprobado
                $btnText = 'Eliminar';
                $confirmText = 'Eliminar';
                }
                ?>
            <div class="actions">
              <?php if (($c['estado'] ?? '') === 'aprobado'): ?>
                <button class="btn-action edit" title="Editar"
                  onclick="location.href='canchasForm.php?cancha_id=<?= (int)$c['cancha_id'] ?>'">Editar</button>
              <?php endif; ?>
              <form method="POST" action="canchasAction.php"
                    onsubmit="return confirm('¿Eliminar definitivamente la cancha «<?= htmlspecialchars($c['nombre']) ?>»?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="cancha_id" value="<?= (int)$c['cancha_id'] ?>">
                
                <button type="submit"
                        class="btn-action delete"
                        title="<?= htmlspecialchars($btnText) ?>">
                    <?= htmlspecialchars($btnText) ?>
                </button>

              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
/* Filtros instantáneos (default: estado=aprobado) */
(function(){
  const $=(s,r=document)=>r.querySelector(s), $$=(s,r=document)=>Array.from(r.querySelectorAll(s));
  const q=$('#f-q'), tipoSel=$('#f-tipo'), capSel=$('#f-cap'), estSel=$('#f-estado');
  const rows=$$('#tablaCanchasProv tbody tr'); const norm=s=>(s||'').toString().toLowerCase();
  const apply=()=>{const text=norm(q.value),tipo=norm(tipoSel.value),capVal=(capSel.value||''),estVal=(estSel.value||'');
    rows.forEach(tr=>{
      const vNombre=(tr.dataset.nombre||''), vTipo=(tr.dataset.tipo||''), vCap=(tr.dataset.capacidad||''), vEst=(tr.dataset.estado||'');
      const passText=(text===''||vNombre.includes(text)),
            passTipo=(tipo===''||vTipo===tipo),
            passCap=(capVal===''||vCap===capVal),
            passEst=(estVal===''||vEst===estVal);
      tr.style.display=(passText&&passTipo&&passCap&&passEst)?'':'none';
    });
  };
  const debounce=(fn,ms=120)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};};
  q.addEventListener('input',debounce(apply,140));
  tipoSel.addEventListener('change',apply); capSel.addEventListener('change',apply); estSel.addEventListener('change',apply);
  apply();
})();
</script>

<?php include '../includes/footer.php'; ?>
