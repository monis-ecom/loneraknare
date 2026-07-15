<?php
require __DIR__ . '/_bootstrap.php';
require_post();
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$b    = body();
$name = trim((string)($b['name'] ?? ''));
if (mb_strlen($name) > 120) json_out(['error' => 'name_too_long'], 422);

$s = db()->prepare('UPDATE ' . TBL_USERS . ' SET name = ? WHERE id = ?');
$s->execute([$name !== '' ? $name : null, (int)$u['id']]);
json_out(['ok' => true, 'user' => ['username' => $u['username'], 'name' => $name]]);
