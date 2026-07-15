<?php
require __DIR__ . '/_bootstrap.php';
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$s = db()->prepare('SELECT data FROM ' . TBL_PROGRESS . ' WHERE user_id = ?');
$s->execute([(int)$u['id']]);
$row = $s->fetch(PDO::FETCH_ASSOC);
json_out(['data' => ($row && $row['data']) ? json_decode($row['data'], true) : null]);
