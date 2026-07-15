<?php
/* One-click lesson-audio generator (no terminal needed).
   Called in small batches from Settings. Each call generates a few clips
   with OpenAI TTS and writes them straight into ../audio/ plus manifest.json.

   INCREMENTAL by default: any string that already has a clip on disk is
   skipped, so re-running only voices NEW lessons (cheap + fast, and your
   existing clips are never touched). Send {reset:1} on the first call to
   wipe and regenerate the whole library instead.

   Filenames are content-hashed (a<hash>.mp3) so a clip's name never changes
   when you add/reorder lessons — old clips stay valid forever. */
require __DIR__ . '/_bootstrap.php';
require_post();

$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$cfg   = ai_cfg();
$key   = $cfg['openai_key'];
$model = $cfg['tts_model'];
if (!$key) json_out(['error' => 'ai_not_configured']);

$b     = body();
$count = min(12, max(1, (int)($b['count'] ?? 8)));
$voice = preg_replace('/[^a-z]/', '', strtolower((string)($b['voice'] ?? 'coral'))) ?: 'coral';
$reset = !empty($b['reset']);
$ver   = (string)($b['ver'] ?? '');
if ($ver === '' || !ctype_digit($ver)) json_out(['error' => 'bad_ver'], 422);

@set_time_limit(120);
$audioDir = __DIR__ . '/../audio';
if (!is_dir($audioDir)) @mkdir($audioDir, 0755, true);
if (!is_writable($audioDir)) json_out(['error' => 'audio_not_writable'], 500);

/* Build the set of every spoken string, from the units. */
$strings = []; $seen = [];
$add = function ($s) use (&$strings, &$seen) { $s = trim((string)$s); if ($s !== '' && !isset($seen[$s])) { $seen[$s] = 1; $strings[] = $s; } };
foreach (glob(__DIR__ . '/../units/*.json') as $uf) {
  $j = json_decode(file_get_contents($uf), true); if (!$j) continue;
  foreach (($j['vocab'] ?? []) as $v) if (!empty($v['es'])) $add($v['es']);
  foreach (($j['exercises'] ?? []) as $ex) {
    $t = $ex['type'] ?? '';
    if ($t === 'listening' && !empty($ex['tts'])) $add($ex['tts']);
    if ($t === 'speak' && !empty($ex['say'])) $add($ex['say']);
    if ($t === 'dialogue') {
      foreach (($ex['lines'] ?? []) as $l) if (empty($l['blank']) && !empty($l['es'])) $add($l['es']);
      foreach (($ex['options'] ?? []) as $o) if (!empty($o['text'])) $add($o['text']);
    }
  }
}
sort($strings, SORT_STRING);
$total = count($strings);

$manifestPath = $audioDir . '/manifest.json';
$manifest = file_exists($manifestPath) ? (json_decode(file_get_contents($manifestPath), true) ?: []) : [];

/* Full rebuild only when explicitly requested. */
if ($reset) {
  foreach (glob($audioDir . '/*.mp3') as $old) @unlink($old);
  $manifest = [];
}
$manifest['_v'] = $ver;

/* A string still needs audio if it isn't mapped OR its file is missing. */
$needs = function ($s) use ($manifest, $audioDir) {
  return !(isset($manifest[$s]) && is_file($audioDir . '/' . $manifest[$s]));
};
$missing = array_values(array_filter($strings, $needs));
$remainingBefore = count($missing);

/* Generate up to $count of the still-missing clips this call. */
$generated = 0; $failed = 0;
foreach (array_slice($missing, 0, $count) as $s) {
  $file = 'a' . substr(md5($s), 0, 12) . '.mp3';
  $payload = json_encode([
    'model' => $model, 'voice' => $voice, 'input' => $s, 'response_format' => 'mp3',
    'instructions' => 'Speak in clear, natural, neutral Spanish at a calm, friendly pace for a language learner.',
  ]);
  $ctx = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "Authorization: Bearer $key\r\nContent-Type: application/json\r\n",
    'content' => $payload, 'timeout' => 30, 'ignore_errors' => true,
  ]]);
  $audio = @file_get_contents('https://api.openai.com/v1/audio/speech', false, $ctx);
  if ($audio !== false && strlen($audio) > 200 && strncmp($audio, '{', 1) !== 0) {
    file_put_contents($audioDir . '/' . $file, $audio);
    $manifest[$s] = $file;
    $generated++;
  } else {
    $failed++;
  }
}
file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$remaining = max(0, $remainingBefore - $generated);
json_out([
  'total'     => $total,
  'have'      => $total - $remaining,
  'remaining' => $remaining,
  'generated' => $generated,
  'failed'    => $failed,
  'voice'     => $voice,
  'mode'      => $reset ? 'full' : 'incremental',
]);
