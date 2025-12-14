<?php
/* =====================================================================
 * file: php/recepcionista/clientes/clientesForm.php
 * COMPLETO: reemplaza el archivo con esto
 * ===================================================================== */
?>
<style>
  :root{
    --eye-size: 10px;       /* cambia este valor global o por campo */
    --eye-gap:  8px;        /* separación entre texto y botón ojo */
    --eye-btn:  calc(var(--eye-size) + 12px); /* botón = icono + padding */
  }

  .form-actions{display:flex;gap:10px;align-items:center;margin-top:8px}
  .btn-add.btn-outline{background:#fff !important;color:#043b3d !important;border:1px solid #ccc !important;}
  .btn-add.btn-outline:hover{background:#f7f7f7 !important;}
  .btn-add, .btn-add.btn-outline { display:inline-block; text-decoration:none; }

  .hint{font-size:12px;color:#6b7280;margin-top:4px}
  .hint.bad{color:#b91c1c}
  .req-list{font-size:12px;margin:6px 0 0 0;padding-left:18px}
  .req-list li{margin:2px 0}
  .ok{color:#166534} .bad{color:#991b1b}
  .meter{height:8px;border-radius:6px;background:#eee;overflow:hidden;margin-top:6px}
  .meter>div{height:100%;width:0%;transition:width .2s}

  /* === Toggle ojo (tamaño configurable) === */
  .pwd-wrap{position:relative;display:block}
  .pwd-wrap input{ padding-right: calc(var(--eye-btn) + var(--eye-gap)); }
  .pwd-toggle{
    position:absolute; right:6px; top:50%; transform:translateY(-50%);
    display:inline-flex; align-items:center; justify-content:center;
    width:var(--eye-btn); height:var(--eye-btn);
    border-radius:8px; border:0; background:transparent; cursor:pointer;
    transition:background .15s ease;
  }
  .pwd-toggle:hover{background:#f3f4f6}
  .pwd-toggle svg{width:var(--eye-size); height:var(--eye-size); transition:transform .18s ease}
  .pwd-toggle[aria-pressed="false"] svg{ transform:scale(1); margin-bottom: 6px; }
  .pwd-toggle[aria-pressed="true"]  svg{ transform:scale(1.04); margin-bottom: 6px;}

  /* Tachado */
  .eye{opacity:1; transition:opacity .18s ease}
  .slash{opacity:0; transform-origin:center; transform:rotate(8deg); transition:opacity .18s ease, transform .18s ease}
  .off .eye{opacity:0.9;}
  .off .slash{opacity:1; transform:rotate(0deg);}

  /* === Tooltip recomendaciones (solo al enviar si falla) === */
  .pwd-tip{
    position:absolute; right:calc(var(--eye-btn) + 10px); top:50%; transform:translateY(-50%);
    background:#111827; color:#fff; font-size:12px; line-height:1.3;
    padding:8px 10px; border-radius:8px; max-width:280px; box-shadow:0 8px 20px rgba(0,0,0,.18);
    opacity:0; pointer-events:none; transition:opacity .15s ease, transform .15s ease;
    transform-origin:right center; transform:translateY(-50%) scale(.98);
    z-index:2;
  }
  .pwd-tip::after{
    content:""; position:absolute; right:-6px; top:50%; transform:translateY(-50%) rotate(45deg);
    width:10px; height:10px; background:#111827;
  }
  .pwd-wrap.invalid .pwd-tip{ opacity:1; pointer-events:auto; transform:translateY(-50%) scale(1); }
  @media (max-width:560px){
    .pwd-tip{ left:0; right:auto; top:calc(100% + 6px); transform:none }
    .pwd-tip::after{ display:none }
  }
  input[type="password"] {
    margin-bottom: 5px !important;
  }
</style>

<form method="POST" action="clientesAction.php" id="clienteForm" novalidate>
  <label>Nombre:</label>
  <input type="text" name="nombre" placeholder="Nombre y apellido" required>

  <label>Email:</label>
  <input type="email" name="email" placeholder="correo@ejemplo.com" required>

  <label>Contraseña:</label>
  <div class="pwd-wrap" id="pwdWrap" style="--eye-size: var(--eye-size);">
    <input type="password" name="password" id="pwd" placeholder="Mínimo 10, con mayúscula, minúscula, dígito y símbolo" required>
    <button type="button" class="pwd-toggle" id="togglePwd" aria-label="Mostrar/ocultar contraseña" aria-pressed="false">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
        <circle class="eye" cx="12" cy="12" r="3"/>
        <path class="slash" d="M3 3L21 21"/>
      </svg>
    </button>
    <div class="pwd-tip" id="pwdTip" role="alert" aria-live="polite"></div>
  </div>

  <div class="meter" aria-hidden="true"><div id="meterBar"></div></div>
  <ul class="req-list" id="reqs">
    <li id="r-len" class="bad">Mínimo 10 caracteres</li>
    <li id="r-up"  class="bad">Al menos 1 mayúscula (A-Z)</li>
    <li id="r-lo"  class="bad">Al menos 1 minúscula (a-z)</li>
    <li id="r-di"  class="bad">Al menos 1 dígito (0-9)</li>
    <li id="r-sy"  class="bad">Al menos 1 símbolo</li>
  </ul>

  <label>Repetir contraseña:</label>
  <div class="pwd-wrap" id="pwdWrap2" style="--eye-size: var(--eye-size);">
    <input type="password" name="password_confirm" id="pwd2" placeholder="Repite la contraseña" required>
    <button type="button" class="pwd-toggle" id="togglePwd2" aria-label="Mostrar/ocultar repetición" aria-pressed="false">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
        <circle class="eye" cx="12" cy="12" r="3"/>
        <path class="slash" d="M3 3L21 21"/>
      </svg>
    </button>
  </div>
  <div class="hint bad" id="matchHint" style="display:none; margin-bottom: 10px;">Las contraseñas no coinciden</div>

  <label>Teléfono (opcional):</label>
  <input type="text" name="telefono" placeholder="Ej: 11 5555-5555">

  <div class="form-actions">
    <button type="submit" class="btn-add" id="btnSubmit" disabled>Crear cliente</button>
    <a href="/php/recepcionista/home_recepcionista.php" class="btn-add btn-outline">Cancelar</a>
  </div>
</form>

<script>
(function(){
  const pwd = document.getElementById('pwd');
  const pwd2= document.getElementById('pwd2');
  const btn = document.getElementById('btnSubmit');
  const rLen= document.getElementById('r-len');
  const rUp = document.getElementById('r-up');
  const rLo = document.getElementById('r-lo');
  const rDi = document.getElementById('r-di');
  const rSy = document.getElementById('r-sy');
  const bar = document.getElementById('meterBar');
  const mHint=document.getElementById('matchHint');
  const tip = document.getElementById('pwdTip');
  const wrap= document.getElementById('pwdWrap');
  const form= document.getElementById('clienteForm');

  const tests = s => ({
    len: (s||'').length >= 10,
    up:  /[A-Z]/.test(s),
    lo:  /[a-z]/.test(s),
    di:  /\d/.test(s),
    sy:  /[^A-Za-z0-9]/.test(s)
  });

  function renderReq(ok, li){ li.className = ok ? 'ok' : 'bad'; }
  function scoreState(t){
    let n = 0; for (const k of ['len','up','lo','di','sy']) if (t[k]) n++;
    const pct = (n/5)*100;
    bar.style.width = pct + '%';
    bar.style.background = n<=2 ? '#fca5a5' : (n===3? '#fde68a' : (n===4? '#93c5fd' : '#86efac'));
    return n>=5;
  }

  function buildTip(t){
    const missing = [];
    if(!t.len) missing.push('≥ 10 caracteres');
    if(!t.up)  missing.push('una MAYÚSCULA');
    if(!t.lo)  missing.push('una minúscula');
    if(!t.di)  missing.push('un número');
    if(!t.sy)  missing.push('un símbolo');
    if (missing.length === 0) return '';
    const recs = [
      'Evita palabras comunes o secuencias (123, qwerty).',
      'Usá frase con símbolos (ej: Mate!2025#Club).'
    ];
    return `<strong>Mejorá tu contraseña:</strong><br>Falta: ${missing.join(', ')}.<br>${recs.join(' ')}`;
  }

  function validate(){
    const t = tests(pwd.value);
    renderReq(t.len, rLen); renderReq(t.up, rUp); renderReq(t.lo, rLo); renderReq(t.di, rDi); renderReq(t.sy, rSy);
    const strong = scoreState(t);
    const match = pwd.value !== '' && pwd.value === pwd2.value;
    mHint.style.display = match ? 'none' : (pwd2.value ? 'block' : 'none');

    // Actualizamos el contenido del tip, pero NO lo mostramos en foco/click
    tip.innerHTML = buildTip(t);

    btn.disabled = !(strong && match);
    return { strong, match };
  }

  ['input','change','keyup','blur','focus'].forEach(ev=>{
    pwd.addEventListener(ev, validate); pwd2.addEventListener(ev, validate);
  });

  /* Mostrar tooltip SOLO al enviar si no cumple */
  form.addEventListener('submit', (e)=>{
    const { strong, match } = validate();
    if (!strong || !match) {
      e.preventDefault();
      // ahora sí mostramos el tip y enfocamos
      wrap.classList.add('invalid');
      pwd.focus({preventScroll:true});
      if (match && tip.innerHTML.trim()==='') tip.innerHTML = 'La contraseña no cumple la política.'; // fallback
      if (!match) { document.getElementById('matchHint').style.display='block'; }
    }
  });

  validate();

  /* Toggle mostrar/ocultar SIN mostrar tooltip ni cambiar foco */
  function setupToggle(inputId, btnId, wrapId, tipId){
    const input = document.getElementById(inputId);
    const btn   = document.getElementById(btnId);
    const wrapEl= wrapId ? document.getElementById(wrapId) : null;
    const tipEl = tipId ? document.getElementById(tipId)   : null;
    const svg   = btn.querySelector('svg');

    const apply = (show)=>{
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      svg.classList.toggle('off', !show);
    };

    btn.addEventListener('click', (ev)=>{
      ev.preventDefault();
      const show = input.type === 'password';
      apply(show);
      // ocultar cualquier tip visible sin tocar el foco
      if (wrapEl) wrapEl.classList.remove('invalid');
      if (tipEl) { tipEl.style.opacity=''; tipEl.innerHTML=''; }
    });

    apply(false);
  }
  setupToggle('pwd','togglePwd','pwdWrap','pwdTip');
  setupToggle('pwd2','togglePwd2','pwdWrap2', null);
})();
</script>
