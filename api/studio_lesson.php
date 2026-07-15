<?php
/* AI lesson generator for the Lesson Studio.
   Takes a real-life TOPIC (e.g. "order food at a restaurant") + a CEFR level
   and asks the model to produce a full Fluenta lesson (grammar + exercises +
   vocab) as strict JSON. The key stays server-side; login required. */
require __DIR__ . '/_bootstrap.php';
require_post();

$u = current_user_row();
if (!$u) json_out(['error' => 'not_authenticated'], 401);

$cfg = ai_cfg();
$key = $cfg['openai_key'];
$model = $cfg['openai_model'] ?: 'gpt-4o-mini';
if (!$key) json_out(['error' => 'ai_not_configured']);

$b     = body();
$topic = trim((string)($b['topic'] ?? ''));
$level = strtoupper(trim((string)($b['level'] ?? 'A1')));
if ($topic === '') json_out(['error' => 'no_topic'], 422);
if (mb_strlen($topic) > 120) $topic = mb_substr($topic, 0, 120);
if (!in_array($level, ['A1','A2','B1','B2','C1','C2'], true)) $level = 'A1';

$system = <<<SYS
You are a Spanish-course author for an app called Fluenta. Create ONE self-contained lesson that teaches an English speaker useful Spanish for a real-life scenario, at CEFR level {$level}. Output MUST be a single JSON object (no markdown) with EXACTLY this shape:

{
  "unit": { "title": "<short English title>", "subtitle": "<short English subtitle>", "level": "{$level}", "xp_reward": 20 },
  "grammar": {
    "title": "<English>",
    "intro": "<2-3 sentence HTML explanation; wrap key Spanish in <b></b>>",
    "sections": [ { "tag": "<short Spanish label>", "heading": "<English>", "body": "<HTML explanation with <b>>", "ex": [ {"es":"<Spanish, may use <b>>","en":"<English>","note":"<short English>"}, {"es":"...","en":"...","note":"..."} ] } ],
    "takeaways": ["<English>", "<English>", "<English>"],
    "mistakes": [ {"w":"<wrong Spanish>","r":"<right Spanish>","why":"<English reason>"} ]
  },
  "exercises": [
    {"id":"ex1","type":"multiple_choice","xp":2,"prompt":"<Spanish with ___ or a question>","prompt_en":"<English>","options":[{"id":"a","text":""},{"id":"b","text":""},{"id":"c","text":""},{"id":"d","text":""}],"answer":"<a|b|c|d>","ok":"<short Spanish praise>","no":"<English hint>"},
    {"id":"ex2","type":"fill_blank","xp":2,"prompt":"<Spanish with ___>","prompt_en":"<English>","accepted":["<lowercase answer>"],"ignore_accents":true,"hint":"<English>","ok":"<Spanish>","no":"<English>"},
    {"id":"ex3","type":"word_bank","xp":3,"prompt":"Build: \"<English sentence>\"","tokens":["..."],"answer":["..."],"ok":"<Spanish>","no":"<English>"},
    {"id":"ex4","type":"match_pairs","xp":3,"prompt":"<English instruction>","pairs":[["<es>","<en>"],["<es>","<en>"],["<es>","<en>"],["<es>","<en>"]],"ok":"<Spanish>","no":""},
    {"id":"ex5","type":"listening","xp":3,"prompt":"Type what you hear.","tts":"<Spanish sentence>","accepted":["<lowercase no-accent version>"],"ignore_accents":true,"translation":"<English>","ok":"<Spanish>","no":"<English>"},
    {"id":"ex6","type":"dialogue","xp":3,"scenario":"<English scene + emoji>","lines":[{"speaker":"<Name>","es":"<Spanish>","en":"<English>"},{"speaker":"You","blank":true}],"options":[{"id":"a","text":"<Spanish reply>"},{"id":"b","text":"<Spanish reply>"},{"id":"c","text":"<Spanish reply>"}],"answer":"<a|b|c>","ok":"<Spanish>","no":"<English>"},
    {"id":"ex7","type":"speak","xp":3,"say":"<short natural Spanish sentence>","en":"<English>","ok":"<Spanish praise>","no":"<English hint>"}
  ],
  "vocab": [ {"es":"<Spanish word/phrase>","en":"<English>"} ],
  "completion": { "xp_on_complete": 20, "adds_vocab_to_srs": ["<must equal a vocab.es>","...5 total"] }
}

RULES:
- Everything the learner READS as instructions/explanations is in ENGLISH; only the Spanish being taught is Spanish. Correct accents (á é í ó ú ñ ¿ ¡) and native, level-appropriate grammar.
- The lesson must be about the requested topic and genuinely teach useful vocabulary + one grammar point for it.
- Exactly 7 exercises with the ids/types shown. Every multiple_choice/dialogue "answer" MUST be one of that exercise's option ids. Every word_bank "answer" token MUST appear in "tokens" (add 1-2 distractor tokens). Provide 6-7 vocab items; "adds_vocab_to_srs" must contain exactly 5 strings, each identical to a vocab[].es.
- Output ONLY the JSON object.
SYS;

$payload = json_encode([
  'model'       => $model,
  'messages'    => [
    ['role' => 'system', 'content' => $system],
    ['role' => 'user',   'content' => "Topic: {$topic}\nLevel: {$level}\nCreate the lesson now."],
  ],
  'max_tokens'      => 3000,
  'temperature'     => 0.5,
  'response_format' => ['type' => 'json_object'],
]);

$ctx = stream_context_create(['http' => [
  'method'        => 'POST',
  'header'        => "Authorization: Bearer $key\r\nContent-Type: application/json\r\n",
  'content'       => $payload,
  'timeout'       => 60,
  'ignore_errors' => true,
]]);
$res = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $ctx);
if ($res === false) json_out(['error' => 'upstream_unreachable'], 502);

$j = json_decode($res, true);
$content = $j['choices'][0]['message']['content'] ?? null;
if (!$content) json_out(['error' => 'upstream_error'], 502);

$lesson = json_decode($content, true);
if (!is_array($lesson) || empty($lesson['exercises']) || !is_array($lesson['exercises'])) {
  json_out(['error' => 'bad_lesson']);
}
json_out(['ok' => true, 'lesson' => $lesson]);
