<?php
require __DIR__ . '/_bootstrap.php';
$u = current_user_row();
if (!$u) json_out(['authenticated' => false]);
json_out(['authenticated' => true, 'user' => ['username' => $u['username'], 'name' => $u['name']], 'must_change' => (bool)$u['must_change']]);
