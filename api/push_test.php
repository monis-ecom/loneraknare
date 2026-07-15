<?php
/* Send a test notification to the logged-in user's own devices. */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/push_lib.php';
require_post();
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

/* Data-less push (null payload): VAPID-authenticated, no fragile payload
   encryption. The service worker renders the message. */
$sent = 0; $failed = 0;
$stmt = db()->prepare('SELECT endpoint, p256dh, auth FROM ' . TBL_PUSH . ' WHERE user_id = ?');
$stmt->execute([(int)$u['id']]);
$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($subs as $s) {
  [$code, $res] = web_push_send($s['endpoint'], $s['p256dh'], $s['auth'], null);
  if ($code >= 200 && $code < 300) $sent++;
  else {
    $failed++;
    if ($code === 404 || $code === 410) db()->prepare('DELETE FROM ' . TBL_PUSH . ' WHERE endpoint = ?')->execute([$s['endpoint']]);
  }
}
if (!$subs) json_out(['error' => 'no_subscriptions']);
json_out(['ok' => $sent > 0, 'sent' => $sent, 'failed' => $failed]);
