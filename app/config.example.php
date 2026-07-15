<?php
// ─────────────────────────────────────────────────────────────────────────────
// Nordic Trend — CONFIG TEMPLATE.  Copy to config.php and fill in REAL values.
// config.php is a PHP file on purpose: web servers never serve PHP source, so
// secrets here can't be dumped even without .htaccess (works on Apache & Nginx).
// NEVER commit the real config.php.
// ─────────────────────────────────────────────────────────────────────────────
return [
    // ── Store / WooCommerce ──────────────────────────────────────────────────
    'site_url'            => 'https://shopnordictrend.com',
    'wc_consumer_key'     => 'ck_PASTE_ROTATED_KEY',
    'wc_consumer_secret'  => 'cs_PASTE_ROTATED_SECRET',
    'wp_user'             => 'wordpress-user@example.com',
    'wp_app_password'     => 'PASTE ROTATED APP PASSWORD',

    // ── Business Data Bridge (optional) ──────────────────────────────────────
    'business_bridge_base' => 'https://shopnordictrend.com/wp-json/as-business/v1',
    'business_bridge_key'  => 'paste_bridge_key_here',

    // ── App / auth ───────────────────────────────────────────────────────────
    'app_version'         => '2.0.0',
    'currency'            => 'kr',

    // ── Web Push (VAPID) — generate with: php tools/make-vapid.php ──
    'push_subject'        => 'mailto:you@example.com',
    'push_cron_token'     => 'set_a_random_token',
    'vapid_public'        => 'PASTE_VAPID_PUBLIC',
    'vapid_private_pem'   => "PASTE_VAPID_PRIVATE_PEM",
    'auth_user'           => 'monis',
    'auth_password_hash'  => 'PASTE_BCRYPT_HASH',   // generate with tools/make-hash.php
    'auth_session_days'   => 30,

    // ── Meta Ads ─────────────────────────────────────────────────────────────
    'meta_ad_account_id'  => '',
    'meta_api_version'    => 'v25.0',
    'meta_access_token'   => '',
    'meta_last_synced_at' => '',

    // ── ROAS Calculator database (merged app) ────────────────────────────────
    'roas_db' => [
        'host' => 'localhost',
        'name' => 'your_db_name',
        'user' => 'your_db_user',
        'pass' => 'PASTE_ROTATED_DB_PASSWORD',
    ],

    // ── Payment-processor fees (editable in Settings → Payment Fees) ──────────
    'payment_fees' => [
        'swish'    => ['pct' => 0,   'flat' => 2],
        'klarna'   => ['pct' => 2.5, 'flat' => 2],
        'kustom'   => ['pct' => 2.5, 'flat' => 2],
        'revolut'  => ['pct' => 1.3, 'flat' => 2],
        '_default' => ['pct' => 2.5, 'flat' => 0],
    ],
];
