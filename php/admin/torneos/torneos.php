<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';
$rol = $_SESSION['rol'] ?? null;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ddmm(?string $ymd): string { $t = $ymd? strtotime($ymd):0; return $t? date('d/m',$t):'—'; }
function estadoClase(string $e): string { $e=strtolower($e); return $e==='abierto'?'st-open':($e==='finalizado'?'st-done':'st-closed'); }
function tipoClase(string $t): string { return strtolower($t)==='individual'?'tp-ind':'tp-team'; }

/* DATA */
$sql = "
  SELECT
    t.torneo_id, t.nombre, t.fecha_inicio, t.fecha_fin, t.estado,
    t.tipo, t.capacidad, t.puntos_ganador, t.proveedor_id,
    COALESCE(pd.nombre_club, pu.nombre) AS proveedor_label
  FROM torneos t
  LEFT JOIN usuarios pu ON pu.user_id = t.proveedor_id
  LEFT JOIN proveedores_detalle pd ON pd.proveedor_id = t.proveedor_id
  ORDER BY t.fecha_inicio DESC, t.torneo_id DESC
";
$res  = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$provRes = $conn->query("
  SELECT u.user_id, COALESCE(pd.nombre_club,u.nombre) AS label
  FROM usuarios u
  LEFT JOIN proveedores_detalle pd ON pd.proveedor_id=u.user_id
  WHERE u.rol='proveedor' ORDER BY label ASC
");
$proveedores = $provRes ? $provRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<div class="section">
  <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <h2 style="margin:0;">Torneos</h2>
    <!-- Botón de crear quitado (no usamos torneosForm.php) -->
    <span></span>
  </div>

  <style>
    :root{ --brand:#0f766e; }
    .fbar{ display:grid;gap:12px;align-items:end;background:#fff;padding:14px 16px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);margin:12px 0; }
    .fbar.cols-8{ grid-template-columns: minmax(240px,1fr) minmax(200px,240px) repeat(6, minmax(100px,140px)); }
    @media (max-width:1100px){ .fbar{ grid-template-columns: repeat(2,minmax(220px,1fr)); } }
    @media (max-width:640px){ .fbar{ grid-template-columns: 1fr; } }
    .f{ display:flex;flex-direction:column;gap:6px }
    .f label{ font-size:12px;color:#586168;font-weight:700 }
    .f input[type=text], .f select{ padding:9px 10px;border:1px solid #d6dadd;border-radius:10px;background:#fff;outline:none }

    table{ width:100%;border-collapse:separate;border-spacing:0;background:#fff;border-radius:12px;overflow:hidden;table-layout:fixed }
    thead th{ position:sticky;top:0;background:#f8fafc;z-index:1;text-align:left;font-weight:700;padding:10px 12px;font-size:13px;color:#334155;border-bottom:1px solid #e5e7eb }
    tbody td{ padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top }
    tbody tr:hover{ background:#f7fbfd }
    .truncate{ display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis }

    .col-id{width:42px}.col-nom{width:200px}.col-prov{width:180px}.col-fe{width:70px}
    .col-es{width:90px; text-align: center;}.col-ti{width:90px; text-align: center;}.col-cap{width:90px}.col-pts{width:120px}.col-acc{width:210px; text-align: center;}

    .pill{ display:inline-block;padding:4px 9px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid transparent;white-space:nowrap }
    .st-open{background:#e6f7f4;border-color:#c8efe8;color:#0f766e}
    .st-closed{background:#fff7e6;border-color:#ffe2b8;color:#92400e}
    .st-done{background:#eef2ff;border-color:#c7d2fe;color:#3730a3}
    .tp-team{background:#e0ecff;border-color:#bfd7ff;color:#1e40af}
    .tp-ind{background:#fde8f1;border-color:#f8c7da;color:#a11a5b}

    .btn-action{appearance:none;border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-weight:700}
    .btn-action.part{background:#e6f7f4;border:1px solid #c8efe8;color:#0f766e}
    .btn-action.delete{background:#fde8e8;border:1px solid #f8c9c9;color:#7f1d1d}
    .actions{display:flex;gap:6px;flex-wrap:wrap}
  </style>

  <!-- Filtros -->
  <div class="fbar cols-8" id="filters">
    <div class="f"><label>Por nombre</label><input type="text" id="f-q" placeholder="Ej: Liga Express"></div>
    <div class="f">
      <label>Proveedor (club)</label>
      <select id="f-prov"><option value="">Todos</option>
        <?php foreach($proveedores as $p): ?><option value="<?= (int)$p['user_id']?>"><?=h($p['label'])?></option><?php endforeach;?>
      </select>
    </div>
    <div class="f"><label>Estado</label>
      <select id="f-estado"><option value="">Todos</option><option value="abierto">Abierto</option><option value="cerrado">Cerrado</option><option value="finalizado">Finalizado</option></select>
    </div>
    <div class="f"><label>Tipo</label>
      <select id="f-tipo"><option value="">Todos</option><option value="individual">Individual</option><option value="equipo">Equipo</option></select>
    </div>
    <div class="f"><label>Inicio (Día)</label><select id="f-i-dia"><option value="">Todos</option><?php for($d=1;$d<=31;$d++) echo "<option>$d</option>";?></select></div>
    <div class="f"><label>Inicio (Mes)</label><select id="f-i-mes"><option value="">Todos</option><?php for($m=1;$m<=12;$m++) echo "<option>$m</option>";?></select></div>
    <div class="f"><label>Fin (Día)</label><select id="f-f-dia"><option value="">Todos</option><?php for($d=1;$d<=31;$d++) echo "<option>$d</option>";?></select></div>
    <div class="f"><label>Fin (Mes)</label><select id="f-f-mes"><option value="">Todos</option><?php for($m=1;$m<=12;$m++) echo "<option>$m</option>";?></select></div>
  </div>

  <table id="tablaTorneos">
    <thead>
      <tr>
        <th class="col-id">ID</th><th class="col-nom">Nombre del torneo</th><th class="col-prov">Creador (club)</th>
        <th class="col-fe">Inicio</th><th class="col-fe">Fin</th><th class="col-es">Estado</th>
        <th class="col-ti">Tipo</th><th class="col-cap">Capacidad</th><th class="col-pts">Puntos ganador</th><th class="col-acc">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if(!$rows): ?>
        <tr><td colspan="10" style="text-align:center;">No hay torneos</td></tr>
      <?php else: foreach($rows as $r):
        $ini=ddmm($r['fecha_inicio']); $fin=ddmm($r['fecha_fin']);
        $iDay=$r['fecha_inicio']? (int)date('j',strtotime($r['fecha_inicio'])):''; $iMon=$r['fecha_inicio']? (int)date('n',strtotime($r['fecha_inicio'])):'';
        $fDay=$r['fecha_fin']? (int)date('j',strtotime($r['fecha_fin'])):''; $fMon=$r['fecha_fin']? (int)date('n',strtotime($r['fecha_fin'])):'';
        $estado=strtolower($r['estado']??''); $tipo=strtolower($r['tipo']??'equipo');
        $stCls=estadoClase($estado); $tpCls=tipoClase($tipo);
        $provId=(int)($r['proveedor_id']??0); $provLb=$r['proveedor_label']?:'—';
        $txt=mb_strtolower(($r['nombre']??'').' '.$provLb,'UTF-8');
      ?>
      <tr
        data-text="<?=h($txt)?>" data-prov="<?=$provId?:''?>" data-estado="<?=h($estado)?>" data-tipo="<?=h($tipo)?>"
        data-i-dia="<?=$iDay?>" data-i-mes="<?=$iMon?>" data-f-dia="<?=$fDay?>" data-f-mes="<?=$fMon?>"
      >
        <td class="col-id"><?= (int)$r['torneo_id']?></td>
        <td class="col-nom"><div class="truncate"><strong><?=h($r['nombre'])?></strong></div></td>
        <td class="col-prov"><span class="truncate"><?=h($provLb)?></span></td>
        <td class="col-fe"><?=h($ini)?></td>
        <td class="col-fe"><?=h($fin)?></td>
        <td class="col-es"><span class="pill <?=$stCls?>"><?=ucfirst($estado)?></span></td>
        <td class="col-ti"><span class="pill <?=$tpCls?>"><?=ucfirst($tipo)?></span></td>
        <td class="col-cap"><?= (int)($r['capacidad']??0)?></td>
        <td class="col-pts"><?= (int)($r['puntos_ganador']??0)?></td>
        <td class="col-acc">
          <div class="actions">
            <button class="btn-action part" onclick="location.href='torneoParticipantes.php?torneo_id=<?= (int)$r['torneo_id']?>'">Participantes</button>
            <form method="POST" action="torneosAction.php" onsubmit="return confirm('¿Eliminar torneo?');" style="display:inline-block">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="torneo_id" value="<?= (int)$r['torneo_id']?>">
              <button type="submit" class="btn-action delete">Eliminar</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<script>
(function(){
  const $  = s=>document.querySelector(s);
  const $$ = s=>Array.from(document.querySelectorAll(s));
  const rows = $$('#tablaTorneos tbody tr');
  const norm = s => (s||'').toString().toLowerCase();
  function apply(){
    const q=norm($('#f-q')?.value), prov=$('#f-prov')?.value||'', est=$('#f-estado')?.value||'', tipo=$('#f-tipo')?.value||'';
    const iD=$('#f-i-dia')?.value||'', iM=$('#f-i-mes')?.value||'', fD=$('#f-f-dia')?.value||'', fM=$('#f-f-mes')?.value||'';
    rows.forEach(tr=>{
      const show =
        (q===''|| (tr.dataset.text||'').includes(q)) &&
        (prov===''|| tr.dataset.prov===prov) &&
        (est==='' || tr.dataset.estado===est) &&
        (tipo===''|| tr.dataset.tipo===tipo) &&
        (iD==='' || String(tr.dataset.iDia)===String(iD)) &&
        (iM==='' || String(tr.dataset.iMes)===String(iM)) &&
        (fD==='' || String(tr.dataset.fDia)===String(fD)) &&
        (fM==='' || String(tr.dataset.fMes)===String(fM));
      tr.style.display = show ? '' : 'none';
    });
  }
  ['#f-q','#f-prov','#f-estado','#f-tipo','#f-i-dia','#f-i-mes','#f-f-dia','#f-f-mes'].forEach(id=>{
    const el=document.querySelector(id); if(el) el.addEventListener(id==='#f-q'?'input':'change',apply);
  });
  apply();
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
