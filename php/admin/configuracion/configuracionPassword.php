<?php
// php/admin/configuracion/configuracionPassword.php
include '../includes/header.php';
include '../includes/sidebar.php';
include '../../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
  header("Location: ../login.php"); exit();
}
$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;
?>
<div class="section">
  <div class="section-header">
    <h2>Cambiar contraseña</h2>
    <div style="margin-left:auto;"><a class="btn-add" style="text-decoration: none; font-size: 16px;" href="configuracion.php">Volver</a></div>
  </div>

  <?php if ($ok): ?>
    <div style="padding:10px;margin-bottom:15px;border-radius:8px;background:#e1f7e1;color:#2e7d32;"><?= htmlspecialchars($ok) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div style="padding:10px;margin-bottom:15px;border-radius:8px;background:#fdecea;color:#c62828;"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <style>
    :root{ --eye-size: 20px; --eye-gap: 8px; --eye-btn: calc(var(--eye-size) + 12px); }
    .pwd-wrap{position:relative;display:block}
    .pwd-wrap input{ padding-right: calc(var(--eye-btn) + var(--eye-gap)); margin-bottom:5px!important; }
    .pwd-toggle{ position:absolute; right:6px; top:50%; transform:translateY(-50%);
      display:inline-flex; align-items:center; justify-content:center; width:var(--eye-btn); height:var(--eye-btn);
      border-radius:8px; border:0; background:transparent; cursor:pointer; transition:background .15s ease;}
    .pwd-toggle:hover{background:#f3f4f6}
    .pwd-toggle svg{width:var(--eye-size); height:var(--eye-size);}
    .eye{opacity:1} .slash{opacity:0; transform:rotate(8deg)}
    .off .eye{opacity:.9;} .off .slash{opacity:1; transform:rotate(0)}
    .meter{height:8px;border-radius:6px;background:#eee;overflow:hidden;margin-top:6px}
    .meter>div{height:100%;width:0%;transition:width .2s}
    .req-list{font-size:12px;margin:6px 0 0 0;padding-left:18px}
    .req-list li{margin:2px 0}
    .ok{color:#166534} .bad{color:#991b1b}
    .hint.bad{font-size:12px;color:#b91c1c;margin-top:4px}
  </style>

  <div class="form-container" style="max-width:600px;">
    <form action="configuracionPasswordAction.php" method="POST" id="pwdForm" novalidate>
      <input type="hidden" name="action" value="change_password">

      <label>Contraseña actual</label>
      <div class="pwd-wrap">
        <input type="password" name="old_password" id="oldPwd" required autocomplete="current-password">
        <button type="button" class="pwd-toggle" id="tOld" aria-pressed="false" aria-label="Mostrar/ocultar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle class="eye" cx="12" cy="12" r="3"/><path class="slash" d="M3 3L21 21"/>
          </svg>
        </button>
      </div>

      <label>Nueva contraseña</label>
      <div class="pwd-wrap" id="wrapNew">
        <input type="password" name="new_password" id="newPwd" required autocomplete="new-password"
               placeholder="Mínimo 10, con mayúscula, minúscula, dígito y símbolo">
        <button type="button" class="pwd-toggle" id="tNew" aria-pressed="false" aria-label="Mostrar/ocultar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle class="eye" cx="12" cy="12" r="3"/><path class="slash" d="M3 3L21 21"/>
          </svg>
        </button>
      </div>

      <div class="meter" aria-hidden="true"><div id="meterBar"></div></div>
      <ul class="req-list">
        <li id="r-len" class="bad">Mínimo 10 caracteres</li>
        <li id="r-up"  class="bad">Al menos 1 mayúscula (A-Z)</li>
        <li id="r-lo"  class="bad">Al menos 1 minúscula (a-z)</li>
        <li id="r-di"  class="bad">Al menos 1 dígito (0-9)</li>
        <li id="r-sy"  class="bad">Al menos 1 símbolo</li>
      </ul>

      <label>Repetir nueva contraseña</label>
      <div class="pwd-wrap">
        <input type="password" name="confirm_password" id="newPwd2" required autocomplete="new-password">
        <button type="button" class="pwd-toggle" id="tNew2" aria-pressed="false" aria-label="Mostrar/ocultar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle class="eye" cx="12" cy="12" r="3"/><path class="slash" d="M3 3L21 21"/>
          </svg>
        </button>
      </div>
      <div class="hint bad" id="matchHint" style="display:none;">Las contraseñas no coinciden</div>

      <button type="submit" class="btn-add" id="btnSubmit" style="margin-top:15px;" disabled>Guardar nueva contraseña</button>
    </form>
  </div>
</div>

<script>
(function(){
  const np = document.getElementById('newPwd');
  const np2= document.getElementById('newPwd2');
  const bar= document.getElementById('meterBar');
  const mH = document.getElementById('matchHint');
  const btn= document.getElementById('btnSubmit');

  const tests = s => ({
    len: (s||'').length >= 10,
    up:  /[A-Z]/.test(s),
    lo:  /[a-z]/.test(s),
    di:  /\d/.test(s),
    sy:  /[^A-Za-z0-9]/.test(s)
  });
  const setLi = (id, ok)=>{ document.getElementById(id).className = ok ? 'ok':'bad'; };
  function score(t){
    let n=0; ['len','up','lo','di','sy'].forEach(k=>{ if (t[k]) n++; });
    const pct=(n/5)*100; bar.style.width = pct+'%';
    bar.style.background = n<=2 ? '#fca5a5' : (n===3? '#fde68a' : (n===4? '#93c5fd' : '#86efac'));
    return n>=5;
  }
  function validate(){
    const t=tests(np.value);
    setLi('r-len',t.len); setLi('r-up',t.up); setLi('r-lo',t.lo); setLi('r-di',t.di); setLi('r-sy',t.sy);
    const strong=score(t);
    const match=np.value!=='' && np.value===np2.value;
    mH.style.display = match ? 'none' : (np2.value ? 'block':'none');
    btn.disabled = !(strong && match);
  }
  ['input','change','keyup'].forEach(ev=>{ np.addEventListener(ev,validate); np2.addEventListener(ev,validate); });
  validate();

  function toggle(btnId, inputId){
    const b=document.getElementById(btnId), i=document.getElementById(inputId), svg=b.querySelector('svg');
    const apply=(show)=>{ i.type = show?'text':'password'; b.setAttribute('aria-pressed', show?'true':'false'); svg.classList.toggle('off', !show); };
    b.addEventListener('click', e=>{ e.preventDefault(); apply(i.type==='password'); });
    apply(false);
  }
  toggle('tOld','oldPwd'); toggle('tNew','newPwd'); toggle('tNew2','newPwd2');
})();
</script>

<?php include '../includes/footer.php'; ?>
