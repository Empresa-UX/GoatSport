<?php
declare(strict_types=1);

function ensure_session(): void { if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); } }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function csrf_ensure_token(): string { ensure_session(); return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32)); }
function csrf_input(): string { return '<input type="hidden" name="csrf_token" value="'.h(csrf_ensure_token()).'">'; }
function csrf_validate_or_die(): void {
  ensure_session();
  $sent = $_POST['csrf_token'] ?? ''; $sess = $_SESSION['csrf_token'] ?? '';
  if (!$sent || !$sess || !hash_equals($sess, $sent)) { http_response_code(400); die('CSRF inválido'); }
}

/** Prioriza cacert del proyecto, luego php.ini, luego bundles XAMPP. */
function resolve_cacert_path(): ?string {
  $project = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'cacert.pem';
  if (is_file($project)) return $project;
  $ini = trim((string)ini_get('curl.cainfo'));
  if ($ini && is_file($ini)) return $ini;
  foreach ([
    'C:\xampp\php\extras\ssl\cacert.pem',
    'C:\xampp\apache\bin\curl-ca-bundle.crt',
  ] as $p) if (is_file($p)) return $p;
  return null;
}

/** Info de CA y versiones para debug. */
function curl_ca_debug(): string {
  $ini = trim((string)ini_get('curl.cainfo')) ?: '(empty)';
  $resolved = resolve_cacert_path() ?: '(none)';
  $cv = curl_version();
  return "curl.cainfo=$ini ; resolved=$resolved ; curl=".$cv['version']."; ssl=".$cv['ssl_version'];
}

/** Headers MP comunes. */
function mp_headers(array $extra = []): array {
  $h = [
    'Authorization: Bearer '.MP_ACCESS_TOKEN,
    'Content-Type: application/json',
  ];
  foreach ($extra as $x) $h[] = $x;
  return $h;
}

/**
 * POST a MP con SSL verificado.
 * Si hay error 60 (CA) y estás en sandbox, reintenta sin verificación (DEV ONLY).
 */
function mp_api_post(string $path, array $payload, array $extraHeaders = []): array {
  $url = (str_starts_with($path, 'http') ? $path : 'https://api.mercadopago.com'.$path);
  $headers = mp_headers($extraHeaders);

  $do = function (bool $insecure) use ($url, $headers, $payload) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST           => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER     => $headers,
      CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      CURLOPT_CONNECTTIMEOUT => 20,
      CURLOPT_TIMEOUT        => 30,
    ]);
    if ($insecure) {
      // Por qué: solo dev para seguir probando si falla CA.
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    } else {
      $ca = resolve_cacert_path();
      if ($ca) {
        curl_setopt($ch, CURLOPT_CAINFO, $ca);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      }
    }
    $raw   = curl_exec($ch);
    $errno = curl_errno($ch);
    $err   = curl_error($ch);
    $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$errno, $err, $http, $raw];
  };

  [$e1, $m1, $h1, $r1] = $do(false);
  if ($e1 === 0) {
    $j = json_decode($r1, true);
    return ['ok'=>($h1>=200 && $h1<300), 'http'=>$h1, 'body'=>$j, 'raw'=>$r1];
  }

  if ($e1 === 60 && defined('MP_ENV') && MP_ENV === 'sandbox') {
    [$e2, $m2, $h2, $r2] = $do(true);
    if ($e2 === 0) {
      $j2 = json_decode($r2, true);
      return ['ok'=>($h2>=200 && $h2<300), 'http'=>$h2, 'body'=>$j2, 'raw'=>$r2, 'insecure'=>true];
    }
    return ['ok'=>false, 'http'=>$h2, 'error'=>"cURL $e1/$e2: $m1 | $m2 ; ".curl_ca_debug(), 'raw'=>$r2];
  }

  return ['ok'=>false, 'http'=>$h1, 'error'=>"cURL $e1: $m1 ; ".curl_ca_debug(), 'raw'=>$r1];
}

/** GET a MP con verificación TLS (mismo fallback dev). */
function mp_api_get(string $path, array $extraHeaders = []): array {
  $url = (str_starts_with($path, 'http') ? $path : 'https://api.mercadopago.com'.$path);
  $headers = mp_headers($extraHeaders);

  $do = function (bool $insecure) use ($url, $headers) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER     => $headers,
      CURLOPT_CONNECTTIMEOUT => 20,
      CURLOPT_TIMEOUT        => 30,
    ]);
    if ($insecure) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    } else {
      $ca = resolve_cacert_path();
      if ($ca) {
        curl_setopt($ch, CURLOPT_CAINFO, $ca);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      }
    }
    $raw   = curl_exec($ch);
    $errno = curl_errno($ch);
    $err   = curl_error($ch);
    $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$errno, $err, $http, $raw];
  };

  [$e1, $m1, $h1, $r1] = $do(false);
  if ($e1 === 0) {
    $j = json_decode($r1, true);
    return ['ok'=>($h1>=200 && $h1<300), 'http'=>$h1, 'body'=>$j, 'raw'=>$r1];
  }

  if ($e1 === 60 && defined('MP_ENV') && MP_ENV === 'sandbox') {
    [$e2, $m2, $h2, $r2] = $do(true);
    if ($e2 === 0) {
      $j2 = json_decode($r2, true);
      return ['ok'=>($h2>=200 && $h2<300), 'http'=>$h2, 'body'=>$j2, 'raw'=>$r2, 'insecure'=>true];
    }
    return ['ok'=>false, 'http'=>$h2, 'error'=>"cURL $e1/$e2: $m1 | $m2 ; ".curl_ca_debug(), 'raw'=>$r2];
  }

  return ['ok'=>false, 'http'=>$h1, 'error'=>"cURL $e1: $m1 ; ".curl_ca_debug(), 'raw'=>$r1];
}

/** Origen absoluto para back_urls. */
function request_origin(): string {
  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
             (($_SERVER['SERVER_PORT'] ?? '') === '443') ||
             (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  $scheme  = $isHttps ? 'https' : 'http';
  $host    = $_SERVER['HTTP_HOST'] ?? (($_SERVER['SERVER_NAME'] ?? 'localhost') . (isset($_SERVER['SERVER_PORT']) ? ':'.$_SERVER['SERVER_PORT'] : ''));
  return $scheme . '://' . $host;
}
