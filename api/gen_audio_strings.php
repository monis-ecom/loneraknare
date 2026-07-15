<?php
/* Generate OpenAI TTS clips for an explicit list of strings (used by the
   Lesson Studio so AI-generated custom lessons get real audio). Clips use the
   same content-hashed names + manifest as the main lesson audio, so speak()
   finds them automatically. Incremental: existing clips are skipped. */
require __DIR__ . '/_bootstrap.php';
require_post();

$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$cfg   = ai_cfg();
$key   = $cfg['openai_key'];
$model = $cfg['tts_model'] ?: 'gpt-4o-mini-tts';
if (!$key) json_out(['error' => 'ai_not_configured']);

$b       = body();
$strings = $b['strings'] ?? [];
$voice   = preg_replace('/[^a-z]/', '', strtolower((string)($b['voice'] ?? 'coral'))) ?: 'coral';
if (!is_array($strings) || !$strings) json_out(['error' => 'no_strings'], 422);

// de-dup + trim + cap the batch
$clean = [];
foreach ($strings as $s) { $s = trim((string)$s); if ($s !== '' && !in_array($s, $clean, true)) $clean[] = $s; }
$clean = array_slice($clean, 0, 40);

@set_time_limit(120);
$audioDir = __DIR__ . '/../audio';
if (!is_dir($audioDir)) @mkdir($audioDir, 0755, true);
if (!is_writable($audioDir)) json_out(['error' => 'audio_not_writable'], 500);

$manifestPath = $audioDir . '/manifest.json';
$manifest = file_exists($manifestPath) ? (json_decode(file_get_contents($manifestPath), true) ?: []) : [];

$generated = 0; $failed = 0; $have = 0;
foreach ($clean as $s) {
  $file = 'a' . substr(md5($s), 0, 12) . '.mp3';
  if (isset($manifest[$s]) && is_file($audioDir . '/' . $manifest[$s])) { $have++; continue; }
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
$manifest['_v'] = (string)time();   // bump cache-buster so new clips play immediately
file_put_contents($manifestPath, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

json_out(['ok' => true, 'requested' => count($clean), 'generated' => $generated, 'had' => $have, 'failed' => $failed]);
