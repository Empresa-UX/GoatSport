<?php
/* ================================================================
 * resetPassword.php — Restablecer contraseña (política fuerte + hash)
 * Totalmente funcional con tu esquema actual.
 * ================================================================ */
session_start();
include("config.php");

/* Debe venir de verifyCode: */
if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
  header("Location: forgot.php");
  exit();
}

$mensaje = '';

/* ---- Política de contraseñas ---- */
function is_strong_password(string $pwd): bool {
  if (strlen($pwd) < 10) return false;
  $hasUpper = preg_match('/[A-Z]/', $pwd);
  $hasLower = preg_match('/[a-z]/', $pwd);
  $hasDigit = preg_match('/\d/',   $pwd);
  $hasSym   = preg_match('/[^A-Za-z0-9]/', $pwd);
  if (!($hasUpper && $hasLower && $hasDigit && $hasSym)) return false;

  // Evitar patrones comunes
  $low = strtolower($pwd);
  $black = ['password','passw0rd','admin','qwerty','letmein','iloveyou','123456','123456789','12345678','abc123','111111','000000'];
  foreach ($black as $b) if (strpos($low, $b) !== false) return false;

  return true;
}

/* ---- Verificar que el registro de reset sigue vigente para ese usuario ---- */
function reset_record_is_valid(mysqli $conn, int $reset_id, int $user_id): bool {
  $sql = "SELECT 1 FROM password_resets WHERE id=? AND usado=0 AND user_id=? LIMIT 1";
  if ($st = $conn->prepare($sql)) {
    $st->bind_param("ii", $reset_id, $user_id);
    $st->execute();
    $ok = (bool)$st->get_result()->fetch_row();
    $st->close();
    return $ok;
  }
  return false;
}

