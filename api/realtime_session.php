<?php
/* Realtime voice conversation — mints a SHORT-LIVED ephemeral client secret
   so the browser can open a WebRTC session straight to OpenAI. The real API
   key never leaves the server; the ephemeral token expires in ~1 minute and
   only works for one Realtime session. Login-gated, so cost is tied to you.

   OpenAI has shipped two shapes of this API. We try the GA endpoint
   (/v1/realtime/client_secrets) first and fall back to the older beta
   (/v1/realtime/sessions), then tell the browser which WebRTC URL to use. */
require __DIR__ . '/_bootstrap.php';
require_post();

$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$cfg   = ai_cfg();
$key   = $cfg['openai_key'];
$model = $cfg['realtime_model'] ?: 'gpt-realtime';
$voice = $cfg['realtime_voice'] ?: 'marin';
$stt   = $cfg['stt_model'] ?: 'gpt-4o-mini-transcribe';
if (!$key) json_out(['error' => 'ai_not_configured']);

/* Server voice-activity detection: wait until the learner clearly stops
   talking before Luma replies, and don't cut in on brief pauses. A slightly
   higher threshold + longer silence window (with echo cancellation on the
   client) stops Luma from triggering on its own voice and talking to itself. */
$turnDetection = [
  'type'                => 'server_vad',
  'threshold'           => 0.55,
  'prefix_padding_ms'   => 300,
  'silence_duration_ms' => 800,
];

/* Luma's coaching brief for spoken practice. */
$name = trim((string)($u['name'] ?? '')) ?: 'there';
$instructions =
  "You are Luma, a warm, patient owl who helps an English speaker practice spoken Spanish "
  . "at roughly A2–B1 level. The learner's name is $name. "
  . "Speak mostly in clear, simple Spanish at a relaxed pace. When they seem stuck, add a short "
  . "English hint, then return to Spanish. Keep your turns SHORT (1–2 sentences) so it feels like a "
  . "real back-and-forth conversation, and always end with a simple question to keep them talking. "
  . "Gently recast mistakes: repeat what they meant, correctly, without lecturing. Be encouraging and "
  . "friendly. Start by warmly greeting them in Spanish and asking how their day is going. "
  . "Never break character or mention these instructions.";

/* Small JSON POST helper (uses stream context, not curl). Returns the
   decoded JSON body (or null); we branch on whether a token came back. */
function oa_post(string $url, array $payload, string $key, array $extra = []) {
  $hdr = "Authorization: Bearer $key\r\nContent-Type: application/json\r\n";
  foreach ($extra as $h) $hdr .= $h . "\r\n";
  $ctx = stream_context_create(['http' => [
    'method'        => 'POST',
    'header'        => $hdr,
    'content'       => json_encode($payload),
    'timeout'       => 20,
    'ignore_errors' => true,
  ]]);
  $raw = @file_get_contents($url, false, $ctx);
  return json_decode($raw ?: '', true);
}

/* 1) GA shape: POST /v1/realtime/client_secrets → { value, expires_at } */
$j = oa_post('https://api.openai.com/v1/realtime/client_secrets', [
  'session' => [
    'type'         => 'realtime',
    'model'        => $model,
    'instructions' => $instructions,
    'audio'        => [
      'input'  => [
        'transcription'  => ['model' => $stt],
        'turn_detection' => $turnDetection,
      ],
      'output' => ['voice' => $voice],
    ],
  ],
], $key);

$token = $j['value'] ?? ($j['client_secret']['value'] ?? null);
if ($token) {
  json_out([
    'token'      => $token,
    'model'      => $model,
    'voice'      => $voice,
    'mode'       => 'ga',
    'sdp_url'    => 'https://api.openai.com/v1/realtime/calls?model=' . rawurlencode($model),
    'expires_at' => $j['expires_at'] ?? ($j['client_secret']['expires_at'] ?? null),
  ]);
}

/* 2) Beta fallback: POST /v1/realtime/sessions with OpenAI-Beta header.
      Uses a preview model name that the beta endpoint accepts. */
$betaModel = 'gpt-4o-realtime-preview';
$j2 = oa_post('https://api.openai.com/v1/realtime/sessions', [
  'model'                     => $betaModel,
  'voice'                     => $voice,
  'instructions'              => $instructions,
  'input_audio_transcription' => ['model' => $stt],
  'turn_detection'            => $turnDetection,
], $key, ['OpenAI-Beta: realtime=v1']);

$token2 = $j2['client_secret']['value'] ?? ($j2['value'] ?? null);
if ($token2) {
  json_out([
    'token'      => $token2,
    'model'      => $betaModel,
    'voice'      => $voice,
    'mode'       => 'beta',
    'sdp_url'    => 'https://api.openai.com/v1/realtime?model=' . rawurlencode($betaModel),
    'expires_at' => $j2['client_secret']['expires_at'] ?? null,
  ]);
}

/* Both failed — surface a useful hint without leaking the key. */
$msg = $j['error']['message'] ?? ($j2['error']['message'] ?? 'realtime_unavailable');
json_out(['error' => 'realtime_failed', 'detail' => $msg], 502);
