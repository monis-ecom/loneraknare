<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'database_name');
define('DB_USER', 'database_user');
define('DB_PASS', 'database_password');

define('WC_BASE', 'https://shopnordictrend.com/wp-json/wc/v3');
define('WC_KEY', 'ck_replace_me');
define('WC_SECRET', 'cs_replace_me');

// Optional server-side Meta fallback constants.
// define('META_AD_ACCOUNT_ID', 'act_1234567890');
// define('META_API_VERSION', 'v25.0');
// define('META_ACCESS_TOKEN', 'EAAB...');

define('PROFIT_ADMIN_USER', 'monis');
define('PROFIT_ADMIN_PASSWORD', 'replace_this_before_first_run');

define('APP_TIMEZONE', 'Europe/Stockholm');
define('APP_CURRENCY', 'SEK');
define('SESSION_DAYS', 30);
define('WC_CACHE_SECONDS', 300);
define('RATE_LIMIT_REQUESTS', 120);
define('RATE_LIMIT_WINDOW', 60);
define('COUNTED_STATUSES', 'completed,processing,on-hold');

// ── A1: background web push (optional) ────────────────────────────────
// 1. Run generate-vapid.php once and paste the output values below.
// 2. In WooCommerce, add a webhook (topic: Order created) pointing to:
//    https://profit.klivrapps.com/api.php?action=push-webhook&secret=YOUR_SECRET
// Foreground order sound/banner works WITHOUT any of this.
// define('VAPID_PUBLIC_KEY',  '');   // base64url, from generate-vapid.php
// define('VAPID_PRIVATE_KEY', '');   // PEM string, from generate-vapid.php
// define('VAPID_SUBJECT',     'admin@shopnordictrend.com');
// define('PUSH_WEBHOOK_SECRET', 'a-long-random-string');
