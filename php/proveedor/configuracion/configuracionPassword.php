<?php
// php/proveedor/configuracion/configuracionPassword.php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'proveedor') {
  header("Location: ../login.php"); exit();
}
$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;
?>
<div class="section">
  <div class="section-header">
    <h2>Cambiar contraseña</h2>
    <div style="margin-left:auto"><a class="btn-add" style="text-decoration: none; font-size: 16px;" href="configuracion.php">Volver</a></div>
  </div>

  <?php if ($ok): ?>
    <div style="padding:10px;margin-bottom:15px;border-radius:8px;background:#e1f7e1;color:#2e7d32;">
      <?= htmlspecialchars($ok) ?>
    </div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div style="padding:10px;margin-bottom:15px;border-radius:8px;background:#fdecea;color:#c62828;">
      <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <div class="form-container" style="max-width:600px;">
    <form action="configuracionPasswordAction.php" method="POST" id="frmPwd" novalidate>
      <input type="hidden" name="action" value="change_password">

      <label>Contraseña actual</label>
      <div class="pwd-wrap" id="wrap-old">
        <input type="password" name="old_password" id="old_password" required minlength="6" autocomplete="current-password">
        <button type="button" class="pwd-toggle" id="toggle-old" aria-label="Mostrar/ocultar" aria-pressed="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle class="eye" cx="12" cy="12" r="3"/>
            <path class="slash" d="M3 3L21 21"/>
          </svg>
        </button>
      </div>

      <label>Nueva contraseña</label>
      <div class="pwd-wrap" id="pwdWrap">
        <input type="password" name="new_password" id="pwd" placeholder="Mínimo 10, con mayúscula, minúscula, dígito y símbolo" required autocomplete="new-password">
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

      <label>Repetir nueva contraseña</label>
      <div class="pwd-wrap" id="pwdWrap2">
        <input type="password" name="confirm_password" id="pwd2" placeholder="Repite la contraseña" required autocomplete="new-password">
        <button type="button" class="pwd-toggle" id="togglePwd2" aria-label="Mostrar/ocultar repetición" aria-pressed="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle class="eye" cx="12" cy="12" r="3"/>
            <path class="slash" d="M3 3L21 21"/>
          </svg>
        </button>
      </div>
      <div class="hint bad" id="matchHint" style="display:none; margin-bottom: 10px;">Las contraseñas no coinciden</div>

      <button type="submit" id="btnSubmit" class="btn-add" style="margin-top:15px;" disabled>Guardar nueva contraseña</button>
    </form>
  </div>
</div>

<style>
  :root{ --eye-size: 20px; --eye-gap: 8px; --eye-btn: calc(var(--eye-size) + 12px); }
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
  .eye{opacity:1; transition:opacity .18s ease}
  .slash{opacity:0; transform-origin:center; transform:rotate(8deg); transition:opacity .18s ease, transform .18s ease}
  .off .eye{opacity:0.9;}
  .off .slash{opacity:1; transform:rotate(0deg);}
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
  input[type="password"]{ margin-bottom:5px !important; }
  .meter{height:8px;border-radius:6px;background:#eee;overflow:hidden;margin-top:6px}
  .meter>div{height:100%;width:0%;transition:width .2s}
  .req-list{font-size:12px;margin:6px 0 10px 0;padding-left:18px}
  .req-list li{margin:2px 0}
  .ok{color:#166534} .bad{color:#991b1b}
</style>

<script>
(function(){
  const form = document.getElementById('frmPwd');
  const old  = document.getElementById('old_password');
  const pwd  = document.getElementById('pwd');
  const pwd2 = document.getElementById('pwd2');
  const btn  = document.getElementById('btnSubmit');

  const rLen= document.getElementById('r-len');
  const rUp = document.getElementById('r-up');
  const rLo = document.getElementById('r-lo');
  const rDi = document.getElementById('r-di');
  const rSy = document.getElementById('r-sy');
  const bar = document.getElementById('meterBar');
  const mHint=document.getElementById('matchHint');
  const tip = document.getElementById('pwdTip');
  const wrap= document.getElementById('pwdWrap');

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
      'Evitá palabras comunes o secuencias (123, qwerty).',
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
    tip.innerHTML = buildTip(t);
    btn.disabled = !(strong && match && old.value.length >= 1);
    return { strong, match };
  }

  ['input','change','keyup','blur','focus'].forEach(ev=>{
    [old, pwd, pwd2].forEach(el=> el.addEventListener(ev, validate));
  });

  form.addEventListener('submit', (e)=>{
    const { strong, match } = validate();
    if (!strong || !match) {
      e.preventDefault();
      wrap.classList.add('invalid');
      pwd.focus({preventScroll:true});
      if (match && tip.innerHTML.trim()==='') tip.innerHTML = 'La contraseña no cumple la política.';
      if (!match) { mHint.style.display='block'; }
    }
  });

  validate();

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
      if (wrapEl) wrapEl.classList.remove('invalid');
      if (tipEl) { tipEl.style.opacity=''; tipEl.innerHTML=''; }
    });

    apply(false);
  }
  setupToggle('old_password','toggle-old','wrap-old', null);
  setupToggle('pwd','togglePwd','pwdWrap','pwdTip');
  setupToggle('pwd2','togglePwd2','pwdWrap2', null);
})();
</script>
<?php include '../includes/footer.php'; ?>
