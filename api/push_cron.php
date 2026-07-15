<?php
/* Daily reminder sender — hit by Hostinger cron jobs. Supports multiple
   fixed times per day, each with its own message, and an optional
   "only if the learner hasn't finished today" condition.

   URL:
     https://YOURDOMAIN/api/push_cron.php?key=CRON_KEY&mode=all&slot=morning&tz=Region/City
     https://YOURDOMAIN/api/push_cron.php?key=CRON_KEY&mode=due&slot=evening&tz=Region/City
     https://YOURDOMAIN/api/push_cron.php?key=CRON_KEY&mode=due&slot=night&tz=Region/City

   Params:
     key   (required)  secret cron key (shown in the app's Settings).
     mode  all | due   'all' = send to everyone (a first daily nudge);
                       'due' = send ONLY to users who have NOT met their
                               daily goal yet today (skip anyone already done).
     slot  morning|evening|night|<blank>  picks the message text.
     tz    e.g. Europe/Stockholm, Asia/Dhaka, America/New_York.
           Defines what counts as "today" so it matches the learner's local
           day. Set it to YOUR timezone. Defaults to UTC.

   The secret key protects this endpoint (cron can't log in). */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/push_lib.php';

/* Verify the cron key (generated + stored on first view in Settings). */
$rows = [];
foreach (db()->query('SELECT k, v FROM ' . TBL_SETTINGS) as $r) $rows[$r['k']] = $r['v'];
$stored = $rows['push_cron_key'] ?? '';
$given  = (string)($_GET['key'] ?? '');
if ($stored === '' || !hash_equals($stored, $given)) json_out(['error' => 'forbidden'], 403);

$mode = ($_GET['mode'] ?? 'all') === 'due' ? 'due' : 'all';
$slot = (string)($_GET['slot'] ?? '');

/* "Today" in the learner's timezone, formatted to match the client's
   JavaScript new Date().toDateString() (e.g. "Fri Jul 10 2026"), which is
   how PROG.todayKey and PROG.activity keys are stored. */
try { $tzObj = new DateTimeZone((string)($_GET['tz'] ?? 'UTC')); }
catch (Throwable $e) { $tzObj = new DateTimeZone('UTC'); }
$todayKey = (new DateTime('now', $tzObj))->format('D M d Y');

/* Data-less pushes (null payload) — VAPID only, no fragile payload
   encryption. The service worker picks a time-appropriate message from the
   device's local hour, so morning/evening/night wording still matches. The
   `slot` param stays for logging/clarity. */

/* For mode=due, work out who has ALREADY met their daily goal today so we
   can skip them. A user with no synced progress counts as "not done" (they
   still get nudged). */
$metGoal = [];
if ($mode === 'due') {
  foreach (db()->query('SELECT user_id, data FROM ' . TBL_PROGRESS) as $r) {
    $d = json_decode($r['data'] ?? '', true);
    $prog = is_array($d) ? ($d['prog'] ?? null) : null;
    if (!is_array($prog)) continue;
    $goal = (int)($prog['dailyGoal'] ?? 30);
    $xpToday = 0;
    if (isset($prog['activity'][$todayKey])) $xpToday = (int)$prog['activity'][$todayKey];
    elseif (($prog['todayKey'] ?? '') === $todayKey) $xpToday = (int)($prog['todayXP'] ?? 0);
    $metGoal[(int)$r['user_id']] = ($goal > 0 && $xpToday >= $goal);
  }
}

$sent = 0; $dead = 0; $skipped = 0;
foreach (db()->query('SELECT user_id, endpoint, p256dh, auth FROM ' . TBL_PUSH) as $s) {
  if ($mode === 'due' && !empty($metGoal[(int)$s['user_id']])) { $skipped++; continue; }
  [$code] = web_push_send($s['endpoint'], $s['p256dh'], $s['auth'], null);
  if ($code >= 200 && $code < 300) $sent++;
  elseif ($code === 404 || $code === 410) { db()->prepare('DELETE FROM ' . TBL_PUSH . ' WHERE endpoint = ?')->execute([$s['endpoint']]); $dead++; }
}
json_out(['ok' => true, 'mode' => $mode, 'slot' => $slot, 'today' => $todayKey, 'sent' => $sent, 'skipped' => $skipped, 'removed' => $dead]);
