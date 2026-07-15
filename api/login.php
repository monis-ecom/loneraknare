<?php
require __DIR__ . '/_bootstrap.php';
require_post();
$b        = body();
$username  = strtolower(trim((string)($b['username'] ?? $b['email'] ?? '')));
$pass      = (string)($b['password'] ?? '');

$s = db()->prepare('SELECT id, pass_hash, name, must_change FROM ' . TBL_USERS . ' WHERE username = ?');
$s->execute([$username]);
$u = $s->fetch(PDO::FETCH_ASSOC);

if (!$u || !password_verify($pass, $u['pass_hash'])) json_out(['error' => 'invalid_credentials'], 401);

session_regenerate_id(true);
$_SESSION['uid'] = (int)$u['id'];
json_out(['authenticated' => true, 'user' => ['username' => $username, 'name' => $u['name']], 'must_change' => (bool)$u['must_change']]);