/* ---- POST ---- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $password  = trim($_POST['password']  ?? '');
  $confirmar = trim($_POST['confirmar'] ?? '');

  if ($password === '' || $confirmar === '') {
    $mensaje = "<p class='error'>⚠️ Completá ambos campos.</p>";
  } elseif ($password !== $confirmar) {
    $mensaje = "<p class='error'>⚠️ Las contraseñas no coinciden.</p>";
  } elseif (!is_strong_password($password)) {
    $mensaje = "<p class='error'>⚠️ La contraseña no cumple la política (mín. 10, con mayúscula, minúscula, dígito y símbolo; evitá contraseñas comunes).</p>";
  } else {
    $user_id  = (int)($_SESSION['reset_user'] ?? 0);
    $reset_id = (int)($_SESSION['reset_id']  ?? 0);

    if ($user_id <= 0 || $reset_id <= 0) {
      $mensaje = "<p class='error'>⚠️ Sesión de restablecimiento inválida. Reintentá el proceso.</p>";
    } elseif (!reset_record_is_valid($conn, $reset_id, $user_id)) {
      $mensaje = "<p class='error'>⚠️ Este enlace de restablecimiento ya fue utilizado o no es válido.</p>";
    } else {
      // Hashear y guardar en usuarios.contrasenia
      $hash = password_hash($password, PASSWORD_DEFAULT);

      $upd = $conn->prepare("UPDATE usuarios SET contrasenia = ? WHERE user_id = ? LIMIT 1");
      $upd->bind_param("si", $hash, $user_id);
      $okU = $upd->execute();
      $upd->close();

      if (!$okU) {
        $mensaje = "<p class='error'>⚠️ No se pudo actualizar la contraseña. Intentá nuevamente.</p>";
      } else {
        // Marcar token como usado (sin usado_en)
        $mark = $conn->prepare("UPDATE password_resets SET usado = 1 WHERE id = ? LIMIT 1");
        $mark->bind_param("i", $reset_id);
        $mark->execute();
        $mark->close();

        // Limpiar solo claves del flujo de reset
        unset($_SESSION['reset_verified'], $_SESSION['reset_user'], $_SESSION['reset_id']);

        header("Location: login.php?reset=success");
        exit();
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablecer Contraseña</title>
  <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
  <style>
    :root{
      --bg-1:#054a56; --bg-2:#1bab9d; --card:#ffffff; --ink:#0f172a; --muted:#64748b;
      --primary:#1bab9d; --ring: rgba(27,171,157,.18); --danger:#b80000; --danger-bg:#ffe6e6;
      --ok:#15803d; --shadow: 0 12px 30px rgba(2,20,31,.18); --radius:16px;
    }
    *{box-sizing:border-box} html,body{height:100%}
    body{
      margin:0; display:grid; place-items:center; padding:24px; color:var(--ink);
      background:
        radial-gradient(1200px 600px at 20% -10%, rgba(255,255,255,.06), transparent 60%),
        linear-gradient(135deg, var(--bg-1), var(--bg-2));
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    }
    .wrap{width:100%; max-width:440px}
    .brand{display:flex;flex-direction:column;align-items:center;margin-bottom:12px}
    .brand img{width:160px; filter:drop-shadow(0 6px 18px rgba(0,0,0,.15))}
    .card{background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); padding:26px 24px;}
    h1{margin:4px 0 16px; font-size:26px; line-height:1.1; text-align:center; color:#043b3d;}
    .error{color:var(--danger); background:var(--danger-bg); padding:10px 12px; border-radius:10px; font-size:14px; margin-bottom:12px;}
    .field{margin-bottom:14px}
    .label{display:block; font-size:12px; font-weight:700; color:#586168; margin-bottom:6px}
    .input-wrap{position:relative}
    .input{
      width:100%; padding:12px 44px 12px 12px; font-size:15px; border:1px solid #d6dadd;
      border-radius:12px; background:#f9f9fb; transition:border-color .2s, box-shadow .2s, background .2s; outline:none;
    }
    .input:focus{background:#fff; border-color:var(--primary); box-shadow:0 0 0 4px var(--ring);}
    .toggle{position:absolute; right:8px; top:50%; transform:translateY(-50%); width:36px; height:36px;
      display:grid; place-items:center; border-radius:8px; border:0; background:transparent; cursor:pointer;}
    .toggle:hover{background:#f1f5f9} .toggle svg{width:18px;height:18px;color:#334155}
    .reqs{margin:8px 0 4px; padding-left:18px; font-size:13px; color:var(--muted)}
    .reqs li{margin:3px 0} .reqs .ok{color:var(--ok)} .reqs .bad{color:#b91c1b}
    .meter{height:8px; background:#eef2f7; border-radius:999px; overflow:hidden; margin:10px 0 4px}
    .meter>div{height:100%; width:0%; background:linear-gradient(90deg,#fda4af,#fde68a,#93c5fd,#86efac); transition:width .25s}
    .btn{width:100%; border:0; border-radius:12px; padding:12px 14px; font-weight:700; color:#fff; background:var(--primary);
      cursor:pointer; transition:filter .15s, transform .02s, box-shadow .2s}
    .btn:hover{filter:brightness(.98)} .btn:active{transform:translateY(1px)} .btn:disabled{opacity:.6; cursor:default}
    .link{display:block; text-align:center; margin-top:10px; color:var(--primary); text-decoration:none; font-weight:600; font-size:14px;}
    .link:hover{text-decoration:underline}
    @media (max-width:480px){ .card{padding:20px 18px} }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="brand"><img src="/img/logotipo.png" alt="GOAT Sports"></div>

    <div class="card">
      <h1>Restablecer<br>Contraseña</h1>

      <?= $mensaje ?>

      <form method="POST">
        <div class="field">
          <label class="label" for="pwd">Nueva contraseña</label>
          <div class="input-wrap">
            <input class="input" type="password" name="password" id="pwd" placeholder="Mínimo 10, con A-z, 0-9 y símbolo" required>
            <button type="button" class="toggle" id="togglePwd" aria-label="Mostrar/ocultar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="field" style="margin-top:10px">
          <label class="label" for="pwd2">Confirmar contraseña</label>
          <div class="input-wrap">
            <input class="input" type="password" name="confirmar" id="pwd2" placeholder="Repetí la contraseña" required>
            <button type="button" class="toggle" id="togglePwd2" aria-label="Mostrar/ocultar confirmación">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <ul class="reqs" id="reqs">
          <li id="r-len" class="bad">Mínimo 10 caracteres</li>
          <li id="r-up"  class="bad">Al menos 1 mayúscula (A-Z)</li>
          <li id="r-lo"  class="bad">Al menos 1 minúscula (a-z)</li>
          <li id="r-di"  class="bad">Al menos 1 dígito (0-9)</li>
          <li id="r-sy"  class="bad">Al menos 1 símbolo</li>
        </ul>
        <div class="meter" aria-hidden="true"><div id="meterBar"></div></div>

        <button type="submit" class="btn" id="btnSubmit" disabled>Guardar contraseña</button>
      </form>

      <a href="login.php" class="link">Volver al login</a>
    </div>
  </div>

  <script>
    // Toggle ojos
    (function(){
      const mkToggle=(inputId, btnId)=>{
        const i=document.getElementById(inputId), b=document.getElementById(btnId);
        if(!i||!b) return;
        b.addEventListener('click', e=>{
          e.preventDefault();
          i.type = i.type==='password' ? 'text' : 'password';
        });
      };
      mkToggle('pwd','togglePwd');
      mkToggle('pwd2','togglePwd2');
    })();
  </script>

  <script>
    // Medidor y requisitos
    (function(){
      const pwd = document.getElementById('pwd');
      const pwd2= document.getElementById('pwd2');
      const btn = document.getElementById('btnSubmit');
      const bar = document.getElementById('meterBar');
      const rLen= document.getElementById('r-len');
      const rUp = document.getElementById('r-up');
      const rLo = document.getElementById('r-lo');
      const rDi = document.getElementById('r-di');
      const rSy = document.getElementById('r-sy');

      const tests = s => ({
        len: (s||'').length >= 10,
        up:  /[A-Z]/.test(s),
        lo:  /[a-z]/.test(s),
        di:  /\d/.test(s),
        sy:  /[^A-Za-z0-9]/.test(s)
      });
      const render = (ok, el) => el.className = ok ? 'ok' : 'bad';
      const score  = t => {
        let n=0; ['len','up','lo','di','sy'].forEach(k=>{ if(t[k]) n++; });
        const p=(n/5)*100;
        bar.style.width = p+'%';
        bar.style.background = n<=2 ? '#fca5a5' : (n===3?'#fde68a':(n===4?'#93c5fd':'#86efac'));
        return n>=5;
      };

      function validate(){
        const t = tests(pwd.value||'');
        render(t.len,rLen); render(t.up,rUp); render(t.lo,rLo); render(t.di,rDi); render(t.sy,rSy);
        const strong = score(t);
        const match  = pwd.value!=='' && pwd.value===pwd2.value;
        btn.disabled = !(strong && match);
        return {strong,match};
      }
      ['input','change','keyup'].forEach(ev=>{
        pwd.addEventListener(ev, validate);
        pwd2.addEventListener(ev, validate);
      });
      validate();
    })();
  </script>
</body>
</html>
