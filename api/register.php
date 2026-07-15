<?php
require __DIR__ . '/_bootstrap.php';
require_post();
$b        = body();
$username  = strtolower(trim((string)($b['username'] ?? $b['email'] ?? '')));
$pass      = (string)($b['password'] ?? '');
$name      = trim((string)($b['name'] ?? ''));

// username: 2–40 chars, letters/digits and . _ - @ (so an email works too)
if (!preg_match('/^[a-z0-9._@-]{2,40}$/', $username)) json_out(['error' => 'invalid_username'], 422);
if (strlen($pass) < 6)                                json_out(['error' => 'weak_password'], 422);

$pdo = db();
$chk = $pdo->prepare('SELECT id FROM ' . TBL_USERS . ' WHERE username = ?');
$chk->execute([$username]);
if ($chk->fetch()) json_out(['error' => 'username_taken'], 409);

$ins = $pdo->prepare('INSERT INTO ' . TBL_USERS . ' (username, pass_hash, name) VALUES (?, ?, ?)');
$ins->execute([$username, password_hash($pass, PASSWORD_DEFAULT), $name !== '' ? $name : null]);

session_regenerate_id(true);
$_SESSION['uid'] = (int)$pdo->lastInsertId();
json_out(['authenticated' => true, 'user' => ['username' => $username, 'name' => $name], 'must_change' => false]);
