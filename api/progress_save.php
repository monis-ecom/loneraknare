<?php
require __DIR__ . '/_bootstrap.php';
require_post();
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$b    = body();
$data = $b['data'] ?? null;
if (!is_array($data)) json_out(['error' => 'bad_data'], 422);
$json = json_encode($data);
if (strlen($json) > 800000) json_out(['error' => 'too_large'], 413);   // ~0.8 MB guard

$pdo = db();
$upsert = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite')
  ? 'INSERT INTO ' . TBL_PROGRESS . ' (user_id, data, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)
       ON CONFLICT(user_id) DO UPDATE SET data = excluded.data, updated_at = CURRENT_TIMESTAMP'
  : 'INSERT INTO ' . TBL_PROGRESS . ' (user_id, data) VALUES (?, ?)
       ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = CURRENT_TIMESTAMP';
$s = $pdo->prepare($upsert);
$s->execute([(int)$u['id'], $json]);
json_out(['ok' => true]);
