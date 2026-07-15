<?php
/* Pronunciation check — proxies the learner's recorded audio to OpenAI
   speech-to-text and returns the transcript. The app compares it to the
   target word/sentence. Key stays server-side; login required. */
require __DIR__ . '/_bootstrap.php';
require_post();

$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$cfg   = ai_cfg();
$key   = $cfg['openai_key'];
$model = $cfg['stt_model'];
if (!$key) json_out(['error' => 'ai_not_configured']);

if (empty($_FILES['audio']['tmp_name']) || !is_uploaded_file($_FILES['audio']['tmp_name'])) json_out(['error' => 'no_audio'], 422);
if (($_FILES['audio']['size'] ?? 0) > 8 * 1024 * 1024) json_out(['error' => 'too_large'], 413);

$mime = $_FILES['audio']['type'] ?: 'audio/webm';
$ext  = strpos($mime, 'mp4') !== false ? 'mp4'
      : (strpos($mime, 'mpeg') !== false ? 'mp3'
      : (strpos($mime, 'wav') !== false ? 'wav'
      : (strpos($mime, 'ogg') !== false ? 'ogg' : 'webm')));
$audio = file_get_contents($_FILES['audio']['tmp_name']);

$boundary = '----fluenta' . bin2hex(random_bytes(8));
$eol = "\r\n";
$b = '';
foreach (['model' => $model, 'language' => 'es', 'response_format' => 'json'] as $k => $v) {
  $b .= "--$boundary$eol" . 'Content-Disposition: form-data; name="' . $k . '"' . $eol . $eol . $v . $eol;
}
$b .= "--$boundary$eol" . 'Content-Disposition: form-data; name="file"; filename="speech.' . $ext . '"' . $eol
    . 'Content-Type: ' . $mime . $eol . $eol . $audio . $eol;
$b .= "--$boundary--$eol";

$ctx = stream_context_create(['http' => [
  'method'        => 'POST',
  'header'        => "Authorization: Bearer $key" . $eol . "Content-Type: multipart/form-data; boundary=$boundary" . $eol,
  'content'       => $b,
  'timeout'       => 45,
  'ignore_errors' => true,
]]);
$res = @file_get_contents('https://api.openai.com/v1/audio/transcriptions', false, $ctx);
if ($res === false) json_out(['error' => 'upstream_unreachable'], 502);

$j = json_decode($res, true);
$text = $j['text'] ?? null;
if ($text === null) json_out(['error' => 'upstream_error'], 502);

json_out(['transcript' => trim($text)]);
