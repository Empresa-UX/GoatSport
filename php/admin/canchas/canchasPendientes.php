<?php
/* =========================================================================
 * file: admin/canchasPendientes.php
 * Pendientes: botones estilo usuarios.php + apunta a ../canchas/canchasAction.php
 * AHORA: la columna "Ubicación" se arma desde proveedores_detalle (direccion, barrio, ciudad)
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

/* === Datos === */
$sql = "
  SELECT 
    c.cancha_id, c.nombre, c.descripcion, c.tipo, c.capacidad, c.precio,
    c.activa, c.hora_apertura, c.hora_cierre, c.duracion_turno, c.estado,
    u.user_id AS proveedor_id, u.nombre AS proveedor, u.email AS proveedor_email,
    CONCAT_WS(', ', pd.direccion, pd.barrio, pd.ciudad) AS ubicacion
  FROM canchas c
  INNER JOIN usuarios u ON u.user_id = c.proveedor_id
  LEFT JOIN proveedores_detalle pd ON pd.proveedor_id = c.proveedor_id
  WHERE c.estado = 'pendiente'
  ORDER BY c.nombre ASC
";
$res = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

/* === Filtros === */
$tipos=[]; $proveedores=[];
foreach ($rows as $r) {
  if (!empty($r['tipo'])) $tipos[$r['tipo']] = true;
  $proveedores[$r['proveedor_id'].'|'.$r['proveedor']] = true;
}
ksort($tipos, SORT_NATURAL|SORT_FLAG_CASE);
ksort($proveedores, SORT_NATURAL|SORT_FLAG_CASE);

