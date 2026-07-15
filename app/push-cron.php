<?php
// ─────────────────────────────────────────────────────────────────────────────
// Nordic Trend — daily push sender. Sends a "morning summary" to all subscribers.
// Hostinger cron (recommended):   php /home/USER/domains/APP/public_html/push-cron.php
// Or URL cron:                    https://APP-URL/push-cron.php?token=YOUR_TOKEN
// ─────────────────────────────────────────────────────────────────────────────
require __DIR__ . '/push.php';
$config = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];

if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json');
    if (($_GET['token'] ?? '') !== ($config['push_cron_token'] ?? '~no~')) { http_response_code(403); exit('{"error":"forbidden"}'); }
}

$SITE = rtrim((string)($config['site_url'] ?? ''), '/');
$CK = (string)($config['wc_consumer_key'] ?? '');
$CS = (string)($config['wc_consumer_secret'] ?? '');
$fees = is_array($config['payment_fees'] ?? null) ? $config['payment_fees'] : [];
$cur = (string)($config['currency'] ?? 'kr');

$after = date('Y-m-d') . 'T00:00:00';
$url = "{$SITE}/wp-json/wc/v3/orders?consumer_key={$CK}&consumer_secret={$CS}&per_page=100&status=processing,completed&after=" . rawurlencode($after);
$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => true]);
$resp = curl_exec($ch);
curl_close($ch);
$orders = json_decode($resp ?: '[]', true);
if (!is_array($orders)) $orders = [];

function cron_fee(array $fees, string $method, float $amt): float {
    $m = strtolower($method);
    $f = $fees['_default'] ?? ['pct' => 2.5, 'flat' => 0];
    foreach ($fees as $k => $v) { if ($k !== '_default' && strpos($m, $k) !== false) { $f = $v; break; } }
    return $amt * (($f['pct'] ?? 0) / 100) + ($f['flat'] ?? 0);
}

$rev = 0.0; $profit = 0.0; $count = 0;
foreach ($orders as $o) {
    $total = (float)($o['total'] ?? 0); $tax = (float)($o['total_tax'] ?? 0); $ship = (float)($o['shipping_total'] ?? 0);
    $cogs = (float)($o['cost_of_goods_sold']['total_value'] ?? 0);
    $ref = 0.0; foreach (($o['refunds'] ?? []) as $r) { $ref += abs((float)($r['total'] ?? 0)); }
    $rev += $total;
    $profit += (($total - $tax - $ship) - $cogs - cron_fee($fees, (string)($o['payment_method_title'] ?? ''), $total) - $ref);
    $count++;
}

$body = $count > 0
    ? 'Today: ' . round($rev) . " {$cur} revenue · " . round($profit) . " {$cur} profit · {$count} orders"
    : 'No orders yet today. ☕';
$payload = json_encode(['title' => 'Nordic Trend — Morning Summary', 'body' => $body, 'url' => '/']);

$subsPath = __DIR__ . '/push_subs.json';
$subs = is_file($subsPath) ? json_decode(file_get_contents($subsPath), true) : [];
if (!is_array($subs)) $subs = [];
$sent = 0; $failed = 0;
foreach ($subs as $ep => $sub) {
    $r = webpush_send($sub, $payload, $config);
    if ($r['ok']) { $sent++; } else { $failed++; if (in_array($r['code'], [404, 410], true)) unset($subs[$ep]); }
}
file_put_contents($subsPath, json_encode($subs), LOCK_EX);
echo json_encode(['ok' => true, 'sent' => $sent, 'failed' => $failed, 'summary' => $body]) . "\n";
