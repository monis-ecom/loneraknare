<?php
require __DIR__ . '/_bootstrap.php';
require_post();
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$b = body();

/* Model choices: a preset from the known list is always accepted; anything
   else is treated as a user-supplied custom model name and accepted as long as
   it looks like a plausible OpenAI model id (letters, digits, . _ - : only).
   This lets you point Fluenta at any current/future model without a new build. */
$fields = ['openai_model', 'stt_model', 'tts_model'];
foreach ($fields as $field) {
  if (!isset($b[$field])) continue;
  $v = trim((string) $b[$field]);
  if ($v === '') continue;
  if (preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $v)) set_setting($field, $v);
}

/* Key: only overwrite when a new one is provided. */
$key = trim((string)($b['openai_key'] ?? ''));
if ($key !== '') {
  if (strncmp($key, 'sk-', 3) !== 0 || strlen($key) < 20) json_out(['error' => 'bad_key'], 422);
  set_setting('openai_key', $key);
}

/* Return fresh status (bypassing ai_cfg's static cache). */
$base = file_exists(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : require __DIR__ . '/config.php';
$cur = [
  'openai_key'   => $base['openai_key']   ?? '',
  'openai_model' => $base['openai_model'] ?? 'gpt-4o-mini',
  'stt_model'    => $base['stt_model']    ?? 'gpt-4o-mini-transcribe',
  'tts_model'    => $base['tts_model']    ?? 'gpt-4o-mini-tts',
];
foreach (db()->query('SELECT k, v FROM ' . TBL_SETTINGS) as $row) {
  if (array_key_exists($row['k'], $cur) && $row['v'] !== '' && $row['v'] !== null) $cur[$row['k']] = $row['v'];
}
$kk = $cur['openai_key'];
json_out([
  'ok'           => true,
  'has_key'      => $kk !== '',
  'key_hint'     => $kk !== '' ? ('••••' . substr($kk, -4)) : '',
  'openai_model' => $cur['openai_model'],
  'stt_model'    => $cur['stt_model'],
  'tts_model'    => $cur['tts_model'],
]);
