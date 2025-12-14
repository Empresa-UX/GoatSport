<?php
// ======================================================================
// file: php/register.php (endurecido + transacción + policy de contraseña)
// ======================================================================

/* ---------- Sesión/headers ---------- */
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
  'lifetime' => 0, 'path' => '/', 'domain' => '',
  'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax',
]);
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
if ($secure) header('Strict-Transport-Security: max-age=15552000; includeSubDomains; preload');

require_once __DIR__ . "/config.php"; // $conn (mysqli)

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_register'])) {
  $_SESSION['csrf_register'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['csrf_register'];

/* ---------- Helpers ---------- */
function normalize_email(string $e): string {
  return strtolower(trim($e));
}
function is_strong_password(string $pwd): bool {
  if (strlen($pwd) < 10) return false;
  $hasUpper = preg_match('/[A-Z]/', $pwd);
  $hasLower = preg_match('/[a-z]/', $pwd);
  $hasDigit = preg_match('/\d/',   $pwd);
  $hasSym   = preg_match('/[^A-Za-z0-9]/', $pwd);
  if (!($hasUpper && $hasLower && $hasDigit && $hasSym)) return false;
  $low = strtolower($pwd);
  $black = [
    'password','passw0rd','admin','qwerty','letmein','iloveyou',
    '123456','123456789','12345678','abc123','111111','000000'
  ];
  foreach ($black as $b) { if (strpos($low, $b) !== false) return false; }
  return true;
}

/* ---------- Estado UI ---------- */
$mensaje = '';
$ok_msg  = '';

/* ---------- POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $postedCsrf  = (string)($_POST['csrf'] ?? '');
  $nombre      = trim((string)($_POST['nombre'] ?? ''));
  $emailInput  = (string)($_POST['email'] ?? '');
  $email       = normalize_email($emailInput);
  $password    = (string)($_POST['password'] ?? '');
  $password2   = (string)($_POST['password_confirm'] ?? '');

  if (!hash_equals($_SESSION['csrf_register'], $postedCsrf)) {
    $mensaje = "<p class='error'>⚠️ Solicitud inválida. Refresca la página e intentá nuevamente.</p>";
  } else if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
    $mensaje = "<p class='error'>⚠️ Completá todos los campos.</p>";
  } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mensaje = "<p class='error'>⚠️ El email no es válido.</p>";
  } else if ($password !== $password2) {
    $mensaje = "<p class='error'>⚠️ Las contraseñas no coinciden.</p>";
  } else if (!is_strong_password($password)) {
    $mensaje = "<p class='error'>⚠️ La contraseña no cumple la política (min. 10, mayúscula, minúscula, dígito y símbolo).</p>";
  } else {
    // ¿Email ya registrado?
    if ($conn && ($check = $conn->prepare("SELECT 1 FROM usuarios WHERE email = ? LIMIT 1"))) {
      $check->bind_param("s", $email);
      $check->execute();
      $exists = (bool)$check->get_result()->fetch_row();
      $check->close();

      if ($exists) {
        $mensaje = "<p class='error'>⚠️ El correo ya está registrado.</p>";
      } else {
        // Transacción: usuarios -> cliente_detalle -> ranking -> notificación
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $rol  = 'cliente';

        $conn->begin_transaction();
        try {
          // 1) usuarios
          if (!($stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol) VALUES (?,?,?,?)"))) {
            throw new Exception("prep usuarios");
          }
          $stmt->bind_param("ssss", $nombre, $email, $hash, $rol);
          $stmt->execute();
          $cliente_id = (int)$stmt->insert_id;
          $stmt->close();

          // 2) cliente_detalle (solo fila base)
          if (!($insDet = $conn->prepare("INSERT INTO cliente_detalle (cliente_id) VALUES (?)"))) {
            throw new Exception("prep cliente_detalle");
          }
          $insDet->bind_param("i", $cliente_id);
          $insDet->execute();
          $insDet->close();

          // 3) ranking (fila base)
          if (!($insRank = $conn->prepare("INSERT INTO ranking (usuario_id) VALUES (?)"))) {
            throw new Exception("prep ranking");
          }
          $insRank->bind_param("i", $cliente_id);
          $insRank->execute();
          $insRank->close();

          // 4) Notificar admins
          $sqlN = "
            INSERT INTO notificaciones (usuario_id, tipo, origen, titulo, mensaje)
            SELECT user_id, ?, ?, ?, ? FROM usuarios WHERE rol = 'admin'
          ";
          $tipo    = 'cliente_alta';
          $origen  = 'registro_web';
          $titulo  = "Nuevo cliente #{$cliente_id}";
          $msg     = "Se registró un nuevo cliente desde el formulario público.";
          if (!($stmtN = $conn->prepare($sqlN))) {
            throw new Exception("prep notificaciones");
          }
          $stmtN->bind_param("ssss", $tipo, $origen, $titulo, $msg);
          $stmtN->execute();
          $stmtN->close();

          $conn->commit();

          // Autologin
          session_regenerate_id(true);
          $_SESSION['usuario_id']    = $cliente_id;
          $_SESSION['usuario_email'] = $email;
          $_SESSION['rol']           = 'cliente';

          header("Location: ./cliente/home_cliente.php");
          exit();
        } catch (Throwable $e) {
          $conn->rollback();
          // Duplicado u otro error
          if (strpos(strtolower($e->getMessage()), 'duplicate') !== false) {
            $mensaje = "<p class='error'>⚠️ El correo ya está registrado.</p>";
          } else {
            error_log('[register] '.$e->getMessage());
            $mensaje = "<p class='error'>⚠️ Ocurrió un error al registrar. Intentá nuevamente.</p>";
          }
        }
      }
    } else {
      $mensaje = "<p class='error'>⚠️ No se pudo preparar la consulta.</p>";
    }
  }
  if ($conn) $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
  <title>GoatSport | Registro</title>
  <style>
    * { font-family: 'Arial', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
    body { display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #054a56ff, #1bab9dff); padding: 20px; }
    .logo-container { text-align: center; margin-bottom: 10px; }
    .logo-container img { width: 160px; }

    .login-box {
      width: 100%; max-width: 460px; background: white; padding: 30px 26px;
      border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); text-align: left;
    }
    h1 { margin-bottom: 8px; font-size: 1.6rem; color: #054a56; text-align:center; }
    .subtitle { text-align:center; font-size: 0.95rem; color:#476; margin-bottom:18px; }

    .input-group { position: relative; margin-bottom: 14px; }
    .input-group input {
      width: 100%; padding: 14px 42px 14px 12px; border: 1px solid #ccc; border-radius: 10px;
      font-size: 16px; background-color: #f9f9f9; transition: border-color .25s;
    }
    .input-group input:focus { border-color: #1bab9dff; outline: none; background-color: #fff; }
    .input-group svg { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; fill: #999; }

    .pwd-wrap{ position:relative; display:block }
    :root{ --eye-size: 20px; --eye-gap: 8px; --eye-btn: calc(var(--eye-size) + 12px); }
    .pwd-wrap input{ padding-right: calc(var(--eye-btn) + var(--eye-gap)); }
    .pwd-toggle{
      position:absolute; right:6px; top:50%; transform:translateY(-50%);
      display:inline-flex; align-items:center; justify-content:center;
      width:var(--eye-btn); height:var(--eye-btn); border-radius:8px; border:0; background:transparent; cursor:pointer; transition:background .15s;
    }
    .pwd-toggle:hover{ background:#f3f4f6 }
    .pwd-toggle svg{ width:var(--eye-size); height:var(--eye-size); transition:transform .18s }
    .pwd-toggle[aria-pressed="false"] svg{ transform:scale(1); margin-bottom: 6px; }
    .pwd-toggle[aria-pressed="true"]  svg{ transform:scale(1.04); margin-bottom: 6px; }
    .eye{ opacity:1; transition:opacity .18s } .slash{ opacity:0; transform-origin:center; transform:rotate(8deg); transition:opacity .18s, transform .18s }
    .off .eye{opacity:0.9;} .off .slash{opacity:1; transform:rotate(0deg);}

    .meter { height:8px; border-radius:6px; background:#eee; overflow:hidden; margin: 6px 0 8px; }
    .meter>div { height:100%; width:0%; transition: width .2s; }

    .req-list { font-size: 12px; margin: 4px 0 12px; padding-left: 18px; }
    .req-list li { margin: 2px 0; }
    .ok { color:#166534 } .bad { color:#991b1b }

    .hint { font-size: 12px; color:#6b7280; margin-top:4px }
    .hint.bad { color:#b91c1c }

    .btn {
      width: 100%; padding: 14px; background-color: #1bab9dff; color: white; border: none;
      border-radius: 10px; font-size: 16px; cursor: pointer; transition: background .25s; margin-top: 8px; font-weight: 600;
    }
    .btn:hover { background-color: #14897f; }
    .btn:disabled { background-color: #ccc; cursor: not-allowed; }

    .extra-links { margin-top: 14px; font-size: 14px; text-align:center; }
    .extra-links a { color: #1bab9dff; text-decoration: none; }
    .extra-links a:hover { text-decoration: underline; }

    .error { color:#b00020; margin: 8px 0 10px; text-align:center; }
    .success { color:#166534; margin: 8px 0 10px; text-align:center; }
  </style>
</head>
<body>
  <div class="logo-container">
    <img src="/img/logotipo.png" alt="Logo Padel">
  </div>

  <div class="login-box">
    <h1>Registrate</h1>
    <p class="subtitle">Creá tu cuenta para reservar y sumar ranking</p>

    <?= $mensaje ? $mensaje : '' ?>
    <?= $ok_msg ? "<p class='success'>{$ok_msg}</p>" : '' ?>

    <form method="POST" id="registroForm" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>"/>

      <div class="input-group">
        <input type="text" name="nombre" placeholder="Nombre y apellido" required value="<?= isset($nombre)?htmlspecialchars($nombre,ENT_QUOTES,'UTF-8'):'' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.67 0 8 1.34 8 4v2H4v-2c0-2.66 5.33-4 8-4zm0-2a4 4 0 1 0-4-4 4 4 0 0 0 4 4z"/></svg>
      </div>

      <div class="input-group">
        <input type="email" name="email" placeholder="correo@ejemplo.com" required autocomplete="email" value="<?= isset($email)?htmlspecialchars($email,ENT_QUOTES,'UTF-8'):'' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 13.065 0 6V4l12 7 12-7v2l-12 7.065z"/></svg>
      </div>

      <label style="font-size:13px;color:#374151;margin: 4px 0 2px;display:block;">Contraseña:</label>
      <div class="input-group pwd-wrap" id="pwdWrap">
        <input type="password" name="password" id="pwd" placeholder="Mínimo 10, con mayúscula, minúscula, dígito y símbolo" required autocomplete="new-password">
        <button type="button" class="pwd-toggle" id="togglePwd" aria-label="Mostrar/ocultar contraseña" aria-pressed="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle class="eye" cx="12" cy="12" r="3"/>
            <path class="slash" d="M3 3L21 21"/>
          </svg>
        </button>
      </div>

      <div class="meter" aria-hidden="true"><div id="meterBar"></div></div>
      <ul class="req-list" id="reqs">
        <li id="r-len" class="bad">Mínimo 10 caracteres</li>
        <li id="r-up"  class="bad">Al menos 1 mayúscula (A-Z)</li>
        <li id="r-lo"  class="bad">Al menos 1 minúscula (a-z)</li>
        <li id="r-di"  class="bad">Al menos 1 dígito (0-9)</li>
        <li id="r-sy"  class="bad">Al menos 1 símbolo</li>
      </ul>

      <label style="font-size:13px;color:#374151;margin: 4px 0 2px;display:block;">Repetir contraseña:</label>
      <div class="input-group pwd-wrap" id="pwdWrap2">
        <input type="password" name="password_confirm" id="pwd2" placeholder="Repetí la contraseña" required autocomplete="new-password">
        <button type="button" class="pwd-toggle" id="togglePwd2" aria-label="Mostrar/ocultar repetición" aria-pressed="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle class="eye" cx="12" cy="12" r="3"/>
            <path class="slash" d="M3 3L21 21"/>
          </svg>
        </button>
      </div>
      <div class="hint bad" id="matchHint" style="display:none; margin-bottom: 8px;">Las contraseñas no coinciden</div>

      <button type="submit" class="btn" id="btnSubmit" disabled>Registrarme</button>
    </form>

    <div class="extra-links">
      <a href="login.php">¿Ya tenés cuenta? Iniciá sesión</a>
    </div>
  </div>

  <script>
  (function(){
    const form = document.getElementById('registroForm');
    const btn  = document.getElementById('btnSubmit');
    const pwd  = document.getElementById('pwd');
    const pwd2 = document.getElementById('pwd2');

    const rLen= document.getElementById('r-len');
    const rUp = document.getElementById('r-up');
    const rLo = document.getElementById('r-lo');
    const rDi = document.getElementById('r-di');
    const rSy = document.getElementById('r-sy');
    const bar = document.getElementById('meterBar');
    const mHint=document.getElementById('matchHint');

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
    function validate(){
      const t = tests(pwd.value);
      renderReq(t.len, rLen); renderReq(t.up, rUp); renderReq(t.lo, rLo); renderReq(t.di, rDi); renderReq(t.sy, rSy);
      const strong = scoreState(t);
      const match = pwd.value !== '' && pwd.value === pwd2.value;
      mHint.style.display = match ? 'none' : (pwd2.value ? 'block' : 'none');
      btn.disabled = !(strong && match);
      return strong && match;
    }
    ['input','change','keyup','blur','focus'].forEach(ev => {
      pwd.addEventListener(ev, validate);
      pwd2.addEventListener(ev, validate);
    });
    validate();

    function setupToggle(inputId, btnId){
      const input = document.getElementById(inputId);
      const btn   = document.getElementById(btnId);
      const svg   = btn.querySelector('svg');
      const apply = show => {
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        svg.classList.toggle('off', !show);
      };
      btn.addEventListener('click', e => { e.preventDefault(); apply(input.type === 'password'); });
      apply(false);
    }
    setupToggle('pwd','togglePwd');
    setupToggle('pwd2','togglePwd2');

    form.addEventListener('submit', (e)=>{
      if (!validate()) { e.preventDefault(); alert('Revisá la contraseña.'); }
    });
  })();
  </script>
</body>
</html>
