<?php
/* Remove a push subscription (user turned notifications off). */
require __DIR__ . '/_bootstrap.php';
require_post();
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);
$b = body();
$endpoint = trim((string)($b['endpoint'] ?? ''));
if ($endpoint !== '') db()->prepare('DELETE FROM ' . TBL_PUSH . ' WHERE endpoint = ?')->execute([$endpoint]);
json_out(['ok' => true]);
