<?php
require __DIR__ . '/_bootstrap.php';
require_post();
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$b   = body();
$new = (string)($b['password'] ?? '');
if (strlen($new) < 6)          json_out(['error' => 'weak_password'], 422);
if ($new === DEFAULT_PASSWORD) json_out(['error' => 'reused_default'], 422);

$s = db()->prepare('UPDATE ' . TBL_USERS . ' SET pass_hash = ?, must_change = 0 WHERE id = ?');
$s->execute([password_hash($new, PASSWORD_DEFAULT), (int)$u['id']]);

json_out(['authenticated' => true, 'user' => ['username' => $u['username'], 'name' => $u['name']], 'must_change' => false]);
