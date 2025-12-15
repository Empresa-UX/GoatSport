<?php
/**************************************************************
 * SOLO LECTURA — Bracket SVG (como admin)
 *************************************************************/
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol']??'')!=='proveedor') {
  header('Location: ../login.php'); exit;
}
$proveedor_id=(int)$_SESSION['usuario_id'];

$torneo_id = (int)($_GET['torneo_id'] ?? 0);
if ($torneo_id<=0){ header('Location: torneos.php'); exit; }

/* Torneo del proveedor */
$ts = $conn->prepare("
  SELECT t.torneo_id,t.nombre,t.tipo,t.capacidad, t.proveedor_id
  FROM torneos t
  WHERE t.torneo_id=? AND t.proveedor_id=?
");
$ts->bind_param("ii",$torneo_id,$proveedor_id);
$ts->execute(); $tres=$ts->get_result();
$torneo=$tres->fetch_assoc(); $ts->close();
if(!$torneo){ header('Location: torneos.php'); exit; }

/* Participantes aceptados */
$ps = $conn->prepare("
  SELECT u.user_id,u.nombre
  FROM participaciones p
  INNER JOIN usuarios u ON u.user_id=p.jugador_id
  WHERE p.torneo_id=? AND p.estado='aceptada'
  ORDER BY p.es_creador DESC, u.nombre ASC
");
$ps->bind_param("i",$torneo_id);
$ps->execute(); $pres=$ps->get_result();
$participantes = $pres? $pres->fetch_all(MYSQLI_ASSOC):[];
$ps->close();

$cap = max(4, (int)$torneo['capacidad']);
function nextPow2($n){ $p=1; while($p<$n) $p<<=1; return $p; }
$slots = max(4, min(nextPow2($cap), 64));

$seed = array_map(fn($r)=>trim($r['nombre']), $participantes);
while(count($seed)<$slots) $seed[] = 'Sin participante';

$tipoLbl = strtolower($torneo['tipo'])==='individual' ? 'Individual' : 'Equipo';
?>
<div class="section">
  <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <div style="display:flex;flex-direction:column;gap:6px;">
      <h2 style="margin:0;">Bracket — <?= htmlspecialchars($torneo['nombre']) ?></h2>
      <div class="meta-chips">
        <span class="chip chip-type"><?= htmlspecialchars($tipoLbl) ?></span>
        <span class="chip chip-cap"><?= (int)$cap ?> plazas</span>
        <span class="chip chip-slots"><?= (int)$slots ?> slots</span>
      </div>
    </div>
    <button class="btn-add" onclick="location.href='torneos.php'">Volver</button>
  </div>

  <style>
    :root{ --brand:#0f766e; --soft:#1bab9d; --ink:#334155; }
    .btn-add { display:inline-flex; align-items:center; gap:8px; padding:8px 12px;
      text-decoration:none; font-weight:600; font-size:14px; transition:filter .15s ease, transform .03s ease; white-space:nowrap; border:1px solid #bfd7ff;background:#e0ecff;color:#1e40af;border-radius:10px}
    .btn-add:hover { filter:brightness(.98); }

    .meta-chips{ display:flex; gap:6px; flex-wrap:wrap; }
    .chip{ display:inline-block; padding:4px 10px; border-radius:999px; font-weight:700; font-size:12px;
      border:1px solid #e2e8f0; background:#fff; color:#475569; }
    .chip-type{  background:#e0ecff;border-color:#bfd7ff;color:#1e40af }
    .chip-cap{   background:#fff7e6;border-color:#ffe2b8;color:#92400e }
    .chip-slots{ background:#eef2ff;border-color:#c7d2fe;color:#3730a3 }

    .stage{ background:#fff; border-radius:14px; box-shadow:0 10px 24px rgba(0,0,0,.06); border:1px solid #e5e7eb; overflow:hidden; }
    .stage-head{ display:flex; align-items:center; justify-content:space-between; gap:8px;
      padding:12px 14px; background:linear-gradient(90deg,#ffffff,#f8fffd);
      border-bottom:1px solid #eef2f7; font-weight:800; color:#475569; }

    .svg-wrap{ overflow:auto; background:#ffffff; }
    .round-title{ font:900 12px/1 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; fill:#475569; text-transform:uppercase; letter-spacing:.06em; }
    .slot-text{ font:700 12px/1.1 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; fill:#0f172a; }
    .slot-empty{ fill:#64748b; }
  </style>

  <div class="stage">
    <div class="stage-head">
      <div>Participantes cargados: <?= count($participantes) ?></div>
      <div>Vista solo lectura</div>
    </div>
    <div class="svg-wrap">
      <svg id="bracketSvg" xmlns="http://www.w3.org/2000/svg"></svg>
    </div>
  </div>
</div>

<script>
(function(){
  const seed  = <?= json_encode($seed, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
  const svg   = document.getElementById('bracketSvg');
  const EMPTY = 'Sin participante';

  const COL_GAP = 120, BOX_W = 200, BOX_H = 30;
  const V_GAP = 30, V_GAP_R1 = 10;
  const TICK = 16, IN_TICK = 12, STROKE = 2;
  const M_LEFT = 20, M_TOP = 56, LINE = '#111827';

  const COL_W   = BOX_W + COL_GAP;
  const ROUNDS  = Math.log2(seed.length) | 0;
  const MATCHES_R0 = seed.length / 2;
  const STEP0   = (BOX_H*2 + V_GAP);

  const centerY = (r,i)=> M_TOP + (i + 0.5) * (STEP0 * Math.pow(2, r));
  const pairGap = (r)=> r===0 ? V_GAP_R1 : V_GAP;

  const S=(n,a={})=>{const e=document.createElementNS('http://www.w3.org/2000/svg',n); for(const k in a) e.setAttribute(k,a[k]); return e;}
  const ln=(x1,y1,x2,y2)=>S('line',{x1,y1,x2,y2,stroke:LINE,'stroke-width':STROKE});
  const pathL=(pts)=>{ const d=['M',pts[0][0],pts[0][1]]; for(let i=1;i<pts.length;i++) d.push('L',pts[i][0],pts[i][1]); return S('path',{d:d.join(' '),fill:'none',stroke:LINE,'stroke-width':STROKE}); };

  function drawBox(x,y,w,h,empty,text){
    const g=S('g');
    const r=S('rect',{x,y,width:w,height:h,rx:6,ry:6,fill:empty?'#f8fafc':'#fff',stroke:'#e2e8f0','stroke-width':1});
    if(empty) r.setAttribute('stroke-dasharray','4 3');
    const t=S('text',{x:x+10,y:y+h/2+4,class:'slot-text'+(empty?' slot-empty':'')});
    t.textContent=text;
    g.appendChild(r); g.appendChild(t);
    return g;
  }
  const titleFor=(i,t)=> i===t-1?'Final' : (i===t-2?'Semifinal' : (i===0?'Ronda 1':'Ronda '+(i+1)));
  const labelForRound = (r,total)=>{ if (r===0) return null; if (r===total-1) return 'Finalista'; if (r===total-2) return 'Semifinalista'; return 'Clasificado'; };
  const CHAMP_LABEL = 'Ganador';

  const lastMid   = centerY(0, MATCHES_R0-1);
  const lastBottom= lastMid + (V_GAP/2) + BOX_H;
  const width     = M_LEFT + (ROUNDS+1)*COL_W + BOX_W + 40;
  const height    = lastBottom + 40;
  svg.setAttribute('width', width);
  svg.setAttribute('height', height);
  svg.innerHTML='';

  for(let r=0;r<ROUNDS;r++){
    const t=S('text',{x:M_LEFT + r*COL_W + 4, y:24, class:'round-title'}); t.textContent=titleFor(r, ROUNDS);
    svg.appendChild(t);
  }
  const tChamp=S('text',{x:M_LEFT + ROUNDS*COL_W + 4, y:24, class:'round-title'}); tChamp.textContent='Campeón';
  svg.appendChild(tChamp);

  for(let r=0;r<ROUNDS;r++){
    const matches = seed.length / Math.pow(2, r+1);
    const x = M_LEFT + r*COL_W;
    const g = pairGap(r);
    for(let m=0;m<matches;m++){
      const mid = centerY(r, m);
      const aY  = mid - (BOX_H + g/2);
      const bY  = mid + (g/2);
      const placeholder = labelForRound(r, ROUNDS);
      const aTxt = (r===0 ? (seed[m*2]   || EMPTY) : placeholder);
      const bTxt = (r===0 ? (seed[m*2+1] || EMPTY) : placeholder);

      if (r>0){
        svg.appendChild(ln(x - IN_TICK, aY + BOX_H/2, x, aY + BOX_H/2));
        svg.appendChild(ln(x - IN_TICK, bY + BOX_H/2, x, bY + BOX_H/2));
      }

      svg.appendChild(drawBox(x, aY, BOX_W, BOX_H, true, aTxt));
      svg.appendChild(drawBox(x, bY, BOX_W, BOX_H, true, bTxt));

      const right = x + BOX_W, xJoin = right + TICK;
      svg.appendChild(ln(right, aY + BOX_H/2, xJoin, aY + BOX_H/2));
      svg.appendChild(ln(right, bY + BOX_H/2, xJoin, bY + BOX_H/2));
      svg.appendChild(ln(xJoin, aY + BOX_H/2, xJoin, bY + BOX_H/2));

      if (r < ROUNDS-1){
        const nextIdx = Math.floor(m/2);
        const nextX   = M_LEFT + (r+1)*COL_W;

        const nextMid = centerY(r+1, nextIdx);
        const ng      = pairGap(r+1);
        const nextAY  = nextMid - (BOX_H + ng/2);
        const nextBY  = nextMid + (ng/2);
        const yDest = (m%2===0) ? (nextAY + BOX_H/2) : (nextBY + BOX_H/2);

        const xKnee = nextX - IN_TICK - TICK/2;
        svg.appendChild(pathL([[xJoin, mid],[xKnee, mid],[xKnee, yDest],[nextX - IN_TICK, yDest]]));
        svg.appendChild(ln(nextX - IN_TICK, yDest, nextX, yDest));
      } else {
        const champX = M_LEFT + ROUNDS*COL_W;
        svg.appendChild(ln(xJoin, mid, champX - IN_TICK, mid));
        svg.appendChild(ln(champX - IN_TICK, mid, champX, mid));
        svg.appendChild(drawBox(champX, mid - BOX_H/2, BOX_W, BOX_H, true, CHAMP_LABEL));
      }
    }
  }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
