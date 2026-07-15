<?php
require __DIR__ . '/_bootstrap.php';
$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$c = ai_cfg();
$k = $c['openai_key'];
json_out([
  'has_key'      => $k !== '',
  'key_hint'     => $k !== '' ? ('••••' . substr($k, -4)) : '',
  'openai_model' => $c['openai_model'],
  'stt_model'    => $c['stt_model'],
  'tts_model'    => $c['tts_model'],
]);
