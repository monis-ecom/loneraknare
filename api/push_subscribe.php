<?php
/* Save a browser's push subscription for the logged-in user. */
require __DIR__ . '/_bootstrap.php';
require_post();
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$b = body();
$endpoint = trim((string)($b['endpoint'] ?? ''));
$p256dh   = trim((string)($b['keys']['p256dh'] ?? ''));
$auth     = trim((string)($b['keys']['auth'] ?? ''));
if ($endpoint === '' || $p256dh === '' || $auth === '') json_out(['error' => 'bad_subscription'], 422);
if (strncmp($endpoint, 'https://', 8) !== 0) json_out(['error' => 'bad_endpoint'], 422);

$pdo = db();
$isSqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
$sql = $isSqlite
  ? 'INSERT INTO ' . TBL_PUSH . ' (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?) ON CONFLICT(endpoint) DO UPDATE SET user_id=excluded.user_id, p256dh=excluded.p256dh, auth=excluded.auth'
  : 'INSERT INTO ' . TBL_PUSH . ' (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), p256dh=VALUES(p256dh), auth=VALUES(auth)';
$pdo->prepare($sql)->execute([(int)$u['id'], $endpoint, $p256dh, $auth]);
json_out(['ok' => true]);
