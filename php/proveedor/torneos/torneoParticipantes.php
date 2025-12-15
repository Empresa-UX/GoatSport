<?php
/**************************************************************
 * PROVEEDOR — Bracket SVG REAL
 * - Muestra nombres reales en cada ronda
 * - Propaga ganadores por next_partido_id/next_pos
 * - Muestra campeón real si existe
 *************************************************************/
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header('Location: ../login.php'); exit;
}

$proveedor_id = (int)$_SESSION['usuario_id'];
$torneo_id = (int)($_GET['torneo_id'] ?? 0);
if ($torneo_id <= 0) { header('Location: torneos.php'); exit; }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Torneo (propiedad) */
$ts = $conn->prepare("
  SELECT t.torneo_id,t.nombre,t.tipo,t.capacidad,t.proveedor_id,
         COALESCE(pd.nombre_club, pu.nombre) AS club
  FROM torneos t
  LEFT JOIN usuarios pu ON pu.user_id=t.proveedor_id
  LEFT JOIN proveedores_detalle pd ON pd.proveedor_id=t.proveedor_id
  WHERE t.torneo_id=? AND t.proveedor_id=?
  LIMIT 1
");
$ts->bind_param("ii",$torneo_id,$proveedor_id);
$ts->execute();
$torneo = $ts->get_result()->fetch_assoc();
$ts->close();
if(!$torneo){ header('Location: torneos.php'); exit; }

/* Partidos del torneo (incluye nombres + next_*) */
$ms = $conn->prepare("
  SELECT
    p.partido_id, p.ronda, p.idx_ronda, p.next_partido_id, p.next_pos,
    p.jugador1_id, p.jugador2_id, p.ganador_id, p.resultado,
    u1.nombre AS j1_nombre,
    u2.nombre AS j2_nombre,
    ug.nombre AS ganador_nombre
  FROM partidos p
  LEFT JOIN usuarios u1 ON u1.user_id = p.jugador1_id
  LEFT JOIN usuarios u2 ON u2.user_id = p.jugador2_id
  LEFT JOIN usuarios ug ON ug.user_id = p.ganador_id
  WHERE p.torneo_id = ?
  ORDER BY p.ronda ASC, p.idx_ronda ASC, p.partido_id ASC
");
$ms->bind_param("i",$torneo_id);
$ms->execute();
$matchesRows = $ms->get_result()->fetch_all(MYSQLI_ASSOC);
$ms->close();

/* Fallback si aún no hay partidos: participantes aceptados */
$participantes = [];
if (!$matchesRows) {
  $ps = $conn->prepare("
    SELECT u.user_id,u.nombre
    FROM participaciones p
    INNER JOIN usuarios u ON u.user_id=p.jugador_id
    WHERE p.torneo_id=? AND p.estado='aceptada'
    ORDER BY p.es_creador DESC, u.nombre ASC
  ");
  $ps->bind_param("i",$torneo_id);
  $ps->execute();
  $participantes = $ps->get_result()->fetch_all(MYSQLI_ASSOC);
  $ps->close();
}

/* Index por partido_id para propagar ganadores a rondas siguientes */
$byId = [];
$maxRonda = 0;

foreach ($matchesRows as $m) {
  $pid = (int)$m['partido_id'];
  $r   = (int)$m['ronda'];
  $idx = (int)$m['idx_ronda'];
  $maxRonda = max($maxRonda, $r);

  $byId[$pid] = [
    'partido_id' => $pid,
    'ronda' => $r,
    'idx_ronda' => $idx,
    'next_partido_id' => $m['next_partido_id'] !== null ? (int)$m['next_partido_id'] : null,
    'next_pos' => $m['next_pos'] ?? null,

    'jugador1_id' => $m['jugador1_id'] !== null ? (int)$m['jugador1_id'] : null,
    'jugador2_id' => $m['jugador2_id'] !== null ? (int)$m['jugador2_id'] : null,

    'j1_nombre' => (string)($m['j1_nombre'] ?? ''),
    'j2_nombre' => (string)($m['j2_nombre'] ?? ''),

    'ganador_id' => $m['ganador_id'] !== null ? (int)$m['ganador_id'] : null,
    'ganador_nombre' => (string)($m['ganador_nombre'] ?? ''),
    'resultado' => (string)($m['resultado'] ?? ''),
  ];
}

/* Propagar ganadores */
if ($byId) {
  usort($matchesRows, function($a,$b){
    $ra=(int)$a['ronda']; $rb=(int)$b['ronda'];
    if ($ra!==$rb) return $ra<=>$rb;
    $ia=(int)$a['idx_ronda']; $ib=(int)$b['idx_ronda'];
    if ($ia!==$ib) return $ia<=>$ib;
    return (int)$a['partido_id'] <=> (int)$b['partido_id'];
  });

  foreach ($matchesRows as $m) {
    $pid = (int)$m['partido_id'];
    $gan = $byId[$pid]['ganador_id'] ?? null;
    $nextId = $byId[$pid]['next_partido_id'] ?? null;
    $pos    = $byId[$pid]['next_pos'] ?? null;

    if (!$gan || !$nextId || !$pos) continue;
    if (!isset($byId[$nextId])) continue;

    if ($pos === 'j1') {
      if ($byId[$nextId]['jugador1_id'] === null) {
        $byId[$nextId]['jugador1_id'] = $gan;
        $byId[$nextId]['j1_nombre'] = $byId[$pid]['ganador_nombre'] ?: ('#'.$gan);
      }
    } elseif ($pos === 'j2') {
      if ($byId[$nextId]['jugador2_id'] === null) {
        $byId[$nextId]['jugador2_id'] = $gan;
        $byId[$nextId]['j2_nombre'] = $byId[$pid]['ganador_nombre'] ?: ('#'.$gan);
      }
    }
  }
}

/* Agrupar por ronda */
$rounds = [];
if ($byId) {
  foreach ($byId as $m) {
    $r = (int)$m['ronda'];
    if (!isset($rounds[$r])) $rounds[$r] = [];
    $rounds[$r][] = $m;
  }
  ksort($rounds);
  foreach ($rounds as $r => &$list) {
    usort($list, fn($a,$b)=> ($a['idx_ronda'] <=> $b['idx_ronda']) ?: ($a['partido_id'] <=> $b['partido_id']));
  }
  unset($list);
}

/* Campeón */
$champName = 'Ganador';
if ($byId && $maxRonda > 0) {
  $finalMatch = $rounds[$maxRonda][0] ?? null;
  if ($finalMatch && !empty($finalMatch['ganador_nombre'])) $champName = $finalMatch['ganador_nombre'];
}

$tipoLbl = strtolower($torneo['tipo'])==='individual' ? 'Individual' : 'Equipo';
?>
<div class="section">
  <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
    <div style="display:flex;flex-direction:column;gap:6px;">
      <h2 style="margin:0;">Bracket — <?= h($torneo['nombre']) ?></h2>
      <div class="meta-chips">
        <span class="chip chip-brand"><?= h($torneo['club'] ?: '—') ?></span>
        <span class="chip chip-type"><?= h($tipoLbl) ?></span>
        <span class="chip chip-cap"><?= (int)($torneo['capacidad'] ?? 0) ?> plazas</span>
      </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a class="btn-add" href="torneoCronograma.php?torneo_id=<?= (int)$torneo_id ?>">Ver cronograma</a>
      <a class="btn-add" href="torneos.php">Volver</a>
    </div>
  </div>

  <style>
    :root{ --brand:#0f766e; }
    .btn-add{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;text-decoration:none;font-weight:800;font-size:14px;border-radius:10px;border:1px solid #bfd7ff;background:#e0ecff;color:#1e40af}
    .meta-chips{ display:flex; gap:6px; flex-wrap:wrap; }
    .chip{ display:inline-block; padding:4px 10px; border-radius:999px; font-weight:900; font-size:12px; border:1px solid #e2e8f0; background:#fff; color:#475569; }
    .chip-brand{ background:#e6f7f4; border-color:#c8efe8; color:#0f766e; }
    .chip-type{ background:#e0ecff; border-color:#bfd7ff; color:#1e40af; }
    .chip-cap{ background:#fff7e6; border-color:#ffe2b8; color:#92400e; }

    .stage{ background:#fff; border-radius:14px; box-shadow:0 10px 24px rgba(0,0,0,.06); border:1px solid #e5e7eb; overflow:hidden; }
    .svg-wrap{ overflow:auto; background:#fff; }
    .round-title{ font:900 12px/1 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; fill:#475569; text-transform:uppercase; letter-spacing:.06em; }

    .slot-text{ font:800 12px/1.1 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; fill:#0f172a; }
    .slot-empty{ fill:#64748b; }
    .win-text{ fill:#065f46; }
  </style>

  <div class="stage">
    <div class="svg-wrap">
      <svg id="bracketSvg" xmlns="http://www.w3.org/2000/svg"></svg>
    </div>
  </div>
</div>

<script>
(function(){
  const svg = document.getElementById('bracketSvg');

  const rounds = <?= json_encode($rounds ? array_values($rounds) : [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
  const champLabel = <?= json_encode($champName, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

  const fallbackSeed = <?= json_encode(array_map(fn($p)=>$p['nombre'], $participantes), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
  const EMPTY = '—';

  const COL_GAP = 120, BOX_W = 220, BOX_H = 30;
  const V_GAP = 30, V_GAP_R1 = 10;
  const TICK = 16, IN_TICK = 12, STROKE = 2;
  const M_LEFT = 20, M_TOP = 56, LINE = '#111827';

  const S=(n,a={})=>{const e=document.createElementNS('http://www.w3.org/2000/svg',n); for(const k in a) e.setAttribute(k,a[k]); return e;}
  const ln=(x1,y1,x2,y2)=>S('line',{x1,y1,x2,y2,stroke:LINE,'stroke-width':STROKE});
  const pathL=(pts)=>{ const d=['M',pts[0][0],pts[0][1]]; for(let i=1;i<pts.length;i++) d.push('L',pts[i][0],pts[i][1]); return S('path',{d:d.join(' '),fill:'none',stroke:LINE,'stroke-width':STROKE}); };

  function drawBox(x,y,text,isEmpty,isWinner){
    const g=S('g');
    const rect=S('rect',{
      x,y,width:BOX_W,height:BOX_H,rx:6,ry:6,
      fill:isEmpty?'#f8fafc':'#fff',
      stroke:isWinner?'#10b981':'#e2e8f0',
      'stroke-width':isWinner?2:1
    });
    if(isEmpty) rect.setAttribute('stroke-dasharray','4 3');
    const t=S('text',{x:x+10,y:y+BOX_H/2+4,class:'slot-text'+(isEmpty?' slot-empty':'')+(isWinner?' win-text':'')});
    t.textContent=text;
    g.appendChild(rect); g.appendChild(t);
    return g;
  }

  const titleFor=(i,total)=> i===total-1?'Final' : (i===total-2?'Semifinal' : (i===0?'Ronda 1':'Ronda '+(i+1)));

  // Fallback sin partidos
  if(!rounds.length){
    const names = fallbackSeed.length ? fallbackSeed : ['Sin participante','Sin participante','Sin participante','Sin participante'];
    const n = Math.max(4, Math.pow(2, Math.ceil(Math.log2(names.length))));
    while(names.length<n) names.push('Sin participante');

    const ROUNDS = Math.log2(n)|0;
    const COL_W = BOX_W + COL_GAP;
    const STEP0 = (BOX_H*2 + V_GAP);
    const centerY = (r,i)=> M_TOP + (i + 0.5) * (STEP0 * Math.pow(2, r));
    const pairGap = (r)=> r===0 ? V_GAP_R1 : V_GAP;

    const MATCHES_R0 = n/2;
    const width  = M_LEFT + (ROUNDS+1)*COL_W + BOX_W + 40;
    const height = centerY(0, MATCHES_R0-1) + BOX_H + 60;
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
      const matches = n / Math.pow(2, r+1);
      const x = M_LEFT + r*COL_W;
      const g = pairGap(r);

      for(let m=0;m<matches;m++){
        const mid = centerY(r, m);
        const aY  = mid - (BOX_H + g/2);
        const bY  = mid + (g/2);

        const aTxt = (r===0 ? names[m*2] : 'Clasificado');
        const bTxt = (r===0 ? names[m*2+1] : 'Clasificado');

        svg.appendChild(drawBox(x,aY,aTxt,true,false));
        svg.appendChild(drawBox(x,bY,bTxt,true,false));

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
          svg.appendChild(drawBox(champX, mid - BOX_H/2, 'Ganador', true, false));
        }
      }
    }
    return;
  }

  // Con partidos reales
  const ROUNDS = rounds.length;
  const COL_W = BOX_W + COL_GAP;
  const STEP0 = (BOX_H*2 + V_GAP);
  const pairGap = (r)=> r===0 ? V_GAP_R1 : V_GAP;

  const matchesR1 = rounds[0].length;
  const centerY = (r,i)=> M_TOP + (i + 0.5) * (STEP0 * Math.pow(2, r));

  const width  = M_LEFT + (ROUNDS+1)*COL_W + BOX_W + 40;
  const height = centerY(0, matchesR1-1) + BOX_H + 80;
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
    const matches = rounds[r].length;
    const x = M_LEFT + r*COL_W;
    const g = pairGap(r);

    for(let m=0;m<matches;m++){
      const match = rounds[r][m];
      const mid = centerY(r, m);
      const aY  = mid - (BOX_H + g/2);
      const bY  = mid + (g/2);

      const j1 = (match.j1_nombre && match.j1_nombre.trim()) ? match.j1_nombre : (match.jugador1_id ? ('#'+match.jugador1_id) : EMPTY);
      const j2 = (match.j2_nombre && match.j2_nombre.trim()) ? match.j2_nombre : (match.jugador2_id ? ('#'+match.jugador2_id) : EMPTY);

      const isEmptyA = (j1===EMPTY);
      const isEmptyB = (j2===EMPTY);

      const winId = match.ganador_id ? parseInt(match.ganador_id,10) : 0;
      const winA = winId && match.jugador1_id && (winId === parseInt(match.jugador1_id,10));
      const winB = winId && match.jugador2_id && (winId === parseInt(match.jugador2_id,10));

      svg.appendChild(drawBox(x, aY, j1, isEmptyA, !!winA));
      svg.appendChild(drawBox(x, bY, j2, isEmptyB, !!winB));

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
        const champTxt = champLabel || 'Ganador';
        svg.appendChild(drawBox(champX, mid - BOX_H/2, champTxt, false, true));
      }
    }
  }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
