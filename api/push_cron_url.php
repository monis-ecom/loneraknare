<?php
/* Returns the full daily-reminder cron URL (with its secret key), so you can
   paste it into Hostinger's Cron Jobs UI. Generates the key on first call. */
require __DIR__ . '/_bootstrap.php';
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$rows = [];
foreach (db()->query('SELECT k, v FROM ' . TBL_SETTINGS) as $r) $rows[$r['k']] = $r['v'];
$key = $rows['push_cron_key'] ?? '';
if ($key === '') { $key = bin2hex(random_bytes(16)); set_setting('push_cron_key', $key); }

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'fluenta.klivrapps.com';
$dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x')), '/');
$url    = $scheme . '://' . $host . $dir . '/push_cron.php?key=' . $key;
json_out(['url' => $url]);
