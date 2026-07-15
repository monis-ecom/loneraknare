<?php
/* ================================================================
   Fluenta — database configuration
   ----------------------------------------------------------------
   Uses the SAME MySQL database as the Mission Control app.
   Fluenta creates its own prefixed tables (fluenta_users,
   fluenta_progress) so it never touches Mission Control's data.

   Log in with:
     username: monis
     password: Fluenta2026!
   …then set your own password immediately.

   (To fall back to zero-setup local storage instead, set
    'driver' => 'sqlite'.)
   ================================================================ */
return [
  'driver'      => 'mysql',
  'host'        => 'localhost',
  'name'        => 'u137498257_hmsalary',
  'user'        => 'u137498257_monis',
  'pass'        => 'nuzjoz-Fodxi8-hyrxad',
  'charset'     => 'utf8mb4',
  'sqlite_path' => __DIR__ . '/fluenta.sqlite',

  /* ---- AI Tutor (OpenAI) ---------------------------------------
     Paste your OpenAI API key here to switch on the Luma chat tutor.
     Get one at platform.openai.com → API Keys. Leave blank to keep
     the tutor off. This file is blocked from web download by .htaccess. */
  'openai_key'   => '',
  'openai_model' => 'gpt-4o-mini',
  'stt_model'    => 'gpt-4o-mini-transcribe',   // pronunciation check (speech-to-text)
  'tts_model'    => 'gpt-4o-mini-tts',          // lesson audio (text-to-speech)
  'realtime_model' => 'gpt-realtime',           // live voice conversation (Realtime API)
  'realtime_voice' => 'marin',                  // realtime voice (marin/cedar/coral/alloy…)
];
