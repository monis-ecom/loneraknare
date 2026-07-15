<?php
// Generate a new VAPID keypair for web push. CLI only.
// Usage:  php tools/make-vapid.php
// Paste the two values into config.php (vapid_public + vapid_private_pem),
// then re-enable notifications on each device.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
$k = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
openssl_pkey_export($k, $pem);
$d = openssl_pkey_get_details($k);
$pub = "\x04" . $d['ec']['x'] . $d['ec']['y'];
$b64u = rtrim(strtr(base64_encode($pub), '+/', '-_'), '=');
echo "'vapid_public'      => '" . $b64u . "',\n";
echo "'vapid_private_pem' => " . var_export($pem, true) . ",\n";
