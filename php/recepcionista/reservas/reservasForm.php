<?php
// =====================================================================
// file: php/recepcionista/reservas/reservasForm.php  (REEMPLAZAR COMPLETO)
// =====================================================================
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$proveedor_id = (int) ($_SESSION['proveedor_id'] ?? 0);

// Helpers de sanitización HH:MM y suma de minutos
function hhmm($v){
  $v = trim((string)($v ?? ''));
  if ($v === '') return '';
  if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $v)) return substr($v,0,5);
  return '';
}
function add_minutes_to_hhmm($hhmm, $mins){
  if ($hhmm === '' || !preg_match('/^\d{2}:\d{2}$/',$hhmm)) return '';
  [$h,$m] = array_map('intval', explode(':',$hhmm));
  $total = $h*60+$m + max(0,(int)$mins);
  $total = max(0, $total);
  $H = floor($total/60) % 24; $M = $total%60;
  return sprintf('%02d:%02d', $H, $M);
}

// Canchas (SOLO ACTIVAS)
$canchas = [];
$stmt = $conn->prepare("SELECT cancha_id, nombre, precio, duracion_turno FROM canchas WHERE proveedor_id = ? AND activa = 1 ORDER BY nombre");
$stmt->bind_param("i", $proveedor_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $canchas[] = $r;
$stmt->close();

// ---- Prefill desde GET (viniendo del calendario) ----
$pre_cancha_id   = isset($_GET['cancha_id']) ? (int)$_GET['cancha_id'] : 0;
$pre_fecha       = isset($_GET['fecha']) ? trim($_GET['fecha']) : date('Y-m-d');
$pre_hora_inicio = hhmm($_GET['hora_inicio'] ?? '');
$pre_duracion    = isset($_GET['duracion']) ? max(0,(int)$_GET['duracion']) : 0;

// Si no vino duración, tomamos la de la cancha (si se seleccionó)
$cancha_turno_map = [];
foreach ($canchas as $c) $cancha_turno_map[(int)$c['cancha_id']] = (int)$c['duracion_turno'];

if ($pre_cancha_id>0 && $pre_duracion<=0 && isset($cancha_turno_map[$pre_cancha_id])) {
  $pre_duracion = max(1, (int)$cancha_turno_map[$pre_cancha_id]);
}

// Calcular hora_fin si hay inicio + duración
$pre_hora_fin = ($pre_hora_inicio !== '' && $pre_duracion>0) ? add_minutes_to_hhmm($pre_hora_inicio, $pre_duracion) : '';

/* Clientes REGISTRADOS (excluir invitados) */
$clientes = [];
$q = $conn->prepare("
  SELECT u.user_id, u.nombre, u.email
  FROM usuarios u
  LEFT JOIN invitados i ON i.user_id = u.user_id
  WHERE u.rol='cliente' AND i.user_id IS NULL
  ORDER BY u.nombre
");
$q->execute();
$rc = $q->get_result();
while ($r = $rc->fetch_assoc()) $clientes[] = $r;
$q->close();
?>
<main>
  <div class="form-container">
    <h2>Nueva reserva</h2>

    <style>
      .row-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
      .row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
      .row-players{display:grid;grid-template-columns:1fr 1fr;gap:12px}
      @media (max-width:900px){.row-3{grid-template-columns:1fr 1fr}}
      @media (max-width:720px){.row-2,.row-3,.row-players{grid-template-columns:1fr}}
      .fld{display:flex;flex-direction:column;gap:6px}
      .fld input,.fld select{width:100%;box-sizing:border-box}
      .hint{font-size:12px;color:#666}.hint.error{color:#c62828}

      .party-wrap{border:1px dashed #e5e7eb;border-radius:10px;padding:12px;margin-top:8px}
      .party-controls{display:none}
      .playerBox{display:flex;flex-direction:column;gap:8px}
      .playerModes{display:flex;align-items:center;gap:14px}
      .playerField{display:flex;flex-direction:column;gap:6px}
      .muted{font-size:12px;color:#6b7280}

      .promo-box{border:1px solid #e5e7eb;border-radius:10px;padding:10px}
      .promo-title{font-size:13px;font-weight:700;color:#334155;margin-bottom:6px}
      .promo-item{font-size:13px;color:#0b6158;background:#e6f7f4;border:1px solid #b7e6de;border-radius:8px;padding:6px 8px;margin-bottom:6px}
      .promo-empty{font-size:13px;color:#6b7280;background:#f8fafc;border:1px dashed #e5e7eb;border-radius:8px;padding:8px}
      .totals{font-size:13px;color:#111827;display:flex;gap:12px;flex-wrap:wrap;margin-top:6px}
      .totals .pill{background:#eef2f7;border:1px solid #d8e0ea;border-radius:999px;padding:4px 8px}

      .split-header{display:flex;align-items:center;gap:10px;margin:6px 0 8px}
      .split-wrap{border:1px dashed #e5e7eb;border-radius:10px;padding:12px;margin-top:4px}
      .split-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}

      input, select { margin: 0 !important; }
      @media(max-width:900px){.split-grid{grid-template-columns:1fr}}
    </style>

    <form method="POST" action="reservasAction.php" id="reservaForm">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="duracion" id="duracion_hidden" value="<?= $pre_duracion > 0 ? (int)$pre_duracion : '' ?>">

      <!-- 1) Cancha – Fecha -->
      <div class="row-2">
        <div class="fld">
          <label>Cancha:</label>
          <select name="cancha_id" id="cancha_id" required>
            <option value="">Selecciona cancha</option>
            <?php foreach ($canchas as $c): ?>
              <option value="<?= (int)$c['cancha_id'] ?>"
                      data-precio="<?= htmlspecialchars($c['precio']) ?>"
                      data-turno="<?= (int)$c['duracion_turno'] ?>"
                      <?= $pre_cancha_id === (int)$c['cancha_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fld">
          <label>Fecha:</label>
          <input type="date" name="fecha" id="fecha" value="<?= htmlspecialchars($pre_fecha) ?>" required>
        </div>
      </div>

      <!-- 2) Hora inicio – Hora fin – Duración -->
      <div class="row-3" style="margin-top:12px">
        <div class="fld">
          <label>Hora inicio:</label>
          <input type="time" name="hora_inicio" id="hora_inicio" value="<?= htmlspecialchars($pre_hora_inicio) ?>" required>
        </div>
        <div class="fld">
          <label>Hora fin:</label>
          <input type="time" id="hora_fin" value="<?= htmlspecialchars($pre_hora_fin) ?>" required>
        </div>
        <div class="fld">
          <label>Duración (min):</label>
          <input type="number" id="duracion_view" min="1" step="1" value="<?= $pre_duracion > 0 ? (int)$pre_duracion : '' ?>" readonly>
        </div>
      </div>
      <div class="hint error" id="timeError" style="display:none;margin-top:4px">La hora fin debe ser mayor a la inicio.</div>

      <!-- Promociones aplicadas -->
      <div class="fld" style="margin-top:12px">
        <div class="promo-box">
          <div class="promo-title">Promociones aplicadas</div>
          <div id="promosList" class="promo-empty">Sin promociones.</div>
          <div class="totals" id="promosTotals" style="display:none">
            <span class="pill" id="pillBase"></span>
            <span class="pill" id="pillDesc"></span>
            <span class="pill" id="pillFinal"></span>
          </div>
        </div>
      </div>

      <!-- 3) Tipo – Método – Precio total -->
      <div class="row-3" style="margin-top:12px">
        <div class="fld">
          <label>Tipo de reserva:</label>
          <select name="tipo_reserva" id="tipo_reserva" required>
            <option value="individual" selected>Individual</option>
            <option value="equipo">Equipo</option>
          </select>
        </div>
        <div class="fld">
          <label>Método de pago:</label>
          <select name="metodo" id="metodo" required>
            <option value="club">Presencial</option>
            <option value="tarjeta">Tarjeta de crédito</option>
            <option value="mercado_pago">Mercado Pago</option>
          </select>
        </div>
        <div class="fld">
          <label>Precio total:</label>
          <input type="number" step="0.01" min="0" name="precio_total" id="precio_total" readonly>
        </div>
      </div>

      <!-- 4) Cliente + Dividir costos -->
      <div class="fld" style="margin-top:12px">
        <label>Cliente (Nombre y apellido):</label>
        <input type="text" name="cliente_nombre" id="cliente_nombre" placeholder="Nombre y apellido" required>
      </div>

      <div class="party-wrap" style="margin-top:10px">
        <div class="split-header">
          <input type="checkbox" id="splitCosts" name="split_costs" value="1">
          <label for="splitCosts" style="margin:0">Dividir costos</label>
        </div>
        <div id="splitBox" class="split-wrap" style="display:none">
          <div class="muted">Completar datos de los demás integrantes</div>
          <div id="splitGrid" class="split-grid"></div>
        </div>
      </div>

      <hr style="margin:12px 0;border:none;border-top:1px solid #eee">

      <!-- 5) Crear partido -->
      <div class="party-wrap">
        <div style="display:flex;align-items:center;gap:10px;margin:0 0 8px">
          <input id="chkCrearPartido" type="checkbox" name="crear_partido" value="1">
          <label for="chkCrearPartido" style="margin:0">Crear partido a partir de esta reserva</label>
        </div>

        <div id="partyControls" class="party-controls">
          <div class="row-players">
            <!-- Jugador/Representante 1 -->
            <div class="playerBox">
              <div class="muted" id="lblP1">Jugador 1:</div>
              <div class="playerModes">
                <label><input type="radio" name="p1_mode" value="reg" checked> Registrado</label>
                <label><input type="radio" name="p1_mode" value="inv"> Invitado</label>
              </div>
              <div class="playerField" id="p1_reg_box">
                <select name="jugador1_id" id="jugador1_id">
                  <option value="">Selecciona jugador</option>
                  <?php foreach ($clientes as $cl): ?>
                    <option value="<?= (int)$cl['user_id'] ?>"><?= htmlspecialchars($cl['nombre']) ?> (<?= htmlspecialchars($cl['email']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="playerField" id="p1_inv_box" style="display:none">
                <input type="text" name="jugador1_nombre" placeholder="Nombre y apellido">
              </div>
            </div>

            <!-- Jugador/Representante 2 -->
            <div class="playerBox">
              <div class="muted" id="lblP2">Jugador 2:</div>
              <div class="playerModes">
                <label><input type="radio" name="p2_mode" value="reg" checked> Registrado</label>
                <label><input type="radio" name="p2_mode" value="inv"> Invitado</label>
              </div>
              <div class="playerField" id="p2_reg_box">
                <select name="jugador2_id" id="jugador2_id">
                  <option value="">Selecciona jugador</option>
                  <?php foreach ($clientes as $cl): ?>
                    <option value="<?= (int)$cl['user_id'] ?>"><?= htmlspecialchars($cl['nombre']) ?> (<?= htmlspecialchars($cl['email']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="playerField" id="p2_inv_box" style="display:none">
                <input type="text" name="jugador2_nombre" placeholder="Nombre y apellido">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions" style="display:flex;gap:10px;align-items:center;margin-top:12px">
        <button type="submit" class="btn-add" id="btnSubmit">Crear reserva</button>
        <a href="/php/recepcionista/reservas/reservas.php" class="btn-add btn-outline" style="text-decoration:none;">Cancelar</a>
      </div>
    </form>
  </div>
</main>

<script>
/* Mostrar/ocultar bloque partido */
(function(){
  const chk=document.getElementById('chkCrearPartido'), box=document.getElementById('partyControls');
  if(!chk||!box) return;
  const sync=()=>{ box.style.display=chk.checked?'block':'none'; };
  chk.addEventListener('change', sync); sync();
})();

/* Duración + validación + recalcular precio/promos */
(function(){
  const ini=document.getElementById('hora_inicio'), fin=document.getElementById('hora_fin');
  const view=document.getElementById('duracion_view'), hid=document.getElementById('duracion_hidden');
  const err=document.getElementById('timeError'), submit=document.getElementById('btnSubmit');

  function toMin(v){ if(!v||!/^\d{2}:\d{2}$/.test(v)) return NaN; const [h,m]=v.split(':').map(Number); return h*60+m; }
  function minToHHMM(m){ const h=Math.floor(m/60), mm=m%60; return String(h).padStart(2,'0')+':'+String(mm).padStart(2,'0'); }

  function recompute(){
    const a=toMin(ini.value), b=toMin(fin.value);
    if(Number.isNaN(a)||Number.isNaN(b)){ view.value=0; hid.value=''; err.style.display='none'; submit.disabled=false; autoPrice(); return; }
    const d=b-a;
    if(d<=0){ view.value=0; hid.value=''; err.style.display='block'; submit.disabled=true; autoPrice(); return; }
    view.value=d; hid.value=String(d); err.style.display='none'; submit.disabled=false; autoPrice();
  }

  // Si hay inicio y no hay fin pero hay duración oculta, calcular fin
  window.__prefillFix = function(){
    const d = parseInt(hid.value || view.value || '0',10);
    const a = toMin(ini.value);
    if (!Number.isNaN(a) && d>0 && (!fin.value || !/^\d{2}:\d{2}$/.test(fin.value))) {
      fin.value = minToHHMM(a + d);
    }
    recompute();
  };

  [ini,fin].forEach(el=>{ el.addEventListener('change',recompute); el.addEventListener('input',recompute); });
})();

/* Precio base + promos (AJAX) */
(function(){
  const sel=document.getElementById('cancha_id'), dur=document.getElementById('duracion_view'), out=document.getElementById('precio_total');
  const fecha=document.getElementById('fecha'), horaIni=document.getElementById('hora_inicio');
  const list=document.getElementById('promosList'), totals=document.getElementById('promosTotals');
  const pillBase=document.getElementById('pillBase'), pillDesc=document.getElementById('pillDesc'), pillFinal=document.getElementById('pillFinal');

  window.autoPrice=function(){
    const opt=sel && sel.options[sel.selectedIndex]; const mins=parseInt(dur.value||'0',10);
    if(!opt || !mins){ out.value=''; renderPromos(null); return; }
    const precio=parseFloat(opt.getAttribute('data-precio') || ''); if(!isFinite(precio)){ out.value=''; renderPromos(null); return; }
    const base=precio*(mins/60);

    if (sel.value && fecha.value && horaIni.value && mins>0) {
      const form=new FormData(); form.append('action','promos_preview');
      form.append('cancha_id', sel.value); form.append('fecha', fecha.value); form.append('hora_inicio', horaIni.value);
      form.append('duracion', String(mins));
      fetch('reservasAction.php',{method:'POST',body:form})
        .then(r=>r.json()).then(j=>{
          if(!j||!j.ok){ out.value=base.toFixed(2); renderPromos({promos:[],base,pct:0,final:base}); return; }
          out.value=j.data.precio_final.toFixed(2);
          renderPromos({promos:j.data.promos, base:j.data.precio_base, pct:j.data.total_descuento_pct, final:j.data.precio_final});
        }).catch(()=>{ out.value=base.toFixed(2); renderPromos({promos:[],base,pct:0,final:base}); });
    } else { out.value=base.toFixed(2); renderPromos({promos:[],base,pct:0,final:base}); }
  };

  function renderPromos(d){
    if(!d||!d.promos||d.promos.length===0){ list.className='promo-empty'; list.textContent='Sin promociones.'; totals.style.display='none'; return; }
    list.className=''; list.innerHTML=d.promos.map(p=>`<div class="promo-item"><strong>${esc(p.nombre)}</strong> — ${p.porcentaje_descuento}% · ahorro $ ${Number(p.ahorro).toFixed(2)}</div>`).join('');
    totals.style.display=''; pillBase.textContent=`Base: $ ${Number(d.base).toFixed(2)}`;
    pillDesc.textContent=`Desc.: ${Number(d.pct).toFixed(2)}%`; pillFinal.textContent=`Final: $ ${Number(d.final).toFixed(2)}`;
  }
  function esc(s){return (s??'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}

  let t=null; ['change','input'].forEach(ev=>{
    [sel,dur,fecha,horaIni].forEach(el=>{ if(!el) return; el.addEventListener(ev, ()=>{ clearTimeout(t); t=setTimeout(()=>autoPrice(),300); }); });
  });
})();

/* Dividir costos */
(function(){
  const split = document.getElementById('splitCosts');
  const box   = document.getElementById('splitBox');
  const grid  = document.getElementById('splitGrid');
  const tipo  = document.getElementById('tipo_reserva');

  function labelFor(idx, mode){
    if (mode==='individual') return 'Segundo integrante';
    return ['Segundo integrante','Tercer integrante','Cuarto integrante'][idx] || 'Integrante';
    }

  function render(){
    box.style.display = split.checked ? 'block' : 'none';
    grid.innerHTML = '';
    if(!split.checked) return;
    const n = (tipo.value === 'equipo') ? 3 : 1;
    for(let i=0;i<n;i++){
      const label = labelFor(i, tipo.value);
      const div = document.createElement('div');
      div.innerHTML = `<div class="fld"><label>${label}:</label><input type="text" name="split_names[]" placeholder="Nombre y apellido" required></div>`;
      grid.appendChild(div);
    }
  }
  split.addEventListener('change', render);
  tipo.addEventListener('change', render);
  render();
})();

/* Modo de jugador + evitar duplicados + labels por tipo */
(function(){
  const chk=document.getElementById('chkCrearPartido'), panel=document.getElementById('partyControls');
  chk && chk.addEventListener('change',()=>{ panel.style.display=chk.checked?'block':'none'; });

  const p1m=document.querySelectorAll('input[name="p1_mode"]'), p2m=document.querySelectorAll('input[name="p2_mode"]');
  const p1Reg=document.getElementById('p1_reg_box'), p1Inv=document.getElementById('p1_inv_box');
  const p2Reg=document.getElementById('p2_reg_box'), p2Inv=document.getElementById('p2_inv_box');
  const s1=document.getElementById('jugador1_id'), s2=document.getElementById('jugador2_id');

  function swap(boxReg,boxInv,val){const isReg=val==='reg'; boxReg.style.display=isReg?'flex':'none'; boxInv.style.display=isReg?'none':'flex';}
  p1m.forEach(r=>r.addEventListener('change',e=>swap(p1Reg,p1Inv,e.target.value)));
  p2m.forEach(r=>r.addEventListener('change',e=>swap(p2Reg,p2Inv,e.target.value)));
  swap(p1Reg,p1Inv,(document.querySelector('input[name="p1_mode"]:checked')||{}).value||'reg');
  swap(p2Reg,p2Inv,(document.querySelector('input[name="p2_mode"]:checked')||{}).value||'reg');

  function syncDisable(){
    const v1=s1.value,v2=s2.value;
    if(s1&&s2){
      [...s1.options].forEach(o=>o.disabled=false);
      [...s2.options].forEach(o=>o.disabled=false);
      if(v1)[...s2.options].forEach(o=>{if(o.value && o.value===v1) o.disabled=true;});
      if(v2)[...s1.options].forEach(o=>{if(o.value && o.value===v2) o.disabled=true;});
    }
  }
  s1&&s1.addEventListener('change', syncDisable);
  s2&&s2.addEventListener('change', syncDisable);
  syncDisable();

  /* Labels dinámicos */
  const tipo=document.getElementById('tipo_reserva');
  const lblP1=document.getElementById('lblP1');
  const lblP2=document.getElementById('lblP2');
  function syncLabels(){
    const team = tipo.value==='equipo';
    lblP1.textContent = team ? 'Representante equipo 1:' : 'Jugador 1:';
    lblP2.textContent = team ? 'Representante equipo 2:' : 'Jugador 2:';
  }
  tipo.addEventListener('change', syncLabels); syncLabels();
})();

/* ===== Prefill post-carga: forzar recálculo si venimos del calendario ===== */
(function(){
  // Disparamos el cálculo de fin/duración si faltaba y el cálculo de precio/promos
  if (window.__prefillFix) window.__prefillFix();
  if (window.autoPrice) window.autoPrice();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
