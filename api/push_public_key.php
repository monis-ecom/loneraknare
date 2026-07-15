<?php
/* Returns the server VAPID public key (safe to expose) so the browser
   can subscribe. Generates + persists the keypair on first call. */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/push_lib.php';
$v = get_vapid();
json_out(['key' => $v['pub_b64u']]);
