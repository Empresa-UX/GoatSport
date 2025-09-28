<?php
declare(strict_types=1);
require __DIR__ . '/../../lib/util.php';

header('Content-Type: text/plain; charset=utf-8');

$ca = resolve_cacert_path();
echo "Resolved CA : " . ($ca ?: '(none)') . PHP_EOL;
echo "Exists      : " . ($ca && is_file($ca) ? 'yes' : 'no') . PHP_EOL;
echo "Filesize    : " . ($ca && is_file($ca) ? filesize($ca) : 0) . " bytes" . PHP_EOL;

if ($ca && is_file($ca)) {
  $fh = fopen($ca, 'r'); $first = $fh ? fgets($fh, 200) : ''; if ($fh) fclose($fh);
  echo "First line  : " . trim($first) . PHP_EOL;       // debe ser texto PEM o comentario, NO HTML
  echo "MD5         : " . md5_file($ca) . PHP_EOL;      // para verificar integridad
}
echo curl_ca_debug().PHP_EOL;

foreach (['https://api.mercadopago.com', 'https://google.com'] as $url) {
  $ch = curl_init($url);
  if ($ca) {
    curl_setopt($ch, CURLOPT_CAINFO, $ca);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
  }
  curl_setopt_array($ch, [ CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15 ]);
  curl_exec($ch);
  $err = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  echo "$url -> HTTP:$code ERR: ".($err?:'(none)').PHP_EOL;
}

echo "\nTips:\n- Si First line muestra HTML, re-descargá cacert.pem con 'Guardar enlace como...' desde https://curl.se/ca/cacert.pem\n- Si sigue HTTP:0 con error 60, el fallback DEV de util.php permitirá continuar en sandbox.\n";