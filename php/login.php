<?php
// ======================================================================
// file: php/login.php (con hCaptcha funcional y desafío visual)
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

/* ---------- Config CAPTCHA (TUS CLAVES REALES) ---------- */
const CAPTCHA_SITE_KEY   = '372a8672-8b30-4ce8-8f10-4a2008963212';
const CAPTCHA_SECRET_KEY = 'ES_09d4af5a8e994e9a81a2904766c497b9';

const LOGIN_WINDOW_MIN    = 15;
const LOGIN_MAX_INTENTOS  = 6;
const LOGIN_LOCK_MIN      = 15;
const LOGIN_PURGE_DAYS    = 7;
const GENERIC_ERR         = 'Credenciales inválidas o acceso temporalmente bloqueado.';

const LOGIN_CAPTCHA_AFTER = 3; // mostrar CAPTCHA desde el intento N

/* ---------- Tabla intentos (idempotente) ---------- */
if ($conn) {
  $conn->query("
    CREATE TABLE IF NOT EXISTS login_intentos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(190) NULL,
      ip VARCHAR(45) NOT NULL,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      exito TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
}

/* ---------- Redirección si ya hay sesión ---------- */
if (isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
  switch ($_SESSION['rol']) {
    case 'admin':        header("Location: ./admin/home_admin.php"); exit;
    case 'proveedor':    header("Location: ./proveedor/home_proveedor.php"); exit;
    case 'recepcionista':header("Location: ./recepcionista/home_recepcionista.php"); exit;
    case 'cliente':
    default:             header("Location: ./cliente/home_cliente.php"); exit;
  }
}

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_login'])) $_SESSION['csrf_login'] = bin2hex(random_bytes(16));
$csrf_token = $_SESSION['csrf_login'];

/* ---------- Helpers ---------- */
function client_ip(): string {
  $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  if (strpos($ip, ',') !== false) { $ip = trim(explode(',', $ip)[0]); }
  return $ip;
}

function verify_hcaptcha(string $token, string $ip): array {
  $url = 'https://hcaptcha.com/siteverify';
  $data = [
    'secret'   => CAPTCHA_SECRET_KEY,
    'response' => $token,
    'remoteip' => $ip,
  ];

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
  ]);
  
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($response === false || $httpCode !== 200) {
    return ['success' => false, 'error' => 'captcha_connection_failed'];
  }

  $result = json_decode($response, true);
  return is_array($result) ? $result : ['success' => false, 'error' => 'invalid_response'];
}

/* ---------- Estado UI ---------- */
$error = null;
$needCaptcha = false;
$captchaError = null;

