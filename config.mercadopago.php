<?php
declare(strict_types=1);

define('MP_ENV', 'sandbox'); // 'production' en prod
define('MP_PUBLIC_KEY_SANDBOX',   'TEST-62acd7ce-b106-458a-8590-44d90e102ff7');
define('MP_ACCESS_TOKEN_SANDBOX', 'TEST-7229704544416343-092721-009116db09f9837564a64cc9b599e32c-1718741605');
define('MP_PUBLIC_KEY_PROD',      'APP_USR-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('MP_ACCESS_TOKEN_PROD',    'APP_USR-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

define('MP_PUBLIC_KEY',   MP_ENV === 'production' ? MP_PUBLIC_KEY_PROD   : MP_PUBLIC_KEY_SANDBOX);
define('MP_ACCESS_TOKEN', MP_ENV === 'production' ? MP_ACCESS_TOKEN_PROD : MP_ACCESS_TOKEN_SANDBOX);

/** Usás localhost; para webhooks necesitás URL pública (ngrok/cloudflared). */
define('BASE_URL', 'http://localhost:3000');

/** Cambiá esto a tu URL pública cuando abras el túnel. */
define('MP_NOTIFICATION_URL', BASE_URL . '/php/cliente/reservas/logica/pagos/mp_webhook.php');

/** Solo para tu flujo de “tarjeta” (no MP Checkout) */
define('MP_DEMO_FORCE_APPROVED', true);