<?php
/**
 * FILE: /config.mercadopago.php
 * Modo sandbox con credenciales TEST del cobrador (tu amigo).
 */
declare(strict_types=1);

define('MP_ENV', 'sandbox'); // Cambiar a 'production' cuando migres

// ===== SANDBOX (TEST) - Cobrador (tu amigo)
define('MP_PUBLIC_KEY_SANDBOX',   'TEST-b8b472fa-249c-475e-bc2b-20b8393adc9f');
define('MP_ACCESS_TOKEN_SANDBOX', 'TEST-3106169317824128-121707-4cdbb9a24bace976eea515984d099da8-1117485014');

// ===== PRODUCCIÓN (APP_USR) - Placeholder hasta migrar
define('MP_PUBLIC_KEY_PROD',      'APP_USR-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('MP_ACCESS_TOKEN_PROD',    'APP_USR-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

// ===== Selección automática por entorno
define('MP_PUBLIC_KEY',   MP_ENV === 'production' ? MP_PUBLIC_KEY_PROD   : MP_PUBLIC_KEY_SANDBOX);
define('MP_ACCESS_TOKEN', MP_ENV === 'production' ? MP_ACCESS_TOKEN_PROD : MP_ACCESS_TOKEN_SANDBOX);

// ===== URLs de tu app
define('BASE_URL', 'http://localhost:3000'); // El browser puede volver a localhost

/**
 * Webhook DEBE ser HTTPS público (ngrok/cloudflared) para que MP pueda llamarte.
 * Reemplazá TU-TUNEL por el dominio del túnel activo.
 * Ej: https://ab12cd34.ngrok.app  o  https://xyz.trycloudflare.com
 */
define('MP_NOTIFICATION_URL', 'https://TU-TUNEL/php/cliente/reservas/logica/pagos/mp_webhook.php');

// Solo para tu flujo "tarjeta" propio (no Checkout Pro). Mantener en false.
define('MP_DEMO_FORCE_APPROVED', false);