/* === Helpers === */
function horaCorta(?string $t): string { return $t ? substr($t, 0, 5) : '--:--'; }
function capacidadLabel($cap): array {
  $n=(int)$cap; if($n===2)return['Individual','pt']; if($n===4)return['Equipo','pk']; return[$cap!==null?(string)$n:'—','pb'];
}
?>
<div class="section">
  <div class="section-header" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
    <h2 style="margin:0;">Canchas pendientes de aprobación</h2>
    <a href="canchas.php" class="btn-add"><span>Ver canchas aprobadas</span></a>
  </div>

  <style>
    .btn-add{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;text-decoration:none;font-weight:600;font-size:14px;transition:filter .15s ease,transform .03s ease;white-space:nowrap;}
    .btn-add:hover{background:#139488;}

    .fbar{display:grid;grid-template-columns:minmax(280px,1fr) minmax(220px,260px) minmax(200px,220px) minmax(200px,220px) minmax(160px,180px);
      gap:12px;align-items:end;background:#fff;padding:14px 16px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);margin-bottom:12px;}
    @media (max-width:1100px){.fbar{grid-template-columns:repeat(2,minmax(220px,1fr));}}
    @media (max-width:640px){.fbar{grid-template-columns:1fr;}}
    .f{display:flex;flex-direction:column;gap:6px;}
    .f label{font-size:12px;color:#586168;font-weight:700;}
    .f input[type="text"],.f select{padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none;}

    table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border-radius:12px;overflow:hidden;table-layout:fixed;}
    thead th{position:sticky;top:0;background:#f8fafc;z-index:1;text-align:left;font-weight:700;padding:10px 12px;font-size:13px;color:#334155;border-bottom:1px solid #e5e7eb;}
    tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top;}
    tbody tr:hover{background:#f7fbfd;}

    .col-id{width:30px;}
    .col-nombre{width:180px;}
    .col-prov{width:210px;}
    .col-ubic{width:240px;}
    .col-tipo{width:110px;}
    .col-cap{width:80px;}
    .col-precio{width:100px;}
    .col-hor{width:110px;}
    .col-acc{width:100px;}

    .truncate{display:block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .money{white-space:nowrap;}
    .sub{color:#64748b;font-size:12px;}

    .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;white-space:nowrap;border:1px solid transparent}
    .pk{background:#e6f7f4;border-color:#c8efe8;color:#0f766e}
    .pb{background:#fde8e8;border-color:#f8c9c9;color:#7f1d1d}
    .pt{background:#e0ecff;border-color:#bfd7ff;color:#1e40af}

    /* === Botones estilo usuarios.php === */
    .btn-action{appearance:none;border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-weight:700;}
    .btn-action.edit{background:#e0ecff;border:1px solid #bfd7ff;color:#1e40af;}     /* Aprobar */
    .btn-action.delete{background:#fde8e8;border:1px solid #f8c9c9;color:#7f1d1d;}  /* Denegar */
    .action-buttons{display:flex;gap:6px;flex-wrap:wrap;align-items:center;}
    @media (max-width:560px){.action-buttons{flex-direction:column;align-items:stretch;gap:8px;}}
  </style>

  <!-- Filtros -->
  <div class="fbar" id="filters">
    <div class="f"><label>Buscar (nombre o ubicación)</label><input type="text" id="f-q" placeholder="Ej: Parque / Norte / 5/7/11"></div>
    <div class="f">
      <label>Proveedor</label>
      <select id="f-prov">
        <option value="">Todos</option>
        <?php foreach ($proveedores as $key => $_): list($pid,$pname)=explode('|',$key,2); ?>
          <option value="<?= (int)$pid ?>"><?= htmlspecialchars($pname) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
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
      <label>Estado</label>
      <select id="f-estado">
        <option value="">Todas</option>
        <option value="1">Activas</option>
        <option value="0">Inactivas</option>
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
  </div>

  <!-- Tabla pendientes -->
  <table id="tablaPendientes">
    <thead>
      <tr>
        <th class="col-id">ID</th>
        <th class="col-nombre">Nombre</th>
        <th class="col-prov">Proveedor</th>
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
        <tr><td colspan="9" style="text-align:center;">No hay canchas pendientes</td></tr>
      <?php else: foreach ($rows as $c):
        $hora_ap = horaCorta($c['hora_apertura']); $hora_ci = horaCorta($c['hora_cierre']);
        [$capLbl,$capPill] = capacidadLabel($c['capacidad']);
        $ubic = $c['ubicacion'] ?? '';
      ?>
        <tr
          data-nombre="<?= htmlspecialchars(mb_strtolower(($c['nombre'] ?? '').' '.($ubic ?? ''),'UTF-8')) ?>"
          data-proveedor-id="<?= (int)$c['proveedor_id'] ?>"
          data-tipo="<?= htmlspecialchars(mb_strtolower($c['tipo'] ?? '','UTF-8')) ?>"
          data-activa="<?= (int)$c['activa'] ?>"
          data-capacidad="<?= (int)$c['capacidad'] ?>"
        >
          <td class="col-id"><?= (int)$c['cancha_id'] ?></td>
          <td class="col-nombre">
            <div class="truncate"><strong><?= htmlspecialchars($c['nombre']) ?></strong></div>
            <?php if (!empty($c['descripcion'])): ?><div class="sub truncate"><?= htmlspecialchars($c['descripcion']) ?></div><?php endif; ?>
          </td>
          <td class="col-prov">
            <div class="truncate"><strong><?= htmlspecialchars($c['proveedor']) ?></strong></div>
            <div class="sub truncate"><?= htmlspecialchars($c['proveedor_email']) ?></div>
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
            <div class="action-buttons">
              <form method="POST" action="../canchas/canchasAction.php">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="cancha_id" value="<?= (int)$c['cancha_id'] ?>">
                <input type="hidden" name="action" value="aprobar">
                <button type="submit" class="btn-action edit" title="Aprobar">Aprobar</button>
              </form>
              <form method="POST" action="../canchas/canchasAction.php"
                    onsubmit="return confirm('¿Denegar la cancha «<?= htmlspecialchars($c['nombre']) ?>»?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="cancha_id" value="<?= (int)$c['cancha_id'] ?>">
                <input type="hidden" name="action" value="denegar">
                <button type="submit" class="btn-action delete" title="Denegar">Denegar</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
/* Filtros instantáneos (idénticos a aprobadas) */
(function(){
  const $=(s,r=document)=>r.querySelector(s), $$=(s,r=document)=>Array.from(r.querySelectorAll(s));
  const q=$('#f-q'), prov=$('#f-prov'), tipoSel=$('#f-tipo'), actSel=$('#f-estado'), capSel=$('#f-cap');
  const rows=$$('#tablaPendientes tbody tr'); const norm=s=>(s||'').toString().toLowerCase();
  const apply=()=>{const text=norm(q.value),provId=prov.value,tipo=norm(tipoSel.value),actVal=(actSel.value||''),capVal=(capSel.value||'');
    rows.forEach(tr=>{
      const vNombre=tr.dataset.nombre||'', vProvId=tr.dataset.proveedorId||'', vTipo=(tr.dataset.tipo||'').toLowerCase(),
            vAct=tr.dataset.activa==='1', vCap=tr.dataset.capacidad||'';
      const passText=text===''||vNombre.includes(text),
            passProv=provId===''||vProvId===provId,
            passTipo=tipo===''||vTipo===tipo,
            passAct=actVal===''||(actVal==='1'&&vAct)||(actVal==='0'&&!vAct),
            passCap=capVal===''||vCap===capVal;
      tr.style.display=(passText&&passProv&&passTipo&&passAct&&passCap)?'':'none';
    });
  };
  const debounce=(fn,ms=120)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};};
  q.addEventListener('input',debounce(apply,140));
  prov.addEventListener('change',apply); tipoSel.addEventListener('change',apply);
  actSel.addEventListener('change',apply); capSel.addEventListener('change',apply);
  apply();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