/* ---------- PRE-UI: contar fallos por IP con NOW() ---------- */
$ipPreview  = client_ip();
$failsPrev  = 0;
if ($conn && ($qPrev = $conn->prepare("
  SELECT COUNT(*) 
  FROM login_intentos 
  WHERE ip = ?
    AND creado_en >= (NOW() - INTERVAL ".LOGIN_WINDOW_MIN." MINUTE)
    AND exito = 0
"))) {
  $qPrev->bind_param("s", $ipPreview);
  $qPrev->execute(); $qPrev->bind_result($failsPrev); $qPrev->fetch(); $qPrev->close();
  $needCaptcha = ((int)$failsPrev >= LOGIN_CAPTCHA_AFTER);
}

/* ---------- POST ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if ($conn) {
    $conn->query("DELETE FROM login_intentos WHERE creado_en < (NOW() - INTERVAL ".LOGIN_PURGE_DAYS." DAY)");
  }

  $emailRaw   = trim((string)($_POST['email'] ?? ''));
  $email      = strtolower($emailRaw);
  $password   = (string)($_POST['password'] ?? '');
  $postedCsrf = (string)($_POST['csrf'] ?? '');
  $honeypot   = (string)($_POST['website'] ?? '');

  if (!hash_equals($csrf_token, $postedCsrf)) {
    $error = GENERIC_ERR;
  } else if ($email === '' || $password === '') {
    $error = "Completa email y contraseña.";
  } else {
    $ip = client_ip();

    // Conteo por IP (NOW)
    $failsVentana = 0;
    if ($conn && ($qB = $conn->prepare("
      SELECT COUNT(*) 
      FROM login_intentos 
      WHERE ip = ?
        AND creado_en >= (NOW() - INTERVAL ".LOGIN_WINDOW_MIN." MINUTE)
        AND exito = 0
    "))) {
      $qB->bind_param("s", $ip);
      $qB->execute(); $qB->bind_result($failsVentana); $qB->fetch(); $qB->close();
    }
    
    // Determinar si necesita CAPTCHA (se activa DESDE el intento N)
    $needCaptcha = ((int)$failsVentana >= LOGIN_CAPTCHA_AFTER);

    // Bloqueo duro
    if ((int)$failsVentana >= LOGIN_MAX_INTENTOS) {
      $error = GENERIC_ERR;
    }

    // Validación CAPTCHA antes de auth
    if ($error === null && $needCaptcha) {
      // Honeypot check
      if ($honeypot !== '') {
        $error = GENERIC_ERR;
      } else {
        $captchaToken = (string)($_POST['h-captcha-response'] ?? '');
        
        if ($captchaToken === '') {
          // Registrar intento fallido
          if ($conn && ($ins0 = $conn->prepare("INSERT INTO login_intentos (email, ip, exito) VALUES (?, ?, 0)"))) {
            $ins0->bind_param("ss", $email, $ip); 
            $ins0->execute(); 
            $ins0->close();
          }
          $captchaError = 'Por favor, completa el CAPTCHA para continuar.';
          $error = GENERIC_ERR;
        } else {
          // Verificar CAPTCHA con hCaptcha
          $captchaResult = verify_hcaptcha($captchaToken, $ip);
          
          if (!$captchaResult['success']) {
            // Registrar intento fallido
            if ($conn && ($ins0 = $conn->prepare("INSERT INTO login_intentos (email, ip, exito) VALUES (?, ?, 0)"))) {
              $ins0->bind_param("ss", $email, $ip); 
              $ins0->execute(); 
              $ins0->close();
            }
            
            // Mensajes de error específicos según el código de error
            $errorCodes = $captchaResult['error-codes'] ?? [];
            if (in_array('missing-input-response', $errorCodes)) {
              $captchaError = 'CAPTCHA no completado. Por favor, resolvé el desafío visual.';
            } else if (in_array('invalid-input-response', $errorCodes)) {
              $captchaError = 'CAPTCHA inválido. Por favor, intentá nuevamente.';
            } else if (in_array('timeout-or-duplicate', $errorCodes)) {
              $captchaError = 'CAPTCHA expirado. Por favor, resolvelo nuevamente.';
            } else {
              $captchaError = 'Error al verificar CAPTCHA. Intentá nuevamente.';
            }
            
            $error = GENERIC_ERR;
          }
        }
      }
    }

    if ($error === null) {
      // === Autenticación ===
      $ok=false; $user_id=null; $user_email=null; $user_password=null; $rol=null;

      if ($conn && ($stmt = $conn->prepare("SELECT user_id, email, contrasenia, rol FROM usuarios WHERE email = ? LIMIT 1"))) {
        $stmt->bind_param("s", $email);
        $stmt->execute(); $stmt->store_result();

        if ($stmt->num_rows === 1) {
          $stmt->bind_result($user_id, $user_email, $user_password, $rol);
          $stmt->fetch();

          $user_password = (string)($user_password ?? '');
          $isHash = ($user_password !== '' && str_starts_with($user_password, '$'));
          if ($isHash) $ok = password_verify($password, $user_password);
          if (!$ok && $user_password !== '') $ok = hash_equals($user_password, $password);
        }
        $stmt->close();
      }

      // Registrar intento
      if ($conn && ($ins = $conn->prepare("INSERT INTO login_intentos (email, ip, exito) VALUES (?, ?, ?)"))) {
        $flag = $ok ? 1 : 0;
        $ins->bind_param("ssi", $email, $ip, $flag);
        $ins->execute(); $ins->close();
      }

      if ($ok && $user_id !== null) {
        // Limpiar intentos por IP
        if ($conn && ($del = $conn->prepare("DELETE FROM login_intentos WHERE ip = ?"))) {
          $del->bind_param("s", $ip); $del->execute(); $del->close();
        }

        // Migración hash
        $isHash = ($user_password !== '' && str_starts_with($user_password, '$'));
        if (!$isHash || password_needs_rehash($user_password, PASSWORD_DEFAULT)) {
          $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
          if ($conn && ($u = $conn->prepare("UPDATE usuarios SET contrasenia = ? WHERE user_id = ?"))) {
            $u->bind_param("si", $nuevoHash, $user_id); $u->execute(); $u->close();
          }
        }

        // Sesión + routing
        session_regenerate_id(true);
        $_SESSION['usuario_id']    = (int)$user_id;
        $_SESSION['usuario_email'] = $user_email;
        $_SESSION['rol']           = $rol;

        if ($rol === 'recepcionista') {
          $prov = 0;
          if ($conn && ($q = $conn->prepare("SELECT proveedor_id FROM recepcionista_detalle WHERE recepcionista_id = ? LIMIT 1"))) {
            $q->bind_param("i", $user_id); $q->execute(); $q->bind_result($prov_id);
            if ($q->fetch()) $prov = (int)$prov_id; $q->close();
          }
          if ($prov <= 0) {
            $_SESSION['flash_error'] = 'Tu usuario de recepción no está vinculado a un proveedor. Contactá al admin.';
            header("Location: ./recepcionista/home_recepcionista.php"); exit;
          }
          $_SESSION['proveedor_id'] = $prov;
          header("Location: ./recepcionista/home_recepcionista.php"); exit;
        }

        switch ($rol) {
          case 'admin':     header("Location: ./admin/home_admin.php"); break;
          case 'proveedor': header("Location: ./proveedor/home_proveedor.php"); break;
          default:          header("Location: ./cliente/home_cliente.php"); break;
        }
        mysqli_close($conn); exit;
      } else {
        $delayMs = min(1000, 80 * (int)$failsVentana);
        if ($delayMs > 0) usleep($delayMs * 1000);
        $error = GENERIC_ERR;
      }
    }
  }
  mysqli_close($conn);
}

// Helpers para UI
$captchaScript = '';
$captchaWidget = '';
if ($needCaptcha) {
  $captchaScript = '<script src="https://js.hcaptcha.com/1/api.js" async defer></script>';
  // size="normal" fuerza el widget completo con desafío visual
  $captchaWidget = '<div class="h-captcha" data-sitekey="'.htmlspecialchars(CAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8').'" data-size="normal"></div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
  <title>GoatSport | Login</title>
  <style>
    * { font-family: 'Arial', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
    body { display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #054a56ff, #1bab9dff); padding: 20px; }
    .logo-container { text-align: center; margin-bottom: 10px; }
    .logo-container img { width: 160px; }
    .login-box { width: 100%; max-width: 400px; background: white; padding: 35px 30px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); text-align: center; }
    h1 { margin-bottom: 20px; font-size: 1.7rem; color: #054a56; }
    .input-group { position: relative; margin-bottom: 20px; }
    .input-group input { width: 100%; padding: 15px 42px 15px 15px; border: 1px solid #ccc; border-radius: 10px; font-size: 16px; background-color: #f9f9f9; transition: border-color .3s; }
    .input-group input:focus { border-color: #1bab9dff; outline: none; background-color: #fff; }
    .input-group svg { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; fill: #999; }
    .btn { width: 100%; padding: 14px; background-color: #1bab9dff; color: white; border: none; border-radius: 10px; font-size: 16px; cursor: pointer; transition: background .3s; margin-top: 10px; font-weight: 600; }
    .btn:hover { background-color: #14897f; }
    .btn:disabled { background-color: #ccc; cursor: not-allowed; }
    .extra-links { margin-top: 18px; font-size: 14px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .extra-links a { color: #1bab9dff; text-decoration: none; }
    .extra-links a:hover { text-decoration: underline; }
    .extra-link-prove { margin-top: 14px; font-size: 14px; text-align: center; }
    .extra-link-prove a { color: #1bab9dff; text-decoration: none; }
    .extra-link-prove a:hover { text-decoration: underline; }
    :root{ --eye-size: 30px; --eye-gap: 8px; --eye-btn: calc(var(--eye-size) + 12px); }
    .pwd-wrap{ position:relative; display:block }
    .pwd-wrap input{ padding-right: calc(var(--eye-btn) + var(--eye-gap)); }
    .pwd-toggle{
      position:absolute; right:6px; top:20%; transform:translateY(-50%);
      display:inline-flex; align-items:center; justify-content:center;
      width:var(--eye-btn); border:0; cursor:pointer;
    }
    .pwd-toggle:hover{ background:#f3f4f6 }
    .pwd-toggle svg{ width:var(--eye-size); height:var(--eye-size); transition:transform .18s }
    .pwd-toggle[aria-pressed="false"] svg{ transform:scale(1); margin-bottom: 6px; }
    .pwd-toggle[aria-pressed="true"]  svg{ transform:scale(1.04); margin-bottom: 6px; }
    .eye{ opacity:1; transition:opacity .18s } .slash{ opacity:0; transform-origin:center; transform:rotate(8deg); transition:opacity .18s, transform .18s }
    .off .eye{opacity:0.9;} .off .slash{opacity:1; transform:rotate(0deg);}
    .error { color: #b00020; margin-bottom: 15px; font-size: 14px; line-height: 1.4; }
    .captcha-error { color: #d32f2f; margin-bottom: 10px; font-size: 13px; font-weight: 500; }
    .captcha-wrap{ margin: 18px 0 8px; display:flex; justify-content:center; flex-direction: column; align-items: center; }
    .captcha-info { font-size: 13px; color: #666; margin-bottom: 12px; line-height: 1.4; }
    .hp-field{ position:absolute; left:-10000px; top:auto; width:1px; height:1px; overflow:hidden; }
    
    /* Estilos para el widget de hCaptcha */
    .h-captcha { transform-origin: center; }
    
    /* Responsive */
    @media (max-width: 450px) {
      .login-box { padding: 25px 20px; }
      h1 { font-size: 1.5rem; }
      .h-captcha { transform: scale(0.9); transform-origin: center; }
    }
  </style>
  <?= $captchaScript ?>
</head>
<body>
  <div class="logo-container">
    <img src="/img/logotipo.png" alt="Logo Padel">
  </div>

  <div class="login-box">
    <h1>Iniciar Sesión</h1>

    <?php if (!empty($error)): ?>
      <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($captchaError)): ?>
      <p class="captcha-error">⚠️ <?= htmlspecialchars($captchaError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="POST" autocomplete="on" novalidate id="loginForm">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>"/>

      <!-- Honeypot -->
      <div class="hp-field" aria-hidden="true">
        <label for="website">Deja este campo vacío</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="input-group">
        <input type="email" name="email" placeholder="Correo electrónico" required autocomplete="username"
               value="<?= isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 13.065 0 6V4l12 7 12-7v2l-12 7.065z"/></svg>
      </div>

      <div class="input-group pwd-wrap">
        <input type="password" name="password" id="pwd" placeholder="Contraseña" required autocomplete="current-password">
        <button type="button" class="pwd-toggle" id="togglePwd" aria-label="Mostrar/ocultar contraseña" aria-pressed="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path class="eye" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle class="eye" cx="12" cy="12" r="3"/>
            <path class="slash" d="M3 3L21 21"/>
          </svg>
        </button>
      </div>

      <?php if ($needCaptcha): ?>
        <div class="captcha-wrap">
          <p class="captcha-info">
            🔒 Por seguridad, resolvé el desafío visual para continuar
          </p>
          <?= $captchaWidget ?>
        </div>
      <?php endif; ?>

      <button type="submit" class="btn" id="submitBtn">Ingresar</button>
    </form>

    <div class="extra-links">
      <a href="register.php">¿No tienes una cuenta?</a>
      <a href="forgot.php">¿Olvidaste tu contraseña?</a>
    </div>

    <div class="extra-link-prove">
      <a href="register_proveedor.php">¿Sos proveedor? Ingresá acá</a>
    </div>
  </div>

  <script>
    (function(){
      // Toggle password visibility
      const input = document.getElementById('pwd');
      const btn   = document.getElementById('togglePwd');
      const svg   = btn.querySelector('svg');
      function apply(show){
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        svg.classList.toggle('off', !show);
      }
      btn.addEventListener('click', (e)=>{ e.preventDefault(); apply(input.type === 'password'); });
      apply(false);

      // Form validation
      const form = document.getElementById('loginForm');
      const submitBtn = document.getElementById('submitBtn');
      
      form.addEventListener('submit', function(e) {
        const email = form.querySelector('input[name="email"]').value.trim();
        const password = form.querySelector('input[name="password"]').value;
        
        if (!email || !password) {
          e.preventDefault();
          alert('Por favor, completa todos los campos.');
          return false;
        }

        <?php if ($needCaptcha): ?>
        // Verificar que el CAPTCHA fue completado
        const captchaResponse = form.querySelector('textarea[name="h-captcha-response"]');
        if (!captchaResponse || !captchaResponse.value) {
          e.preventDefault();
          alert('Por favor, completa el CAPTCHA antes de continuar.');
          return false;
        }
        <?php endif; ?>

        // Deshabilitar botón para evitar doble submit
        submitBtn.disabled = true;
        submitBtn.textContent = 'Verificando...';
      });
    })();
  </script>
</body>
</html>