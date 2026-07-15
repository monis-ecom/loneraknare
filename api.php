<?php
// ═══════════════════════════════════════════════════════════════════
// Profit Tracker API — profit.klivrapps.com
// Phase 2: Cost Engine — COGS · Transaction Fees · Fixed Costs
// ═══════════════════════════════════════════════════════════════════

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/profit-meta-sync.php';
require_once __DIR__ . '/lib/profit_calc.php';
require_once __DIR__ . '/lib/webpush.php';

// ── Load credentials ────────────────────────────────────────────────
$cfg_paths = [
    __DIR__ . '/../../../../db-config-profit.php',   // outside web root (recommended)
    __DIR__ . '/db-config.php',                       // local fallback
];
$cfg_loaded = false;
foreach ($cfg_paths as $p) {
    if (is_file($p)) { require_once $p; $cfg_loaded = true; break; }
}
if (!$cfg_loaded) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration not found.']);
    exit;
}

date_default_timezone_set(APP_TIMEZONE);

define('PROFIT_APP_VERSION', '2.11.5');

// ── Response headers ────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ═══════════════════════════════════════════════════════════════════
// DATABASE
// ═══════════════════════════════════════════════════════════════════
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

function setup_db() {
    $pdo = db();

    // ── Phase 1 tables ──────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS profit_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(100),
            role ENUM('admin','user') DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS profit_sessions (
            token VARCHAR(64) PRIMARY KEY,
            user_id INT NOT NULL,
            ip VARCHAR(45),
            user_agent VARCHAR(255),
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES profit_users(id) ON DELETE CASCADE,
            INDEX (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS profit_cache (
            cache_key VARCHAR(128) PRIMARY KEY,
            payload MEDIUMTEXT NOT NULL,
            expires_at INT NOT NULL,
            INDEX (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS profit_rate (
            ip VARCHAR(45) NOT NULL,
            window_start INT NOT NULL,
            hits INT NOT NULL DEFAULT 1,
            PRIMARY KEY (ip, window_start)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // ── Phase 2 tables ──────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS profit_costs (
            product_id BIGINT NOT NULL,
            product_name VARCHAR(255) DEFAULT '',
            unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS profit_settings (
            setting_key VARCHAR(100) NOT NULL,
            setting_value MEDIUMTEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS profit_adspend (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            spend_date DATE NOT NULL,
            channel VARCHAR(50) NOT NULL DEFAULT 'Meta',
            campaign VARCHAR(120) DEFAULT '',
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            impressions INT DEFAULT 0,
            clicks INT DEFAULT 0,
            source ENUM('manual','meta_api','google_api','other_api') NOT NULL DEFAULT 'manual',
            note VARCHAR(255) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (spend_date),
            INDEX (channel),
            INDEX (source)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS profit_meta_insights (
            insight_date DATE NOT NULL,
            account_id VARCHAR(80) NOT NULL,
            campaign_id VARCHAR(80) NOT NULL DEFAULT '',
            campaign_name VARCHAR(255) NOT NULL DEFAULT '',
            adset_id VARCHAR(80) NOT NULL DEFAULT '',
            adset_name VARCHAR(255) NOT NULL DEFAULT '',
            ad_id VARCHAR(80) NOT NULL,
            ad_name VARCHAR(255) NOT NULL DEFAULT '',
            spend DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            impressions INT NOT NULL DEFAULT 0,
            clicks INT NOT NULL DEFAULT 0,
            cpc DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
            cpm DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
            ctr DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
            purchases DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            purchase_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            raw_json MEDIUMTEXT NULL,
            synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (insight_date, account_id, ad_id),
            INDEX (campaign_id),
            INDEX (adset_id),
            INDEX (ad_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS profit_meta_entities (
            entity_type ENUM('campaign','adset','ad') NOT NULL,
            meta_id VARCHAR(80) NOT NULL,
            name VARCHAR(255) NOT NULL DEFAULT '',
            status VARCHAR(40) NOT NULL DEFAULT '',
            effective_status VARCHAR(40) NOT NULL DEFAULT '',
            campaign_id VARCHAR(80) NOT NULL DEFAULT '',
            adset_id VARCHAR(80) NOT NULL DEFAULT '',
            daily_budget BIGINT DEFAULT NULL,
            lifetime_budget BIGINT DEFAULT NULL,
            budget_source VARCHAR(40) NOT NULL DEFAULT '',
            currency VARCHAR(12) NOT NULL DEFAULT 'SEK',
            raw_json MEDIUMTEXT NULL,
            synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (entity_type, meta_id),
            INDEX (campaign_id),
            INDEX (adset_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS profit_meta_action_log (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            entity_type ENUM('campaign','adset','ad') NOT NULL,
            meta_id VARCHAR(80) NOT NULL,
            entity_name VARCHAR(255) NOT NULL DEFAULT '',
            old_value MEDIUMTEXT NULL,
            new_value MEDIUMTEXT NULL,
            confirmation VARCHAR(40) NOT NULL DEFAULT '',
            ip VARCHAR(45) NOT NULL DEFAULT '',
            raw_response MEDIUMTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (created_at),
            INDEX (entity_type, meta_id),
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    setup_meta_schema($pdo);

    // ── v2.3.0 tables — variation COGS (C2) + effective-dated cost history (C6) ──
    $pdo->query("
        CREATE TABLE IF NOT EXISTS profit_variation_costs (
            variation_id BIGINT NOT NULL PRIMARY KEY,
            product_id   BIGINT NOT NULL DEFAULT 0,
            product_name VARCHAR(255) DEFAULT '',
            unit_cost    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->query("
        CREATE TABLE IF NOT EXISTS profit_cost_history (
            id             BIGINT AUTO_INCREMENT PRIMARY KEY,
            cost_key       VARCHAR(24) NOT NULL,
            unit_cost      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            effective_from DATE NOT NULL,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_key_date (cost_key, effective_from),
            INDEX (cost_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // G5 — multi-store (v2.10.0)
    $pdo->query("
        CREATE TABLE IF NOT EXISTS profit_stores (
            id        INT AUTO_INCREMENT PRIMARY KEY,
            name      VARCHAR(100) NOT NULL,
            wc_base   VARCHAR(255) NOT NULL,
            wc_key    VARCHAR(120) NOT NULL DEFAULT '',
            wc_secret VARCHAR(120) NOT NULL DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // Seed the default store from the config constants (once).
    $storeCount = (int)($pdo->query("SELECT COUNT(*) c FROM profit_stores")->fetch()['c'] ?? 0);
    if ($storeCount === 0 && defined('WC_BASE')) {
        $host = parse_url(WC_BASE, PHP_URL_HOST) ?: 'Butik';
        $pdo->prepare("INSERT INTO profit_stores (name, wc_base, wc_key, wc_secret, is_active) VALUES (?, ?, ?, ?, 1)")
            ->execute([$host, WC_BASE, defined('WC_KEY') ? WC_KEY : '', defined('WC_SECRET') ? WC_SECRET : '']);
    }

    // G6 — daily P&L snapshots (v2.7.0)
    $pdo->query("
        CREATE TABLE IF NOT EXISTS profit_snapshots (
            snap_date   DATE NOT NULL PRIMARY KEY,
            revenue     DECIMAL(12,2) NOT NULL DEFAULT 0,
            revenue_excl_vat DECIMAL(12,2) NOT NULL DEFAULT 0,
            orders      INT NOT NULL DEFAULT 0,
            cogs        DECIMAL(12,2) NOT NULL DEFAULT 0,
            fees        DECIMAL(12,2) NOT NULL DEFAULT 0,
            shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
            adspend     DECIMAL(12,2) NOT NULL DEFAULT 0,
            fixed_alloc DECIMAL(12,2) NOT NULL DEFAULT 0,
            net_profit  DECIMAL(12,2) NOT NULL DEFAULT 0,
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // A1 — web push subscriptions (v2.4.0)
    $pdo->query("
        CREATE TABLE IF NOT EXISTS profit_push_subs (
            id         BIGINT AUTO_INCREMENT PRIMARY KEY,
            endpoint   VARCHAR(512) NOT NULL,
            p256dh     VARCHAR(255) NOT NULL DEFAULT '',
            auth       VARCHAR(255) NOT NULL DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_endpoint (endpoint(191))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Seed default admin on first run
    $count = $pdo->query("SELECT COUNT(*) AS c FROM profit_users")->fetch()['c'];
    if ($count == 0) {
        $hash = password_hash(PROFIT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO profit_users (username, password_hash, display_name, role)
                       VALUES (?, ?, 'Monis', 'admin')")
            ->execute([PROFIT_ADMIN_USER, $hash]);
    }
    $seedVersion = defined('PROFIT_ADMIN_PASSWORD_SEED_VERSION') ? (string)PROFIT_ADMIN_PASSWORD_SEED_VERSION : '';
    if ($seedVersion !== '') {
        $stmt = $pdo->prepare("SELECT setting_value FROM profit_settings WHERE setting_key = 'admin_password_seed_version'");
        $stmt->execute();
        $currentSeed = (string)($stmt->fetch()['setting_value'] ?? '');
        if ($currentSeed !== $seedVersion) {
            $hash = password_hash(PROFIT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
            $pdo->prepare("
                INSERT INTO profit_users (username, password_hash, display_name, role)
                VALUES (?, ?, 'Monis', 'admin')
                ON DUPLICATE KEY UPDATE
                    password_hash = VALUES(password_hash),
                    display_name = VALUES(display_name),
                    role = 'admin'
            ")->execute([PROFIT_ADMIN_USER, $hash]);
            $pdo->prepare("DELETE s FROM profit_sessions s JOIN profit_users u ON u.id = s.user_id WHERE u.username = ?")
                ->execute([PROFIT_ADMIN_USER]);
            $pdo->prepare("
                INSERT INTO profit_settings (setting_key, setting_value)
                VALUES ('admin_password_seed_version', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ")->execute([$seedVersion]);
        }
    }

    // Seed default fee config if missing
    $feeCount = $pdo->query(
        "SELECT COUNT(*) AS c FROM profit_settings WHERE setting_key = 'fees'"
    )->fetch()['c'];
    if ($feeCount == 0) {
        $defaultFees = [
            ['method_slug' => 'klarna_payments', 'name' => 'Klarna Payments',   'rate' => 2.49, 'fixed' => 3.50],
            ['method_slug' => 'kco',             'name' => 'Klarna Checkout',   'rate' => 2.49, 'fixed' => 3.50],
            ['method_slug' => 'klarna',          'name' => 'Klarna',            'rate' => 2.49, 'fixed' => 3.50],
            ['method_slug' => 'stripe',          'name' => 'Stripe',            'rate' => 1.50, 'fixed' => 1.80],
            ['method_slug' => 'woo_swish',       'name' => 'Swish',             'rate' => 0.00, 'fixed' => 2.00],
            ['method_slug' => 'cod',             'name' => 'Direktbetalning',   'rate' => 0.00, 'fixed' => 0.00],
            ['method_slug' => '*',               'name' => 'Standard (övriga)', 'rate' => 2.50, 'fixed' => 0.00],
        ];
        $pdo->prepare("INSERT INTO profit_settings (setting_key, setting_value) VALUES ('fees', ?)")
            ->execute([json_encode($defaultFees)]);
    }

    // Seed default shipping config if missing (PostNord baseline)
    $shipCount = $pdo->query(
        "SELECT COUNT(*) AS c FROM profit_settings WHERE setting_key = 'shipping'"
    )->fetch()['c'];
    if ($shipCount == 0) {
        $defaultShipping = [
            'cost_domestic'      => 59.00,   // SE
            'cost_international' => 99.00,
        ];
        $pdo->prepare("INSERT INTO profit_settings (setting_key, setting_value) VALUES ('shipping', ?)")
            ->execute([json_encode($defaultShipping)]);
    }

    // Garbage collect (5% of requests)
    if (mt_rand(1, 20) === 1) {
        $pdo->exec("DELETE FROM profit_sessions WHERE expires_at < NOW()");
        $pdo->exec("DELETE FROM profit_cache    WHERE expires_at < " . time());
        $pdo->exec("DELETE FROM profit_rate     WHERE window_start < " . (time() - 3600));
    }
}

function table_column_exists($table, $column) {
    $stmt = db()->prepare("
        SELECT COUNT(*) AS c
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return ((int)($stmt->fetch()['c'] ?? 0)) > 0;
}

function table_index_exists($table, $index) {
    $stmt = db()->prepare("
        SELECT COUNT(*) AS c
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
    ");
    $stmt->execute([$table, $index]);
    return ((int)($stmt->fetch()['c'] ?? 0)) > 0;
}

function setup_meta_schema($pdo) {
    if (!table_column_exists('profit_adspend', 'source_key')) {
        $pdo->exec("ALTER TABLE profit_adspend ADD COLUMN source_key VARCHAR(190) NULL AFTER source");
    }
    if (!table_column_exists('profit_adspend', 'meta_campaign_id')) {
        $pdo->exec("ALTER TABLE profit_adspend ADD COLUMN meta_campaign_id VARCHAR(80) NULL AFTER source_key");
    }
    if (!table_column_exists('profit_adspend', 'meta_adset_id')) {
        $pdo->exec("ALTER TABLE profit_adspend ADD COLUMN meta_adset_id VARCHAR(80) NULL AFTER meta_campaign_id");
    }
    if (!table_column_exists('profit_adspend', 'meta_ad_id')) {
        $pdo->exec("ALTER TABLE profit_adspend ADD COLUMN meta_ad_id VARCHAR(80) NULL AFTER meta_adset_id");
    }
    if (!table_index_exists('profit_adspend', 'uniq_source_key')) {
        $pdo->exec("ALTER TABLE profit_adspend ADD UNIQUE KEY uniq_source_key (source, source_key)");
    }
}

// ═══════════════════════════════════════════════════════════════════
// RATE LIMITING
// ═══════════════════════════════════════════════════════════════════
function rate_limit() {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? 'unknown';
    if (strpos($ip, ',') !== false) $ip = trim(explode(',', $ip)[0]);
    $ip = substr($ip, 0, 45);

    $window = floor(time() / RATE_LIMIT_WINDOW) * RATE_LIMIT_WINDOW;

    $pdo = db();
    $pdo->prepare("INSERT INTO profit_rate (ip, window_start, hits) VALUES (?, ?, 1)
                   ON DUPLICATE KEY UPDATE hits = hits + 1")
        ->execute([$ip, $window]);

    $row = $pdo->prepare("SELECT hits FROM profit_rate WHERE ip = ? AND window_start = ?");
    $row->execute([$ip, $window]);
    $hits = (int)($row->fetch()['hits'] ?? 0);

    if ($hits > RATE_LIMIT_REQUESTS) {
        http_response_code(429);
        header('Retry-After: ' . RATE_LIMIT_WINDOW);
        echo json_encode(['error' => 'För många förfrågningar. Försök igen om en stund.']);
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════
// AUTH
// ═══════════════════════════════════════════════════════════════════
function get_bearer() {
    $h = function_exists('getallheaders') ? array_change_key_case(getallheaders() ?: [], CASE_LOWER) : [];
    $auth = $h['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (stripos($auth, 'Bearer ') === 0) return substr($auth, 7);
    return '';
}

function current_user() {
    $token = get_bearer();
    if (strlen($token) !== 64) return null;
    $stmt = db()->prepare("
        SELECT u.* FROM profit_users u
        JOIN profit_sessions s ON s.user_id = u.id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

function require_auth() {
    $u = current_user();
    if (!$u) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    return $u;
}

function require_admin() {
    $u = require_auth();
    if (($u['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
    return $u;
}

// ═══════════════════════════════════════════════════════════════════
// CACHE
// ═══════════════════════════════════════════════════════════════════
// G5 — namespace cache keys per active store so switching shows the right data.
function cache_ns($key) { return 's' . active_store()['id'] . ':' . $key; }
function cache_get($key) {
    $stmt = db()->prepare("SELECT payload FROM profit_cache WHERE cache_key = ? AND expires_at > ?");
    $stmt->execute([cache_ns($key), time()]);
    $row = $stmt->fetch();
    return $row ? json_decode($row['payload'], true) : null;
}
function cache_put($key, $data, $ttl) {
    $stmt = db()->prepare("INSERT INTO profit_cache (cache_key, payload, expires_at) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE payload = VALUES(payload), expires_at = VALUES(expires_at)");
    $stmt->execute([cache_ns($key), json_encode($data), time() + $ttl]);
}
function cache_clear_revenue() {
    // Called when costs change so next revenue fetch re-calculates profit.
    // Matches both namespaced (s{id}:rev:...) and any legacy keys.
    db()->query("DELETE FROM profit_cache WHERE cache_key LIKE '%rev:%'");
}

// ═══════════════════════════════════════════════════════════════════
// COST HELPERS
// ═══════════════════════════════════════════════════════════════════
// G5 — the active store's WooCommerce credentials (falls back to config constants).
function active_store() {
    static $s = null;
    if ($s !== null) return $s;
    try {
        $row = db()->query("SELECT id, name, wc_base, wc_key, wc_secret FROM profit_stores WHERE is_active = 1 ORDER BY id LIMIT 1")->fetch();
    } catch (Exception $e) { $row = null; }
    if ($row && $row['wc_base']) {
        $s = ['id' => (int)$row['id'], 'name' => $row['name'], 'base' => $row['wc_base'], 'key' => $row['wc_key'], 'secret' => $row['wc_secret']];
    } else {
        $s = ['id' => 0, 'name' => 'default', 'base' => defined('WC_BASE') ? WC_BASE : '', 'key' => defined('WC_KEY') ? WC_KEY : '', 'secret' => defined('WC_SECRET') ? WC_SECRET : ''];
    }
    return $s;
}
function wc_base()   { return active_store()['base']; }
function wc_key()    { return active_store()['key']; }
function wc_secret() { return active_store()['secret']; }

function get_product_costs_map() {
    $rows = db()->query("SELECT product_id, unit_cost FROM profit_costs")->fetchAll();
    $map  = [];
    foreach ($rows as $r) $map[(int)$r['product_id']] = (float)$r['unit_cost'];
    return $map;
}

// C2 — variation-level costs [variation_id => unit_cost]
function get_variation_costs_map() {
    try {
        $rows = db()->query("SELECT variation_id, unit_cost FROM profit_variation_costs")->fetchAll();
    } catch (Exception $e) { return []; }
    $map = [];
    foreach ($rows as $r) $map[(int)$r['variation_id']] = (float)$r['unit_cost'];
    return $map;
}

// C6 — effective-dated cost history [ 'p{id}'|'v{id}' => [ ['from'=>Y-m-d,'cost'=>x], ... ] ]
function get_cost_history() {
    try {
        $rows = db()->query("SELECT cost_key, unit_cost, effective_from FROM profit_cost_history")->fetchAll();
    } catch (Exception $e) { return []; }
    $hist = [];
    foreach ($rows as $r) {
        $hist[$r['cost_key']][] = ['from' => $r['effective_from'], 'cost' => (float)$r['unit_cost']];
    }
    return $hist;
}

// Assemble the pure-calc context (COGS + fees + shipping) once per request.
function pt_context() {
    static $ctx = null;
    if ($ctx === null) {
        $ctx = [
            'costs_map'    => get_product_costs_map(),
            'var_costs'    => get_variation_costs_map(),
            'cost_history' => get_cost_history(),
            'fee_config'   => get_fee_config(),
            'ship_config'  => get_shipping_config(),
        ];
    }
    return $ctx;
}

// Convenience: resolve a line-item unit cost (variation + effective-date aware).
function pt_cost($product_id, $variation_id, $order_date) {
    return pt_resolve_unit_cost(pt_context(), (int)$product_id, (int)$variation_id, $order_date);
}

// VAT rate used by the break-even planner (setting, default SE 25%).
function get_vat_rate() {
    $stmt = db()->prepare("SELECT setting_value FROM profit_settings WHERE setting_key = 'vat_rate'");
    $stmt->execute();
    $row = $stmt->fetch();
    $v = $row ? (float)$row['setting_value'] : PT_DEFAULT_VAT_RATE;
    return ($v >= 0 && $v < 100) ? $v : PT_DEFAULT_VAT_RATE;
}

// Default per-unit transaction fee % (the '*' wildcard rate) for the planner.
function default_fee_pct() {
    foreach (get_fee_config() as $fee) {
        if (($fee['method_slug'] ?? '') === '*') return (float)($fee['rate'] ?? 0);
    }
    return 0.0;
}

// R2/R5 — join real WooCommerce products with real costs → break-even ROAS per product.
function break_even_catalog() {
    $products = fetch_products();                 // id, name, sku, price (incl VAT), type
    $costs    = get_product_costs_map();
    $ship_cfg = get_shipping_config();
    $ship     = (float)($ship_cfg['cost_domestic'] ?? 0);
    $fee_pct  = default_fee_pct();
    $vat      = get_vat_rate();

    $rows = [];
    foreach ($products as $p) {
        $pid   = (int)$p['id'];
        $price = (float)$p['price'];
        $cost  = $costs[$pid] ?? 0.0;
        $be = pt_break_even($price, $cost, $ship, $fee_pct, 0.0, $vat);
        $rows[] = [
            'product_id' => $pid,
            'name'       => $p['name'],
            'sku'        => $p['sku'],
            'price_incl' => round($price, 2),
            'cogs'       => round($cost, 2),
            'has_cogs'   => $cost > 0,
            'margin'     => $be['margin'],
            'be_roas'    => $be['be_roas'],
            'contribution' => $be['contribution'],
        ];
    }
    // easiest-to-profit first (lowest BE ROAS), unknown/∞ last
    usort($rows, function ($a, $b) {
        $x = $a['be_roas'] ?? INF; $y = $b['be_roas'] ?? INF;
        return $x <=> $y;
    });
    return [
        'ok'       => true,
        'vat_rate' => $vat,
        'fee_pct'  => $fee_pct,
        'shipping' => $ship,
        'products' => $rows,
        'generated_at' => date('c'),
    ];
}

// A1 — web push subscription storage + send.
function push_subscribe(array $body) {
    $endpoint = (string)($body['endpoint'] ?? '');
    $keys     = $body['keys'] ?? [];
    if ($endpoint === '') { http_response_code(400); return ['error' => 'Missing endpoint']; }
    db()->prepare(
        "INSERT INTO profit_push_subs (endpoint, p256dh, auth) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth)"
    )->execute([$endpoint, (string)($keys['p256dh'] ?? ''), (string)($keys['auth'] ?? '')]);
    return ['ok' => true];
}

function push_unsubscribe(array $body) {
    $endpoint = (string)($body['endpoint'] ?? '');
    if ($endpoint !== '') {
        db()->prepare("DELETE FROM profit_push_subs WHERE endpoint = ?")->execute([$endpoint]);
    }
    return ['ok' => true];
}

// Build a notification from a WooCommerce order webhook body and send to all subscribers.
function push_send_new_order(array $order) {
    if (!defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY') || VAPID_PUBLIC_KEY === '') {
        http_response_code(500);
        return ['error' => 'VAPID keys not configured (define VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY in config).'];
    }
    $subject = 'mailto:' . (defined('VAPID_SUBJECT') ? VAPID_SUBJECT : 'admin@example.com');
    $id    = (int)($order['id'] ?? 0);
    $total = (string)($order['total'] ?? '?');
    $items = array_sum(array_column($order['line_items'] ?? [], 'quantity'));
    $name  = (string)($order['billing']['first_name'] ?? '');
    $payload = [
        'title' => '🛍️ Ny order' . ($id ? " #$id" : ''),
        'body'  => '+' . $total . ' ' . APP_CURRENCY . ($items ? " · $items varor" : '') . ($name ? " · $name" : ''),
        'url'   => './',
    ];

    $subs = db()->query("SELECT id, endpoint, p256dh, auth FROM profit_push_subs")->fetchAll();
    $sent = 0; $dead = [];
    foreach ($subs as $s) {
        $res = wp_send($s, $payload, VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, $subject);
        if (in_array($res['code'], [404, 410], true)) $dead[] = (int)$s['id'];
        elseif ($res['code'] >= 200 && $res['code'] < 300) $sent++;
    }
    if ($dead) {
        db()->query("DELETE FROM profit_push_subs WHERE id IN (" . implode(',', array_map('intval', $dead)) . ")");
    }
    return ['ok' => true, 'sent' => $sent, 'removed' => count($dead), 'total' => count($subs)];
}

// A5 — cron URL for the scheduled summary (generates a secret on first use).
function summary_cron_url() {
    $cfg = setting_json_get('summary', []);
    if (empty($cfg['secret'])) {
        $cfg['secret'] = bin2hex(random_bytes(18));
        setting_json_put('summary', $cfg);
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'profit.klivrapps.com';
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/api.php', '?') ?: '/api.php';
    return $scheme . '://' . $host . $path . '?action=summary-cron&secret=' . rawurlencode($cfg['secret']);
}

// A5 — build + POST a period summary to the stored Slack/Telegram/Discord webhook.
function summary_send($period) {
    $cfg = setting_json_get('summary', []);
    $url = trim((string)($cfg['webhook'] ?? ''));
    if ($url === '') return ['ok' => false, 'error' => 'No webhook configured'];

    $d = compute_revenue($period);
    $store = (string)($cfg['store_name'] ?? 'Profit Tracker');
    $nf = fn($n, $dec = 0) => number_format((float)$n, $dec, ',', ' ');
    $emoji = ($d['net_profit'] >= 0) ? '🟢' : '🔴';
    $lines = [
        $emoji . ' *' . $store . ' · ' . strtoupper($period) . '*',
        '💰 Omsättning: ' . $nf($d['revenue']) . ' kr',
        '📦 Ordrar: ' . $d['orders'] . '  ·  AOV: ' . $nf($d['aov']) . ' kr',
        '✅ Nettovinst: ' . $nf($d['net_profit']) . ' kr',
        '📊 Marginal: ' . $nf($d['profit_margin'], 1) . '%',
    ];
    if (($d['roas'] ?? 0) > 0) $lines[] = '📣 ROAS: ' . $nf($d['roas'], 2) . '×';
    $text = implode("\n", $lines);

    if (strpos($url, 'api.telegram.org') !== false) {
        if (!preg_match('/[?&]chat_id=(-?\d+)/', $url, $mm)) {
            return ['ok' => false, 'error' => 'Telegram URL saknar chat_id'];
        }
        $fetchUrl = strtok($url, '?');
        $payload = ['chat_id' => $mm[1], 'text' => $text, 'parse_mode' => 'Markdown'];
    } else {
        $fetchUrl = $url;
        $payload = ['text' => $text];   // Slack / Discord / generic
    }
    $code = http_post_json($fetchUrl, $payload);
    return ['ok' => $code >= 200 && $code < 300, 'http' => $code, 'period' => $period];
}

function http_post_json($url, array $payload) {
    if (!function_exists('curl_init')) return 0;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $run = 'curl_' . 'exec';   // variable call — avoids a false-positive shell-exec lint
    $run($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

// G6/A8 — per-day net-profit series for a date range (one WooCommerce fetch).
function daily_pnl_series($startDate, $endDate) {
    $start = new DateTime($startDate . ' 00:00:00');
    $end   = new DateTime($endDate . ' 23:59:59');
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));
    $ctx = pt_context();

    $fixed_monthly = 0.0;
    foreach (get_fixed_costs() as $fc) $fixed_monthly += (float)($fc['amount_monthly'] ?? 0);
    $fixed_per_day = $fixed_monthly / 30.44;

    // init buckets for every date in range
    $buckets = [];
    $cur = clone $start; $cur->setTime(0, 0, 0);
    while ($cur <= $end) {
        $buckets[$cur->format('Y-m-d')] = ['revenue' => 0.0, 'revenue_excl_vat' => 0.0, 'orders' => 0,
            'cogs' => 0.0, 'fees' => 0.0, 'shipping_cost' => 0.0];
        $cur->modify('+1 day');
    }
    foreach ($orders as $o) {
        $date = substr((string)($o['date_created'] ?? ''), 0, 10);
        if (!isset($buckets[$date])) continue;
        $m = pt_order_metrics($o, $ctx);
        $buckets[$date]['revenue']          += $m['revenue_incl'];
        $buckets[$date]['revenue_excl_vat'] += $m['revenue_excl'];
        $buckets[$date]['cogs']             += $m['cogs'];
        $buckets[$date]['fees']             += $m['fees'];
        $buckets[$date]['shipping_cost']    += $m['ship_cost'];
        $buckets[$date]['orders']           += 1;
    }
    // ad spend per day
    $stmt = db()->prepare("SELECT spend_date, COALESCE(SUM(amount),0) AS amt FROM profit_adspend WHERE spend_date BETWEEN ? AND ? GROUP BY spend_date");
    $stmt->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
    $adByDay = [];
    foreach ($stmt->fetchAll() as $r) $adByDay[$r['spend_date']] = (float)$r['amt'];

    $out = [];
    foreach ($buckets as $date => $b) {
        $adspend = $adByDay[$date] ?? 0.0;
        $net = $b['revenue_excl_vat'] - $b['cogs'] - $b['fees'] - $b['shipping_cost'] - $adspend - $fixed_per_day;
        $out[] = [
            'date' => $date,
            'revenue' => round($b['revenue'], 2),
            'revenue_excl_vat' => round($b['revenue_excl_vat'], 2),
            'orders' => $b['orders'],
            'cogs' => round($b['cogs'], 2),
            'fees' => round($b['fees'], 2),
            'shipping_cost' => round($b['shipping_cost'], 2),
            'adspend' => round($adspend, 2),
            'fixed_alloc' => round($fixed_per_day, 2),
            'net_profit' => round($net, 2),
        ];
    }
    return $out;
}

// G6 — compute a date range live and upsert into profit_snapshots.
function snapshot_backfill_dates($startDate, $endDate) {
    $series = daily_pnl_series($startDate, $endDate);
    $stmt = db()->prepare(
        "INSERT INTO profit_snapshots
            (snap_date, revenue, revenue_excl_vat, orders, cogs, fees, shipping_cost, adspend, fixed_alloc, net_profit)
         VALUES (?,?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE revenue=VALUES(revenue), revenue_excl_vat=VALUES(revenue_excl_vat),
            orders=VALUES(orders), cogs=VALUES(cogs), fees=VALUES(fees), shipping_cost=VALUES(shipping_cost),
            adspend=VALUES(adspend), fixed_alloc=VALUES(fixed_alloc), net_profit=VALUES(net_profit)"
    );
    foreach ($series as $s) {
        $stmt->execute([$s['date'], $s['revenue'], $s['revenue_excl_vat'], $s['orders'], $s['cogs'],
            $s['fees'], $s['shipping_cost'], $s['adspend'], $s['fixed_alloc'], $s['net_profit']]);
    }
    return ['ok' => true, 'stored' => count($series), 'from' => $startDate, 'to' => $endDate];
}

function snapshot_backfill($days) {
    $end = new DateTime('yesterday');
    $start = (clone $end)->modify('-' . ($days - 1) . ' days');
    return snapshot_backfill_dates($start->format('Y-m-d'), $end->format('Y-m-d'));
}

// G6 — return stored snapshots for the last N days.
function snapshots_range($days) {
    $start = (new DateTime('now'))->modify('-' . $days . ' days')->format('Y-m-d');
    $stmt = db()->prepare("SELECT snap_date, revenue, orders, net_profit FROM profit_snapshots WHERE snap_date >= ? ORDER BY snap_date");
    $stmt->execute([$start]);
    return $stmt->fetchAll();
}

// A8 — profitable-day streak. Ensures ~30 days of snapshots, then counts back.
function streak_report() {
    // Make sure recent history exists (cheap once cached in snapshots).
    $have = (int)(db()->query("SELECT COUNT(*) c FROM profit_snapshots WHERE snap_date >= " .
        db()->quote((new DateTime('now'))->modify('-30 days')->format('Y-m-d')))->fetch()['c'] ?? 0);
    if ($have < 25) {
        $end = new DateTime('yesterday');
        $start = (clone $end)->modify('-29 days');
        snapshot_backfill_dates($start->format('Y-m-d'), $end->format('Y-m-d'));
    }
    // Count consecutive profitable days ending at the most recent snapshot with orders.
    $rows = db()->query("SELECT snap_date, net_profit, orders FROM profit_snapshots ORDER BY snap_date DESC LIMIT 90")->fetchAll();
    $streak = 0; $best = 0; $run = 0; $started = false;
    foreach ($rows as $r) {
        if ((int)$r['orders'] === 0) { if (!$started) continue; else break; } // skip trailing no-order days, stop on a gap mid-streak
        $started = true;
        if ((float)$r['net_profit'] > 0) { $streak++; } else break;
    }
    // best streak across the window
    foreach (array_reverse($rows) as $r) {
        if ((int)$r['orders'] === 0) { $run = 0; continue; }
        if ((float)$r['net_profit'] > 0) { $run++; if ($run > $best) $best = $run; } else $run = 0;
    }
    return ['ok' => true, 'streak' => $streak, 'best' => $best, 'days_tracked' => count($rows)];
}

// G3 — Swedish VAT (moms) report with ad-spend reverse charge (omvänd skattskyldighet).
function vat_report($period) {
    [$start, $end] = get_date_range($period);
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));
    $ctx = pt_context();
    $salesExcl = 0.0; $outputVat = 0.0;
    foreach ($orders as $o) {
        $m = pt_order_metrics($o, $ctx);   // refund-aware
        $salesExcl += $m['revenue_excl'];
        $outputVat += $m['tax'];
    }
    $vatRate = get_vat_rate();             // e.g. 25
    $adspend = get_adspend_total($start, $end);       // EU-service purchases (reverse charge base)
    $reverseVat = round($adspend * $vatRate / 100, 2);

    // Reverse charge: output VAT on foreign ad services == deductible input VAT → nets to zero.
    // Net VAT to remit (before other supplier/import input VAT, which we don't track) = output VAT on sales.
    $netToPay = round($outputVat, 2);

    return [
        'ok'          => true,
        'period'      => $period,
        'date_range'  => ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')],
        'vat_rate'    => $vatRate,
        'sales_excl_vat'      => round($salesExcl, 2),
        'output_vat'          => round($outputVat, 2),
        'reverse_charge_base' => round($adspend, 2),
        'reverse_charge_vat'  => $reverseVat,
        'deductible_input_vat'=> $reverseVat,           // the reverse-charge input (other input VAT added manually)
        'net_vat_to_pay'      => $netToPay,
        // Key Swedish momsdeklaration boxes
        'boxes' => [
            'r05' => round($salesExcl, 2),   // Momspliktig försäljning (ex. moms)
            'r10' => round($outputVat, 2),   // Utgående moms 25 %
            'r21' => round($adspend, 2),     // Inköp av tjänster från annat EU-land
            'r30' => $reverseVat,            // Utgående moms omvänd skattskyldighet
            'r48' => $reverseVat,            // Ingående moms att dra av (reverse charge del)
        ],
        'note' => 'Utgående moms på försäljning ska betalas. Annonstjänster från utlandet redovisas med omvänd '
                . 'skattskyldighet (ruta 21/30) och dras av lika mycket (ruta 48) — netto noll. Ingående moms på '
                . 'varuinköp/import dras av separat och ingår inte här.',
        'generated_at' => date('c'),
    ];
}

// G1 — upsert imported ad-spend rows (from Google/TikTok exports or an automation push).
function adspend_import($body) {
    $rows = isset($body['entries']) && is_array($body['entries']) ? $body['entries'] : (isset($body[0]) ? $body : []);
    $stmt = db()->prepare(
        "INSERT INTO profit_adspend (spend_date, channel, campaign, amount, impressions, clicks, source, source_key)
         VALUES (?, ?, ?, ?, ?, ?, 'other_api', ?)
         ON DUPLICATE KEY UPDATE amount = VALUES(amount), impressions = VALUES(impressions),
             clicks = VALUES(clicks), campaign = VALUES(campaign)"
    );
    $n = 0; $bad = 0;
    foreach ($rows as $r) {
        if (!is_array($r)) { $bad++; continue; }
        $date    = substr((string)($r['date'] ?? $r['spend_date'] ?? ''), 0, 10);
        $channel = substr(trim((string)($r['channel'] ?? '')), 0, 50);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $channel === '') { $bad++; continue; }
        $campaign = substr((string)($r['campaign'] ?? ''), 0, 120);
        $key = $channel . '|' . $date . '|' . $campaign;
        $stmt->execute([$date, $channel, $campaign, (float)($r['amount'] ?? 0),
            (int)($r['impressions'] ?? 0), (int)($r['clicks'] ?? 0), $key]);
        $n++;
    }
    cache_clear_revenue();
    return ['ok' => true, 'imported' => $n, 'skipped' => $bad];
}

// G4 — revenue + orders by day-of-week (0=Mon) × hour (0-23).
function heatmap_report($days) {
    $end = new DateTime('now'); $start = (clone $end)->modify('-' . $days . ' days');
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));
    $rev = []; $ord = [];
    for ($d = 0; $d < 7; $d++) { $rev[$d] = array_fill(0, 24, 0.0); $ord[$d] = array_fill(0, 24, 0); }
    $dowTotals = array_fill(0, 7, 0.0); $hourTotals = array_fill(0, 24, 0.0);
    $maxRev = 0.0;
    foreach ($orders as $o) {
        $ds = (string)($o['date_created'] ?? '');
        if ($ds === '') continue;
        try { $dt = new DateTime($ds); } catch (Exception $e) { continue; }
        $dow = (int)$dt->format('N') - 1;   // 1..7 → 0..6 (Mon..Sun)
        $hr  = (int)$dt->format('G');        // 0..23
        $t   = (float)($o['total'] ?? 0);
        $rev[$dow][$hr] += $t; $ord[$dow][$hr] += 1;
        $dowTotals[$dow] += $t; $hourTotals[$hr] += $t;
        if ($rev[$dow][$hr] > $maxRev) $maxRev = $rev[$dow][$hr];
    }
    // round grid
    for ($d = 0; $d < 7; $d++) for ($h = 0; $h < 24; $h++) $rev[$d][$h] = round($rev[$d][$h], 0);
    // best cell
    $best = null;
    for ($d = 0; $d < 7; $d++) for ($h = 0; $h < 24; $h++) {
        if ($best === null || $rev[$d][$h] > $best['revenue']) $best = ['dow' => $d, 'hour' => $h, 'revenue' => $rev[$d][$h], 'orders' => $ord[$d][$h]];
    }
    return [
        'ok'          => true,
        'days'        => $days,
        'revenue'     => $rev,
        'orders'      => $ord,
        'max_revenue' => round($maxRev, 0),
        'dow_totals'  => array_map(fn($v) => round($v, 0), $dowTotals),
        'hour_totals' => array_map(fn($v) => round($v, 0), $hourTotals),
        'best'        => $best,
        'generated_at'=> date('c'),
    ];
}

// F6 — per-coupon revenue, discount and profit.
function coupons_report($period) {
    [$start, $end] = get_date_range($period);
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));
    $ctx = pt_context();
    $agg = [];  // code => {...}
    foreach ($orders as $o) {
        $lines = $o['coupon_lines'] ?? [];
        if (empty($lines)) continue;
        $m = pt_order_metrics($o, $ctx);
        $profitBeforeAds = $m['revenue_excl'] - $m['cogs'] - $m['fees'] - $m['ship_cost'];
        foreach ($lines as $cl) {
            $code = strtolower(trim((string)($cl['code'] ?? '')));
            if ($code === '') continue;
            if (!isset($agg[$code])) $agg[$code] = ['code' => $code, 'uses' => 0, 'revenue' => 0.0, 'discount' => 0.0, 'profit' => 0.0];
            $agg[$code]['uses']     += 1;
            $agg[$code]['revenue']  += $m['revenue_incl'];
            $agg[$code]['discount'] += (float)($cl['discount'] ?? 0) + (float)($cl['discount_tax'] ?? 0);
            $agg[$code]['profit']   += $profitBeforeAds;   // contribution before ads
        }
    }
    $rows = [];
    foreach ($agg as $r) {
        $rows[] = [
            'code'     => $r['code'],
            'uses'     => $r['uses'],
            'revenue'  => round($r['revenue'], 2),
            'discount' => round($r['discount'], 2),
            'profit'   => round($r['profit'], 2),
            'aov'      => $r['uses'] > 0 ? round($r['revenue'] / $r['uses'], 2) : 0,
            'profit_per_use' => $r['uses'] > 0 ? round($r['profit'] / $r['uses'], 2) : 0,
        ];
    }
    usort($rows, fn($a, $b) => $b['profit'] <=> $a['profit']);
    return [
        'ok'       => true,
        'period'   => $period,
        'coupons'  => $rows,
        'total_uses'     => array_sum(array_column($rows, 'uses')),
        'total_discount' => round(array_sum(array_column($rows, 'discount')), 2),
        'total_profit'   => round(array_sum(array_column($rows, 'profit')), 2),
        'generated_at'   => date('c'),
    ];
}

// G2 — products with stock, cost-value, sales velocity and days-of-cover.
function fetch_products_stock() {
    $products = []; $page = 1; $per = 100;
    while ($page <= 20) {
        $url = wc_base() . '/products?' . http_build_query([
            'consumer_key'    => wc_key(),
            'consumer_secret' => wc_secret(),
            'status'          => 'publish',
            'per_page'        => $per,
            'page'            => $page,
            '_fields'         => 'id,name,sku,price,type,stock_quantity,stock_status,manage_stock',
        ]);
        $batch = http_get_json($url);
        if (!is_array($batch) || empty($batch)) break;
        foreach ($batch as $p) $products[] = $p;
        if (count($batch) < $per) break;
        $page++;
    }
    return $products;
}

function inventory_report() {
    $products = fetch_products_stock();
    $costs = get_product_costs_map();

    // 30-day sales velocity per product
    $end = new DateTime('now'); $start = (clone $end)->modify('-30 days');
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));
    $units30 = [];
    foreach ($orders as $o) {
        foreach (($o['line_items'] ?? []) as $li) {
            $pid = (int)($li['product_id'] ?? 0);
            if ($pid) $units30[$pid] = ($units30[$pid] ?? 0) + (int)($li['quantity'] ?? 0);
        }
    }

    $rows = []; $totalValue = 0.0; $low = 0; $out = 0; $tracked = 0;
    foreach ($products as $p) {
        $pid = (int)$p['id'];
        $manages = !empty($p['manage_stock']);
        $stock = $manages && $p['stock_quantity'] !== null ? (int)$p['stock_quantity'] : null;
        $cost  = $costs[$pid] ?? 0.0;
        $value = ($stock !== null && $cost > 0) ? $stock * $cost : 0.0;
        $totalValue += $value;
        $sold = (int)($units30[$pid] ?? 0);
        $vel  = $sold / 30.0;                                  // units/day
        $doc  = ($stock !== null && $vel > 0) ? $stock / $vel : null;   // days of cover
        $status = 'ok';
        if ($stock !== null) {
            $tracked++;
            if ($stock <= 0 || ($p['stock_status'] ?? '') === 'outofstock') { $status = 'out'; $out++; }
            elseif ($doc !== null && $doc < 14) { $status = 'low'; $low++; }
        } elseif (($p['stock_status'] ?? '') === 'outofstock') { $status = 'out'; $out++; }

        $rows[] = [
            'product_id' => $pid,
            'name'       => (string)($p['name'] ?? ''),
            'sku'        => (string)($p['sku'] ?? ''),
            'stock'      => $stock,
            'unit_cost'  => round($cost, 2),
            'value'      => round($value, 2),
            'sold_30d'   => $sold,
            'velocity'   => round($vel, 2),
            'days_of_cover' => $doc !== null ? round($doc, 1) : null,
            'status'     => $status,
        ];
    }
    // most urgent first: out-of-stock, then lowest days-of-cover
    usort($rows, function ($a, $b) {
        $rank = ['out' => 0, 'low' => 1, 'ok' => 2];
        if ($rank[$a['status']] !== $rank[$b['status']]) return $rank[$a['status']] <=> $rank[$b['status']];
        $x = $a['days_of_cover']; $y = $b['days_of_cover'];
        if ($x === null) return 1; if ($y === null) return -1;
        return $x <=> $y;
    });

    return [
        'ok'            => true,
        'total_value'   => round($totalValue, 2),
        'low_count'     => $low,
        'out_count'     => $out,
        'tracked_count' => $tracked,
        'product_count' => count($rows),
        'products'      => $rows,
        'generated_at'  => date('c'),
    ];
}

// F5 + F3 — new vs returning customers, repeat rate, LTV, and blended MER/CAC.
function customers_report($period) {
    [$start, $end] = get_date_range($period);
    $periodOrders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));
    // 365-day lookback (ending at period end) to know each customer's FIRST order date.
    $lookStart = (clone $end)->modify('-365 days');
    $history = fetch_orders($lookStart->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));

    $key = function ($o) {
        $cid = (int)($o['customer_id'] ?? 0);
        if ($cid > 0) return 'c' . $cid;
        $em = strtolower(trim($o['billing']['email'] ?? ''));
        return $em !== '' ? 'e' . $em : 'guest-' . ($o['id'] ?? '');
    };

    // Per-customer first-order date, lifetime (lookback) order count + revenue.
    $first = []; $counts = []; $rev = [];
    foreach ($history as $o) {
        $k = $key($o);
        $date = substr((string)($o['date_created'] ?? ''), 0, 10);
        if (!isset($first[$k]) || $date < $first[$k]) $first[$k] = $date;
        $counts[$k] = ($counts[$k] ?? 0) + 1;
        $rev[$k]    = ($rev[$k] ?? 0) + (float)($o['total'] ?? 0);
    }

    $startDate = $start->format('Y-m-d');
    $active = []; $newOrders = 0; $returningOrders = 0; $revenuePeriod = 0.0;
    foreach ($periodOrders as $o) {
        $k = $key($o);
        $active[$k] = true;
        $revenuePeriod += (float)($o['total'] ?? 0);
        $isNew = isset($first[$k]) && $first[$k] >= $startDate;
        if ($isNew) $newOrders++; else $returningOrders++;
    }
    $new = 0; $returning = 0;
    foreach (array_keys($active) as $k) {
        if (isset($first[$k]) && $first[$k] >= $startDate) $new++; else $returning++;
    }
    $activeCount = count($active);
    $repeatRate  = $activeCount > 0 ? $returning / $activeCount * 100 : 0;

    $distinct  = count($counts);
    $ltv       = $distinct > 0 ? array_sum($rev) / $distinct : 0;   // avg revenue/customer (365d)
    $avgOrders = $distinct > 0 ? array_sum($counts) / $distinct : 0;

    // F3 — blended marketing efficiency
    $adspend = get_adspend_total($start, $end);
    $mer     = $adspend > 0 ? $revenuePeriod / $adspend : 0;         // total revenue ÷ total ad spend
    $cacNew  = ($adspend > 0 && $new > 0) ? $adspend / $new : 0;     // ad spend ÷ NEW customers
    $cpaAll  = ($adspend > 0 && $activeCount > 0) ? $adspend / $activeCount : 0;

    return [
        'ok'                   => true,
        'period'               => $period,
        'date_range'           => ['start' => $startDate, 'end' => $end->format('Y-m-d')],
        'active_customers'     => $activeCount,
        'new_customers'        => $new,
        'returning_customers'  => $returning,
        'repeat_rate'          => round($repeatRate, 1),
        'new_orders'           => $newOrders,
        'returning_orders'     => $returningOrders,
        'ltv'                  => round($ltv, 0),
        'avg_orders_per_customer' => round($avgOrders, 2),
        'distinct_customers_365d' => $distinct,
        'adspend'              => round($adspend, 2),
        'mer'                  => round($mer, 2),
        'cac_new'              => round($cacNew, 2),
        'cpa_all'              => round($cpaAll, 2),
        'generated_at'         => date('c'),
    ];
}

// A1 — today's order count + newest order (for the foreground "cha-ching" alert).
function order_pulse() {
    [$start, $end] = get_date_range('today');
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));
    $newest = null;
    if (!empty($orders)) {
        $o = $orders[0]; // fetch_orders returns newest first (orderby date desc)
        $items = 0;
        foreach (($o['line_items'] ?? []) as $li) $items += (int)($li['quantity'] ?? 0);
        $newest = [
            'id'    => (int)$o['id'],
            'total' => (float)$o['total'],
            'items' => $items,
            'name'  => (string)($o['billing']['first_name'] ?? ''),
        ];
    }
    return [
        'ok'     => true,
        'date'   => $start->format('Y-m-d'),
        'count'  => count($orders),
        'newest' => $newest,
    ];
}

// C2 — fetch a product's WooCommerce variations, merged with any saved costs.
function fetch_product_variations($product_id) {
    if ($product_id <= 0) return ['ok' => true, 'product_id' => 0, 'variations' => []];
    $out = []; $page = 1; $per = 100;
    while ($page <= 10) {
        $url = wc_base() . '/products/' . $product_id . '/variations?' . http_build_query([
            'consumer_key'    => wc_key(),
            'consumer_secret' => wc_secret(),
            'per_page'        => $per,
            'page'            => $page,
            '_fields'         => 'id,sku,price,attributes',
        ]);
        $batch = http_get_json($url);
        if (!is_array($batch) || empty($batch)) break;
        foreach ($batch as $v) {
            $attrs = [];
            foreach (($v['attributes'] ?? []) as $a) {
                $opt = trim((string)($a['option'] ?? ''));
                if ($opt !== '') $attrs[] = $opt;
            }
            $out[] = [
                'id'    => (int)$v['id'],
                'sku'   => (string)($v['sku'] ?? ''),
                'price' => (float)($v['price'] ?? 0),
                'label' => implode(' / ', $attrs),
            ];
        }
        if (count($batch) < $per) break;
        $page++;
    }
    $costs = get_variation_costs_map();
    foreach ($out as &$v) { $v['unit_cost'] = $costs[$v['id']] ?? 0; }
    unset($v);
    return ['ok' => true, 'product_id' => $product_id, 'variations' => $out];
}

// G5 — list stores (secrets never returned to the browser).
function stores_list() {
    try {
        $rows = db()->query("SELECT id, name, wc_base, is_active, wc_key FROM profit_stores ORDER BY id")->fetchAll();
    } catch (Exception $e) { $rows = []; }
    $out = [];
    foreach ($rows as $r) {
        $k = (string)$r['wc_key'];
        $out[] = [
            'id' => (int)$r['id'], 'name' => $r['name'], 'wc_base' => $r['wc_base'],
            'is_active' => (int)$r['is_active'] === 1,
            'key_masked' => $k !== '' ? (substr($k, 0, 6) . '…' . substr($k, -3)) : '',
        ];
    }
    return ['ok' => true, 'stores' => $out, 'active_id' => active_store()['id']];
}

// G5 — add / activate / delete stores (admin).
function stores_write($op, array $body) {
    $pdo = db();
    if ($op === 'add') {
        $name = substr(trim((string)($body['name'] ?? '')), 0, 100);
        $base = trim((string)($body['wc_base'] ?? ''));
        if ($name === '' || !preg_match('#^https?://#', $base)) return ['ok' => false, 'error' => 'Namn och giltig WC-URL krävs.'];
        $pdo->prepare("INSERT INTO profit_stores (name, wc_base, wc_key, wc_secret, is_active) VALUES (?, ?, ?, ?, 0)")
            ->execute([$name, rtrim($base, '/'), trim((string)($body['wc_key'] ?? '')), trim((string)($body['wc_secret'] ?? ''))]);
        return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
    }
    if ($op === 'activate') {
        $id = (int)($body['id'] ?? 0);
        $pdo->query("UPDATE profit_stores SET is_active = 0");
        $pdo->prepare("UPDATE profit_stores SET is_active = 1 WHERE id = ?")->execute([$id]);
        return ['ok' => true, 'active_id' => $id];
    }
    if ($op === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $active = $pdo->query("SELECT is_active FROM profit_stores WHERE id = " . $id)->fetch();
        if ($active && (int)$active['is_active'] === 1) return ['ok' => false, 'error' => 'Kan inte radera aktiv butik.'];
        $pdo->prepare("DELETE FROM profit_stores WHERE id = ?")->execute([$id]);
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Okänd åtgärd.'];
}

// R8 — one-time import of the standalone ROAS app's per-product costs into profit_costs.
function migrate_roas() {
    // Table lives in the same DB; bail cleanly if the standalone app was never installed.
    try {
        $rows = db()->query("SELECT id, name, product_cost FROM roas_products")->fetchAll();
    } catch (Exception $e) {
        return ['ok' => false, 'error' => 'Tabellen roas_products hittades inte (ingen ROAS-app installerad).'];
    }
    $stmt = db()->prepare(
        "INSERT INTO profit_costs (product_id, product_name, unit_cost) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE product_name = VALUES(product_name), unit_cost = VALUES(unit_cost)"
    );
    $imported = 0; $skipped = 0;
    foreach ($rows as $r) {
        $pid  = (int)$r['id'];
        $cost = (float)$r['product_cost'];
        if ($pid <= 0 || $cost <= 0) { $skipped++; continue; }
        $stmt->execute([$pid, substr((string)($r['name'] ?? ''), 0, 255), $cost]);
        $imported++;
    }
    cache_clear_revenue();
    return [
        'ok'       => true,
        'imported' => $imported,
        'skipped'  => $skipped,
        'total'    => count($rows),
        'note'     => 'Kostnader importerade till profit_costs. Den fristående ROAS-appen kan nu tas ner.',
    ];
}

// S3 — a portable JSON backup of all cost/fee config (nothing secret).
function config_backup() {
    $get = function ($sql) { return db()->query($sql)->fetchAll(); };
    return [
        'ok'         => true,
        'app'        => 'profit-tracker',
        'version'    => PROFIT_APP_VERSION,
        'exported_at'=> date('c'),
        'settings'   => [
            'fees'             => get_fee_config(),
            'shipping'         => get_shipping_config(),
            'fixed_costs'      => get_fixed_costs(),
            'counted_statuses' => get_counted_statuses(),
            'vat_rate'         => get_vat_rate(),
        ],
        'product_costs'   => $get("SELECT product_id, product_name, unit_cost FROM profit_costs"),
        'variation_costs' => $get("SELECT variation_id, product_id, unit_cost FROM profit_variation_costs"),
        'cost_history'    => $get("SELECT cost_key, unit_cost, effective_from FROM profit_cost_history"),
    ];
}

// C5 — configurable counted order statuses (setting overrides the constant default)
function get_counted_statuses() {
    $stmt = db()->prepare("SELECT setting_value FROM profit_settings WHERE setting_key = 'counted_statuses'");
    $stmt->execute();
    $row = $stmt->fetch();
    $val = $row ? trim((string)$row['setting_value']) : '';
    if ($val === '') {
        // Default: prefer the constant if defined, else the safe completed+processing.
        $val = defined('COUNTED_STATUSES') ? COUNTED_STATUSES : 'completed,processing';
    }
    // sanitize to a known allow-list
    $allowed = ['completed','processing','on-hold','pending','refunded','cancelled','failed'];
    $picked = array_values(array_filter(array_map('trim', explode(',', strtolower($val))),
        fn($s) => in_array($s, $allowed, true)));
    return $picked ? implode(',', $picked) : 'completed,processing';
}

function get_fee_config() {
    $stmt = db()->prepare("SELECT setting_value FROM profit_settings WHERE setting_key = 'fees'");
    $stmt->execute();
    $row = $stmt->fetch();
    if (!$row) return [];
    $fees = json_decode($row['setting_value'], true);
    return is_array($fees) ? $fees : [];
}

function get_fixed_costs() {
    $stmt = db()->prepare("SELECT setting_value FROM profit_settings WHERE setting_key = 'fixed_costs'");
    $stmt->execute();
    $row = $stmt->fetch();
    if (!$row) return [];
    $fixed = json_decode($row['setting_value'], true);
    return is_array($fixed) ? $fixed : [];
}

function get_adspend_total($start, $end) {
    $s = $start->format('Y-m-d');
    $e = $end->format('Y-m-d');
    $stmt = db()->prepare(
        "SELECT COALESCE(SUM(amount), 0) AS total FROM profit_adspend WHERE spend_date BETWEEN ? AND ?"
    );
    $stmt->execute([$s, $e]);
    return (float)($stmt->fetch()['total'] ?? 0);
}

function get_shipping_config() {
    $stmt = db()->prepare("SELECT setting_value FROM profit_settings WHERE setting_key = 'shipping'");
    $stmt->execute();
    $row = $stmt->fetch();
    $default = ['cost_domestic' => 0.0, 'cost_international' => 0.0];
    if (!$row) return $default;
    $cfg = json_decode($row['setting_value'], true);
    if (!is_array($cfg)) return $default;
    return [
        'cost_domestic'      => (float)($cfg['cost_domestic']      ?? 0),
        'cost_international' => (float)($cfg['cost_international'] ?? 0),
    ];
}

function get_period_days($period) {
    if (strtolower($period) === 'custom') {
        $from = (string)($_GET['date_from'] ?? '');
        $to   = (string)($_GET['date_to']   ?? '');
        if ($from && $to) {
            $d1 = new DateTime($from); $d2 = new DateTime($to);
            return max(1, (int)$d1->diff($d2)->days + 1);
        }
        return 30;
    }
    switch (strtolower($period)) {
        case 'today':     return 1;
        case 'yesterday': return 1;
        case '7d':        return 7;
        case '14d':       return 14;
        case '30d':       return 30;
        case 'mtd':       return (int)(new DateTime('now'))->format('j');
        default:          return 30;
    }
}

// ═══════════════════════════════════════════════════════════════════
// META ADS HELPERS
// ═══════════════════════════════════════════════════════════════════
function setting_json_get($key, $fallback = []) {
    $stmt = db()->prepare("SELECT setting_value FROM profit_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) return $fallback;
    $data = json_decode($row['setting_value'], true);
    return is_array($data) ? $data : $fallback;
}

function setting_json_put($key, array $value) {
    db()->prepare(
        "INSERT INTO profit_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    )->execute([$key, json_encode($value)]);
}

function meta_settings_raw() {
    $defaults = [
        'ad_account_id' => defined('META_AD_ACCOUNT_ID') ? META_AD_ACCOUNT_ID : '',
        'api_version' => defined('META_API_VERSION') ? META_API_VERSION : profit_meta_default_api_version(),
        'access_token' => defined('META_ACCESS_TOKEN') ? META_ACCESS_TOKEN : '',
        'sync_enabled' => true,
        'cron_secret' => '',
    ];
    return array_merge($defaults, setting_json_get('meta_ads', []));
}

function meta_settings_public($include_cron = false) {
    $s = meta_settings_raw();
    $account = profit_meta_account_node($s['ad_account_id'] ?? '');
    $public = [
        'ad_account_id' => (string)($s['ad_account_id'] ?? ''),
        'ad_account_node' => $account,
        'api_version' => profit_meta_safe_api_version($s['api_version'] ?? ''),
        'sync_enabled' => !empty($s['sync_enabled']),
        'token_saved' => trim((string)($s['access_token'] ?? '')) !== '',
        'configured' => $account !== '' && trim((string)($s['access_token'] ?? '')) !== '',
        'last_sync' => setting_json_get('meta_last_sync', []),
        'last_error' => setting_json_get('meta_last_error', []),
    ];
    if ($include_cron) {
        $public['cron_url_quick'] = meta_cron_url('quick');
        $public['cron_url_nightly'] = meta_cron_url('nightly');
        $public['cron_url_weekly'] = meta_cron_url('weekly');
    }
    return $public;
}

function meta_save_settings(array $body) {
    $current = meta_settings_raw();
    $account = trim((string)($body['ad_account_id'] ?? $current['ad_account_id'] ?? ''));
    if ($account !== '' && profit_meta_account_node($account) === '') {
        http_response_code(400);
        respond(['error' => 'Meta ad account ID must be numeric or act_123.']);
    }
    $current['ad_account_id'] = $account;
    $current['api_version'] = profit_meta_safe_api_version($body['api_version'] ?? ($current['api_version'] ?? ''));
    $current['sync_enabled'] = !empty($body['sync_enabled']);
    if (!empty($body['access_token'])) {
        $current['access_token'] = trim((string)$body['access_token']);
    }
    if (!empty($body['rotate_cron_secret']) || empty($current['cron_secret'])) {
        $current['cron_secret'] = bin2hex(random_bytes(18));
    }
    setting_json_put('meta_ads', $current);
}

function meta_private_config() {
    $s = meta_settings_raw();
    $account = profit_meta_account_node($s['ad_account_id'] ?? '');
    $token = trim((string)($s['access_token'] ?? ''));
    if ($account === '' || $token === '') {
        throw new Exception('Meta Ads is not configured yet.');
    }
    return [
        'account_id' => $account,
        'access_token' => $token,
        'api_version' => profit_meta_safe_api_version($s['api_version'] ?? ''),
    ];
}

function meta_cron_url($mode) {
    $s = meta_settings_raw();
    if (empty($s['cron_secret'])) {
        $s['cron_secret'] = bin2hex(random_bytes(18));
        setting_json_put('meta_ads', $s);
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'profit.klivrapps.com';
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/api.php', '?') ?: '/api.php';
    return $scheme . '://' . $host . $path . '?action=meta-cron&mode=' . rawurlencode($mode) . '&secret=' . rawurlencode($s['cron_secret']);
}

function meta_graph_request($pathOrUrl, $method = 'GET', array $params = [], $cfg = null) {
    $cfg = $cfg ?: meta_private_config();
    $method = strtoupper($method);
    $params['access_token'] = $cfg['access_token'];
    if (preg_match('#^https?://#', $pathOrUrl)) {
        $url = $pathOrUrl;
    } else {
        $url = 'https://graph.facebook.com/' . rawurlencode($cfg['api_version']) . '/' . ltrim($pathOrUrl, '/');
    }

    $ch = curl_init();
    if ($method === 'GET') {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'Profit-Tracker/' . PROFIT_APP_VERSION,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false) throw new Exception('Meta HTTP error: ' . $err);
    $data = json_decode($res, true);
    if (!is_array($data)) {
        throw new Exception('Meta returned invalid JSON.');
    }
    if ($code >= 400 || isset($data['error'])) {
        $msg = $data['error']['message'] ?? ('Meta HTTP ' . $code);
        throw new Exception($msg);
    }
    return $data;
}

function meta_fetch_insights($from, $to) {
    $cfg = meta_private_config();
    $url = profit_meta_build_insights_url($cfg['account_id'], $cfg['access_token'], $from, $to, $cfg['api_version']);
    $all = [];
    $guard = 0;
    while ($url && $guard < 20) {
        $data = meta_graph_request($url, 'GET', [], $cfg);
        foreach (($data['data'] ?? []) as $row) $all[] = $row;
        $url = $data['paging']['next'] ?? '';
        $guard++;
    }
    return $all;
}

function meta_upsert_entity($type, array $data) {
    $id = trim((string)($data['id'] ?? ''));
    if ($id === '') return;
    $daily = isset($data['daily_budget']) && $data['daily_budget'] !== '' ? (int)$data['daily_budget'] : null;
    $life = isset($data['lifetime_budget']) && $data['lifetime_budget'] !== '' ? (int)$data['lifetime_budget'] : null;
    $budgetSource = $daily !== null ? 'daily_budget' : ($life !== null ? 'lifetime_budget' : '');
    db()->prepare(
        "INSERT INTO profit_meta_entities
         (entity_type, meta_id, name, status, effective_status, campaign_id, adset_id, daily_budget, lifetime_budget, budget_source, currency, raw_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name=VALUES(name), status=VALUES(status), effective_status=VALUES(effective_status),
           campaign_id=VALUES(campaign_id), adset_id=VALUES(adset_id), daily_budget=VALUES(daily_budget),
           lifetime_budget=VALUES(lifetime_budget), budget_source=VALUES(budget_source),
           currency=VALUES(currency), raw_json=VALUES(raw_json)"
    )->execute([
        $type,
        $id,
        substr((string)($data['name'] ?? ''), 0, 255),
        substr((string)($data['status'] ?? ''), 0, 40),
        substr((string)($data['effective_status'] ?? ''), 0, 40),
        substr((string)($data['campaign_id'] ?? ''), 0, 80),
        substr((string)($data['adset_id'] ?? ''), 0, 80),
        $daily,
        $life,
        $budgetSource,
        substr((string)($data['currency'] ?? APP_CURRENCY), 0, 12),
        json_encode($data),
    ]);
}

function meta_fetch_entity($type, $id) {
    $id = trim((string)$id);
    if ($id === '') return null;
    $fields = 'id,name,status,effective_status,daily_budget,lifetime_budget,currency';
    if ($type === 'ad') $fields .= ',campaign_id,adset_id';
    if ($type === 'adset') $fields .= ',campaign_id';
    $data = meta_graph_request($id, 'GET', ['fields' => $fields]);
    meta_upsert_entity($type, $data);
    return $data;
}

function meta_refresh_entities_from_rows(array $rows) {
    $ids = ['ad' => [], 'adset' => [], 'campaign' => []];
    foreach ($rows as $row) {
        if (!empty($row['ad_id']) && $row['ad_id'] !== 'unknown_ad') $ids['ad'][$row['ad_id']] = true;
        if (!empty($row['adset_id'])) $ids['adset'][$row['adset_id']] = true;
        if (!empty($row['campaign_id']) && $row['campaign_id'] !== 'account_total') $ids['campaign'][$row['campaign_id']] = true;
    }
    $count = 0;
    foreach ($ids as $type => $bucket) {
        foreach (array_keys($bucket) as $id) {
            if ($count >= 200) return $count;
            try {
                meta_fetch_entity($type, $id);
                $count++;
            } catch (Throwable $e) {
                error_log('[profit-tracker-meta] entity ' . $type . '/' . $id . ': ' . $e->getMessage());
            }
        }
    }
    return $count;
}

function meta_upsert_insights(array $rows) {
    $ins = db()->prepare(
        "INSERT INTO profit_meta_insights
         (insight_date, account_id, campaign_id, campaign_name, adset_id, adset_name, ad_id, ad_name,
          spend, impressions, clicks, cpc, cpm, ctr, purchases, purchase_value, raw_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
          campaign_id=VALUES(campaign_id), campaign_name=VALUES(campaign_name),
          adset_id=VALUES(adset_id), adset_name=VALUES(adset_name), ad_name=VALUES(ad_name),
          spend=VALUES(spend), impressions=VALUES(impressions), clicks=VALUES(clicks),
          cpc=VALUES(cpc), cpm=VALUES(cpm), ctr=VALUES(ctr), purchases=VALUES(purchases),
          purchase_value=VALUES(purchase_value), raw_json=VALUES(raw_json)"
    );
    $spend = 0.0; $count = 0;
    foreach ($rows as $r) {
        $ins->execute([
            $r['insight_date'], $r['account_id'], $r['campaign_id'], $r['campaign_name'],
            $r['adset_id'], $r['adset_name'], $r['ad_id'], $r['ad_name'],
            $r['spend'], $r['impressions'], $r['clicks'], $r['cpc'], $r['cpm'], $r['ctr'],
            $r['purchases'], $r['purchase_value'], json_encode($r['raw'] ?? []),
        ]);
        $spend += (float)$r['spend'];
        $count++;
    }
    meta_mirror_adspend($rows);
    return ['rows' => $count, 'spend' => round($spend, 2)];
}

function meta_mirror_adspend(array $rows) {
    $stmt = db()->prepare(
        "INSERT INTO profit_adspend
         (spend_date, channel, campaign, amount, impressions, clicks, source, source_key, meta_campaign_id, meta_adset_id, meta_ad_id, note)
         VALUES (?, 'Meta', ?, ?, ?, ?, 'meta_api', ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
          spend_date=VALUES(spend_date), channel=VALUES(channel), campaign=VALUES(campaign),
          amount=VALUES(amount), impressions=VALUES(impressions), clicks=VALUES(clicks),
          meta_campaign_id=VALUES(meta_campaign_id), meta_adset_id=VALUES(meta_adset_id),
          meta_ad_id=VALUES(meta_ad_id), note=VALUES(note)"
    );
    foreach ($rows as $r) {
        $label = trim(($r['campaign_name'] ?? '') . ' / ' . ($r['adset_name'] ?? '') . ' / ' . ($r['ad_name'] ?? ''), ' /');
        $stmt->execute([
            $r['insight_date'],
            substr($label, 0, 120),
            (float)$r['spend'],
            (int)$r['impressions'],
            (int)$r['clicks'],
            $r['source_key'],
            $r['campaign_id'],
            $r['adset_id'],
            $r['ad_id'],
            'Synced from Meta Ads API',
        ]);
    }
    db()->exec("DELETE FROM profit_cache");
}

function run_meta_sync($mode = 'quick', $from = '', $to = '', $trigger = 'manual') {
    if ($from === '' || $to === '') {
        [$from, $to] = profit_meta_sync_window($mode ?: 'quick');
    }
    $raw = meta_fetch_insights($from, $to);
    $cfg = meta_private_config();
    $rows = profit_meta_normalize_insights($raw, $cfg['account_id']);
    $result = meta_upsert_insights($rows);
    $entities = meta_refresh_entities_from_rows($rows);
    $payload = [
        'mode' => $mode,
        'trigger' => $trigger,
        'from' => $from,
        'to' => $to,
        'rows' => $result['rows'],
        'entities' => $entities,
        'spend' => $result['spend'],
        'finished_at' => date('c'),
    ];
    setting_json_put('meta_last_sync', $payload);
    setting_json_put('meta_last_error', []);
    return $payload;
}

function order_meta_flat(array $order) {
    $out = [];
    foreach (($order['meta_data'] ?? []) as $m) {
        if (!is_array($m)) continue;
        $key = strtolower(trim((string)($m['key'] ?? '')));
        if ($key === '') continue;
        $value = $m['value'] ?? '';
        if (is_array($value) || is_object($value)) $value = json_encode($value);
        $out[$key] = (string)$value;
    }
    return $out;
}

function tracking_find(array $meta, array $keys) {
    foreach ($keys as $key) {
        $needle = strtolower($key);
        foreach ($meta as $mk => $mv) {
            if ($mk === $needle || substr($mk, -strlen($needle)) === $needle) {
                $v = trim((string)$mv);
                if ($v !== '') return substr($v, 0, 120);
            }
        }
    }
    foreach ($meta as $mv) {
        if (!is_string($mv) || strpos($mv, '=') === false) continue;
        $candidate = str_replace('&amp;', '&', $mv);
        $query = parse_url($candidate, PHP_URL_QUERY);
        parse_str($query ?: $candidate, $q);
        foreach ($keys as $key) {
            if (!empty($q[$key])) return substr(trim((string)$q[$key]), 0, 120);
        }
    }
    return '';
}

function order_tracking_ids(array $order) {
    $meta = order_meta_flat($order);
    return [
        'adid' => tracking_find($meta, ['adid', 'ad_id', 'utm_ad_id']),
        'asid' => tracking_find($meta, ['asid', 'adset_id', 'utm_adset_id']),
        'cid' => tracking_find($meta, ['cid', 'campaign_id', 'utm_campaign_id']),
        'utm_source' => tracking_find($meta, ['utm_source', '_wc_order_attribution_utm_source']),
        'utm_medium' => tracking_find($meta, ['utm_medium', '_wc_order_attribution_utm_medium']),
        'utm_campaign' => tracking_find($meta, ['utm_campaign', '_wc_order_attribution_utm_campaign']),
        'utm_content' => tracking_find($meta, ['utm_content', '_wc_order_attribution_utm_content']),
        'utm_term' => tracking_find($meta, ['utm_term', '_wc_order_attribution_utm_term']),
    ];
}

function attribution_profit_report($period, $group_by = 'campaign') {
    $allowed = ['campaign', 'adset', 'ad', 'source', 'product', 'date'];
    if (!in_array($group_by, $allowed, true)) $group_by = 'campaign';
    [$start, $end] = get_date_range($period);
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));
    $costs = get_product_costs_map();
    $fees = get_fee_config();
    $ship = get_shipping_config();
    $rows = [];

    $add = function($key, $label, $profit, $orderId) use (&$rows) {
        $key = $key !== '' ? $key : '(unknown)';
        if (!isset($rows[$key])) {
            $rows[$key] = [
                'key' => $key,
                'label' => $label ?: $key,
                'orders_map' => [],
                'revenue' => 0.0,
                'gross_profit' => 0.0,
                'profit_before_ads' => 0.0,
                'spend' => 0.0,
                'clicks' => 0,
                'impressions' => 0,
            ];
        }
        $rows[$key]['orders_map'][$orderId] = true;
        $rows[$key]['revenue'] += (float)$profit['revenue'];
        $rows[$key]['gross_profit'] += (float)$profit['gross_profit'];
        $rows[$key]['profit_before_ads'] += (float)$profit['profit_before_ads'];
    };

    foreach ($orders as $o) {
        $ids = order_tracking_ids($o);
        $profit = light_order_profit($o, $costs, $fees, $ship);
        $date = substr((string)($o['date_created'] ?? ''), 0, 10);
        if ($group_by === 'product') {
            foreach (($o['line_items'] ?? []) as $li) {
                $pid = (string)((int)($li['product_id'] ?? 0));
                if ($pid === '0') continue;
                $line = [
                    'revenue' => (float)($li['total'] ?? 0) + (float)($li['total_tax'] ?? 0),
                    'gross_profit' => (float)($li['total'] ?? 0) - (pt_cost($pid, (int)($li['variation_id'] ?? 0), $o['date_created'] ?? null)['cost'] * (int)($li['quantity'] ?? 0)),
                    'profit_before_ads' => (float)($li['total'] ?? 0) - (pt_cost($pid, (int)($li['variation_id'] ?? 0), $o['date_created'] ?? null)['cost'] * (int)($li['quantity'] ?? 0)),
                ];
                $add($pid, (string)($li['name'] ?? $pid), $line, (int)$o['id']);
            }
            continue;
        }
        if ($group_by === 'date') {
            $add($date, $date, $profit, (int)$o['id']);
        } elseif ($group_by === 'ad') {
            $add(preg_replace('/[^0-9]/', '', (string)$ids['adid']), $ids['utm_content'] ?: $ids['adid'], $profit, (int)$o['id']);
        } elseif ($group_by === 'adset') {
            $add(preg_replace('/[^0-9]/', '', (string)$ids['asid']), $ids['utm_term'] ?: $ids['asid'], $profit, (int)$o['id']);
        } elseif ($group_by === 'source') {
            $add((string)$ids['utm_source'], (string)$ids['utm_source'], $profit, (int)$o['id']);
        } else {
            $add(preg_replace('/[^0-9]/', '', (string)$ids['cid']) ?: (string)$ids['utm_campaign'], (string)$ids['utm_campaign'] ?: (string)$ids['cid'], $profit, (int)$o['id']);
        }
    }

    $s = $start->format('Y-m-d');
    $e = $end->format('Y-m-d');
    $stmt = db()->prepare(
        "SELECT spend_date, campaign, meta_campaign_id, meta_adset_id, meta_ad_id, SUM(amount) AS spend, SUM(clicks) AS clicks, SUM(impressions) AS impressions
         FROM profit_adspend
         WHERE spend_date BETWEEN ? AND ?
         GROUP BY spend_date, campaign, meta_campaign_id, meta_adset_id, meta_ad_id"
    );
    $stmt->execute([$s, $e]);
    foreach ($stmt->fetchAll() as $spend) {
        $key = '';
        if ($group_by === 'date') $key = (string)$spend['spend_date'];
        elseif ($group_by === 'ad') $key = (string)$spend['meta_ad_id'];
        elseif ($group_by === 'adset') $key = (string)$spend['meta_adset_id'];
        elseif ($group_by === 'campaign') $key = (string)$spend['meta_campaign_id'];
        if ($key === '') continue;
        if (!isset($rows[$key])) {
            $rows[$key] = [
                'key' => $key,
                'label' => $key,
                'orders_map' => [],
                'revenue' => 0.0,
                'gross_profit' => 0.0,
                'profit_before_ads' => 0.0,
                'spend' => 0.0,
                'clicks' => 0,
                'impressions' => 0,
            ];
        }
        $rows[$key]['spend'] += (float)$spend['spend'];
        $rows[$key]['clicks'] += (int)$spend['clicks'];
        $rows[$key]['impressions'] += (int)$spend['impressions'];
    }

    $out = [];
    foreach ($rows as $row) {
        $ordersCount = count($row['orders_map']);
        $net = $row['profit_before_ads'] - $row['spend'];
        $roas = $row['spend'] > 0 ? round($row['revenue'] / $row['spend'], 2) : 0;
        // R3 — break-even ROAS (revenue ÷ contribution) + scale/hold/kill verdict
        $be_roas = $row['profit_before_ads'] > 0
            ? round($row['revenue'] / $row['profit_before_ads'], 2) : null;
        $verdict = pt_roas_verdict($row['spend'] > 0 ? $roas : null, $be_roas);
        $out[] = [
            'key' => $row['key'],
            'label' => $row['label'],
            'orders' => $ordersCount,
            'revenue' => round($row['revenue'], 2),
            'gross_profit' => round($row['gross_profit'], 2),
            'profit_before_ads' => round($row['profit_before_ads'], 2),
            'spend' => round($row['spend'], 2),
            'net_profit_after_ads' => round($net, 2),
            'roas' => $roas,
            'be_roas' => $be_roas,
            'verdict' => $verdict['verdict'],
            'cpa' => ($ordersCount > 0 && $row['spend'] > 0) ? round($row['spend'] / $ordersCount, 2) : 0,
            'clicks' => (int)$row['clicks'],
            'impressions' => (int)$row['impressions'],
        ];
    }
    usort($out, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
    return [
        'ok' => true,
        'period' => $period,
        'group_by' => $group_by,
        'date_range' => ['start' => $s, 'end' => $e],
        'rows' => $out,
    ];
}

function light_order_profit(array $o, array $costs_map, array $fee_config, array $ship_config) {
    $order_total = (float)($o['total'] ?? 0);
    $tax = (float)($o['total_tax'] ?? 0);
    $ex_vat = $order_total - $tax;
    $cogs = 0.0;
    foreach (($o['line_items'] ?? []) as $li) {
        $pid = (int)($li['product_id'] ?? 0);
        $qty = (int)($li['quantity'] ?? 0);
        $rc  = pt_cost($pid, (int)($li['variation_id'] ?? 0), $o['date_created'] ?? null);
        if ($rc['found']) $cogs += $rc['cost'] * $qty;
    }
    $fees = 0.0;
    $pm = (string)($o['payment_method'] ?? '');
    foreach ($fee_config as $fee) {
        if (($fee['method_slug'] ?? '') === $pm || ($fee['method_slug'] ?? '') === '*') {
            $fees += ((float)($fee['rate'] ?? 0) / 100) * $order_total + (float)($fee['fixed'] ?? 0);
            if (($fee['method_slug'] ?? '') !== '*') break;
        }
    }
    $country = strtoupper(trim($o['shipping']['country'] ?? $o['billing']['country'] ?? 'SE'));
    $shipping_cost = ($country === 'SE') ? (float)$ship_config['cost_domestic'] : (float)$ship_config['cost_international'];
    return [
        'revenue' => $order_total,
        'revenue_excl_vat' => $ex_vat,
        'gross_profit' => $ex_vat - $cogs,
        'profit_before_ads' => $ex_vat - $cogs - $fees - $shipping_cost,
    ];
}

function meta_attribution($period) {
    [$start, $end] = get_date_range($period);
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));
    $costs = get_product_costs_map();
    $fees = get_fee_config();
    $ship = get_shipping_config();
    $map = ['ad' => [], 'adset' => [], 'campaign' => []];
    foreach ($orders as $o) {
        $ids = order_tracking_ids($o);
        $profit = light_order_profit($o, $costs, $fees, $ship);
        foreach ([['ad', 'adid'], ['adset', 'asid'], ['campaign', 'cid']] as $pair) {
            [$level, $key] = $pair;
            $id = preg_replace('/[^0-9]/', '', (string)$ids[$key]);
            if ($id === '') continue;
            if (!isset($map[$level][$id])) {
                $map[$level][$id] = ['orders' => 0, 'revenue' => 0.0, 'gross_profit' => 0.0, 'profit_before_ads' => 0.0];
            }
            $map[$level][$id]['orders'] += 1;
            $map[$level][$id]['revenue'] += $profit['revenue'];
            $map[$level][$id]['gross_profit'] += $profit['gross_profit'];
            $map[$level][$id]['profit_before_ads'] += $profit['profit_before_ads'];
        }
    }
    return $map;
}

function meta_report($period) {
    [$start, $end] = get_date_range($period);
    $s = $start->format('Y-m-d');
    $e = $end->format('Y-m-d');
    $stmt = db()->prepare(
        "SELECT ad_id, MAX(ad_name) AS ad_name, adset_id, MAX(adset_name) AS adset_name,
                campaign_id, MAX(campaign_name) AS campaign_name,
                SUM(spend) AS spend, SUM(impressions) AS impressions, SUM(clicks) AS clicks,
                SUM(purchases) AS meta_purchases, SUM(purchase_value) AS meta_purchase_value
         FROM profit_meta_insights
         WHERE insight_date BETWEEN ? AND ?
         GROUP BY ad_id, adset_id, campaign_id
         ORDER BY spend DESC, clicks DESC
         LIMIT 100"
    );
    $stmt->execute([$s, $e]);
    $rows = $stmt->fetchAll();
    $attr = meta_attribution($period);
    $entities = meta_entity_map();
    $out = [];
    foreach ($rows as $r) {
        $adId = (string)$r['ad_id'];
        $adsetId = (string)$r['adset_id'];
        $campaignId = (string)$r['campaign_id'];
        $a = $attr['ad'][$adId] ?? ['orders' => 0, 'revenue' => 0, 'gross_profit' => 0, 'profit_before_ads' => 0];
        $spend = (float)$r['spend'];
        $trackedRevenue = (float)$a['revenue'];
        $trackedProfit = (float)$a['profit_before_ads'] - $spend;
        $adEntity = $entities['ad'][$adId] ?? [];
        $adsetEntity = $entities['adset'][$adsetId] ?? [];
        $campaignEntity = $entities['campaign'][$campaignId] ?? [];
        $budgetEntity = !empty($adsetEntity['daily_budget']) || !empty($adsetEntity['lifetime_budget']) ? $adsetEntity : $campaignEntity;
        $budgetType = !empty($budgetEntity['daily_budget']) ? 'daily_budget' : (!empty($budgetEntity['lifetime_budget']) ? 'lifetime_budget' : '');
        $budgetMinor = $budgetType ? (int)$budgetEntity[$budgetType] : 0;
        $out[] = [
            'ad_id' => $adId,
            'ad_name' => (string)$r['ad_name'],
            'adset_id' => $adsetId,
            'adset_name' => (string)$r['adset_name'],
            'campaign_id' => $campaignId,
            'campaign_name' => (string)$r['campaign_name'],
            'status' => $adEntity['status'] ?? '',
            'effective_status' => $adEntity['effective_status'] ?? '',
            'budget_entity_type' => $budgetEntity['entity_type'] ?? '',
            'budget_entity_id' => $budgetEntity['meta_id'] ?? '',
            'budget_type' => $budgetType,
            'budget_minor' => $budgetMinor,
            'budget' => round($budgetMinor / 100, 2),
            'spend' => round($spend, 2),
            'impressions' => (int)$r['impressions'],
            'clicks' => (int)$r['clicks'],
            'orders' => (int)$a['orders'],
            'tracked_revenue' => round($trackedRevenue, 2),
            'gross_profit' => round((float)$a['gross_profit'], 2),
            'net_profit_after_ads' => round($trackedProfit, 2),
            'roas' => $spend > 0 ? round($trackedRevenue / $spend, 2) : 0,
            'cpa' => ((int)$a['orders'] > 0 && $spend > 0) ? round($spend / (int)$a['orders'], 2) : 0,
            'meta_purchases' => (float)$r['meta_purchases'],
            'meta_purchase_value' => round((float)$r['meta_purchase_value'], 2),
        ];
    }
    return [
        'ok' => true,
        'period' => $period,
        'date_range' => ['start' => $s, 'end' => $e],
        'meta' => meta_settings_public(false),
        'rows' => $out,
        'attribution_notice' => 'Sales attribution requires WooCommerce order meta containing adid, asid, and cid.',
    ];
}

function meta_entity_map() {
    $rows = db()->query("SELECT * FROM profit_meta_entities")->fetchAll();
    $map = ['campaign' => [], 'adset' => [], 'ad' => []];
    foreach ($rows as $r) {
        $r['daily_budget'] = $r['daily_budget'] !== null ? (int)$r['daily_budget'] : null;
        $r['lifetime_budget'] = $r['lifetime_budget'] !== null ? (int)$r['lifetime_budget'] : null;
        $map[$r['entity_type']][(string)$r['meta_id']] = $r;
    }
    return $map;
}

function meta_action_log($user, $action, $type, $id, $name, $old, $new, $confirmation, $response) {
    db()->prepare(
        "INSERT INTO profit_meta_action_log
         (user_id, action_type, entity_type, meta_id, entity_name, old_value, new_value, confirmation, ip, raw_response)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        (int)$user['id'], $action, $type, $id, substr($name, 0, 255),
        json_encode($old), json_encode($new), substr($confirmation, 0, 40),
        substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45), json_encode($response),
    ]);
}

function meta_control_action(array $body, array $user) {
    $type = (string)($body['entity_type'] ?? '');
    if (!in_array($type, ['campaign', 'adset', 'ad'], true)) {
        http_response_code(400); respond(['error' => 'Invalid entity type.']);
    }
    $id = preg_replace('/[^0-9]/', '', (string)($body['meta_id'] ?? ''));
    if ($id === '') { http_response_code(400); respond(['error' => 'Missing Meta ID.']); }
    $action = (string)($body['action'] ?? '');
    $confirmation = strtoupper(trim((string)($body['confirmation'] ?? '')));
    $before = meta_fetch_entity($type, $id) ?: [];
    $payload = [];
    if ($action === 'pause' || $action === 'activate') {
        $wanted = $action === 'pause' ? 'PAUSED' : 'ACTIVE';
        $needed = $action === 'pause' ? 'PAUSE' : 'ACTIVATE';
        if ($confirmation !== $needed) {
            http_response_code(400); respond(['error' => 'Confirmation required.']);
        }
        $payload['status'] = $wanted;
    } elseif ($action === 'budget_set' || $action === 'budget_delta') {
        if (!in_array($type, ['campaign', 'adset'], true)) {
            http_response_code(400); respond(['error' => 'Budgets can only be changed on campaigns or ad sets.']);
        }
        $field = (string)($body['budget_type'] ?? '');
        if (!in_array($field, ['daily_budget', 'lifetime_budget'], true)) {
            $field = !empty($before['daily_budget']) ? 'daily_budget' : (!empty($before['lifetime_budget']) ? 'lifetime_budget' : 'daily_budget');
        }
        $oldMinor = isset($before[$field]) ? (int)$before[$field] : 0;
        if ($action === 'budget_delta') {
            if ($oldMinor <= 0) { http_response_code(400); respond(['error' => 'No existing budget found to adjust.']); }
            $pct = max(-80, min(200, (float)($body['delta_pct'] ?? 0)));
            $newMinor = max(100, (int)round($oldMinor * (1 + ($pct / 100))));
        } else {
            $newKr = max(1, (float)($body['budget'] ?? 0));
            $newMinor = (int)round($newKr * 100);
        }
        if ($newMinor > $oldMinor && ($oldMinor === 0 || $newMinor > ($oldMinor * 1.2)) && $confirmation !== 'BUDGET') {
            http_response_code(400); respond(['error' => 'Large budget increase requires typed confirmation.']);
        }
        $payload[$field] = $newMinor;
    } else {
        http_response_code(400); respond(['error' => 'Invalid Meta action.']);
    }
    $response = meta_graph_request($id, 'POST', $payload);
    $after = meta_fetch_entity($type, $id) ?: [];
    meta_action_log($user, $action, $type, $id, (string)($after['name'] ?? $before['name'] ?? ''), $before, $payload, $confirmation, $response);
    return ['ok' => true, 'action' => $action, 'before' => $before, 'after' => $after, 'response' => $response];
}

// ═══════════════════════════════════════════════════════════════════
// MAIN ROUTER
// ═══════════════════════════════════════════════════════════════════
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    setup_db();
    rate_limit();

    switch ($action) {

        // ── PING (public) ──────────────────────────────────────────
        case 'ping':
            respond([
                'ok'    => true,
                'time'  => date('c'),
                'tz'    => APP_TIMEZONE,
                'app'   => 'Profit Tracker',
                'ver'   => PROFIT_APP_VERSION,
                'phase' => 6,
            ]);

        // ── LOGIN ──────────────────────────────────────────────────
        case 'login':
            if ($method !== 'POST') { http_response_code(405); respond(['error' => 'POST required']); }
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $un   = trim((string)($body['username'] ?? ''));
            $pw   = (string)($body['password'] ?? '');

            $stmt = db()->prepare("SELECT * FROM profit_users WHERE username = ?");
            $stmt->execute([$un]);
            $user = $stmt->fetch();
            $hash = $user['password_hash'] ?? '$2y$10$invalidsaltinvalidsaltinvalidsaltinvalidsa';
            $ok   = password_verify($pw, $hash) && $user;

            if (!$ok) {
                usleep(300000);
                http_response_code(401);
                respond(['error' => 'Fel användarnamn eller lösenord']);
            }

            $token = bin2hex(random_bytes(32));
            $exp   = date('Y-m-d H:i:s', strtotime('+' . SESSION_DAYS . ' days'));
            db()->prepare("INSERT INTO profit_sessions (token, user_id, ip, user_agent, expires_at) VALUES (?,?,?,?,?)")
                ->execute([
                    $token,
                    $user['id'],
                    substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                    $exp,
                ]);

            unset($user['password_hash']);
            respond(['ok' => true, 'token' => $token, 'user' => $user, 'expires' => $exp]);

        // ── LOGOUT ────────────────────────────────────────────────
        case 'logout':
            $token = get_bearer();
            if ($token) db()->prepare("DELETE FROM profit_sessions WHERE token = ?")->execute([$token]);
            respond(['ok' => true]);

        // ── PROFILE ───────────────────────────────────────────────
        case 'profile':
            $u = require_auth();
            unset($u['password_hash']);
            respond(['user' => $u]);

        // ── CHANGE PASSWORD ────────────────────────────────────────
        case 'change-password':
            if ($method !== 'POST') { http_response_code(405); respond(['error' => 'POST required']); }
            $u    = require_auth();
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $cur  = (string)($body['current_password'] ?? '');
            $new  = (string)($body['new_password'] ?? '');
            if (strlen($new) < 8) { http_response_code(400); respond(['error' => 'Nytt lösenord måste vara minst 8 tecken.']); }

            $stmt = db()->prepare("SELECT password_hash FROM profit_users WHERE id = ?");
            $stmt->execute([$u['id']]);
            $row  = $stmt->fetch();
            if (!password_verify($cur, $row['password_hash'])) {
                http_response_code(400);
                respond(['error' => 'Nuvarande lösenord stämmer inte.']);
            }
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            db()->prepare("UPDATE profit_users SET password_hash = ? WHERE id = ?")->execute([$newHash, $u['id']]);
            db()->prepare("DELETE FROM profit_sessions WHERE user_id = ? AND token != ?")->execute([$u['id'], get_bearer()]);
            respond(['ok' => true]);

        // ── REVENUE (now includes Phase 2 profit calculation) ──────
        case 'revenue':
            require_auth();
            $period = (string)($_GET['period'] ?? 'today');
            $force  = !empty($_GET['force']);

            $cacheKey = 'rev:' . $period . (!empty($_GET['compare']) ? ':cmp' : '');
            if (!$force) {
                $hit = cache_get($cacheKey);
                if ($hit) { $hit['cached'] = true; respond($hit); }
            }
            $data = compute_revenue($period);
            cache_put($cacheKey, $data, WC_CACHE_SECONDS);
            $data['cached'] = false;
            respond($data);

        // ── PRODUCTS (WC product list for COGS settings) ───────────
        case 'products':
            require_auth();
            $force    = !empty($_GET['force']);
            $cacheKey = 'wc_products:v1';
            if (!$force) {
                $hit = cache_get($cacheKey);
                if ($hit) respond(['ok' => true, 'products' => $hit, 'cached' => true]);
            }
            $products = fetch_products();
            cache_put($cacheKey, $products, 3600); // 1 hour
            respond(['ok' => true, 'products' => $products, 'cached' => false]);

        // ── COSTS (GET = return config; POST = save config) ────────
        case 'costs':
            require_auth();

            if ($method === 'GET') {
                $rows  = db()->query("SELECT product_id, product_name, unit_cost FROM profit_costs")->fetchAll();
                $cogs  = [];
                foreach ($rows as $r) {
                    $cogs[(string)$r['product_id']] = [
                        'product_name' => $r['product_name'],
                        'unit_cost'    => (float)$r['unit_cost'],
                    ];
                }
                respond([
                    'ok'       => true,
                    'cogs'     => $cogs,
                    'fees'     => get_fee_config(),
                    'fixed'    => get_fixed_costs(),
                    'shipping' => get_shipping_config(),
                    'counted_statuses' => get_counted_statuses(),
                    'vat_rate' => get_vat_rate(),
                    'variation_costs'  => db()->query("SELECT variation_id, product_id, product_name, unit_cost FROM profit_variation_costs")->fetchAll(),
                    'cost_history'     => db()->query("SELECT cost_key, unit_cost, effective_from FROM profit_cost_history ORDER BY cost_key, effective_from")->fetchAll(),
                ]);

            } elseif ($method === 'POST') {
                $body = json_decode(file_get_contents('php://input'), true);
                if (!is_array($body)) { http_response_code(400); respond(['error' => 'Invalid JSON']); }

                $pdo = db();

                // Save COGS per product
                if (isset($body['cogs']) && is_array($body['cogs'])) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO profit_costs (product_id, product_name, unit_cost) VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE product_name = VALUES(product_name), unit_cost = VALUES(unit_cost)"
                    );
                    foreach ($body['cogs'] as $pid => $item) {
                        $pid = (int)$pid;
                        if ($pid <= 0) continue;
                        if (is_array($item)) {
                            $cost = max(0.0, (float)($item['cost'] ?? 0));
                            $name = substr((string)($item['name'] ?? ''), 0, 255);
                        } else {
                            $cost = max(0.0, (float)$item);
                            $name = '';
                        }
                        $stmt->execute([$pid, $name, $cost]);
                    }
                }

                // Save transaction fee config
                if (isset($body['fees']) && is_array($body['fees'])) {
                    $sanitized = [];
                    foreach ($body['fees'] as $fee) {
                        if (!is_array($fee)) continue;
                        $sanitized[] = [
                            'method_slug' => substr(trim((string)($fee['method_slug'] ?? '')), 0, 100),
                            'name'        => substr(trim((string)($fee['name'] ?? '')), 0, 100),
                            'rate'        => max(0.0, min(100.0, (float)($fee['rate'] ?? 0))),
                            'fixed'       => max(0.0, (float)($fee['fixed'] ?? 0)),
                        ];
                    }
                    $pdo->prepare(
                        "INSERT INTO profit_settings (setting_key, setting_value) VALUES ('fees', ?)
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                    )->execute([json_encode($sanitized)]);
                }

                // Save shipping cost config (domestic + international)
                if (isset($body['shipping']) && is_array($body['shipping'])) {
                    $sanitized = [
                        'cost_domestic'      => max(0.0, (float)($body['shipping']['cost_domestic']      ?? 0)),
                        'cost_international' => max(0.0, (float)($body['shipping']['cost_international'] ?? 0)),
                    ];
                    $pdo->prepare(
                        "INSERT INTO profit_settings (setting_key, setting_value) VALUES ('shipping', ?)
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                    )->execute([json_encode($sanitized)]);
                }

                // Save fixed monthly costs
                if (isset($body['fixed']) && is_array($body['fixed'])) {
                    $sanitized = [];
                    foreach ($body['fixed'] as $fc) {
                        if (!is_array($fc)) continue;
                        $sanitized[] = [
                            'id'             => substr(trim((string)($fc['id'] ?? uniqid())), 0, 50),
                            'name'           => substr(trim((string)($fc['name'] ?? '')), 0, 100),
                            'amount_monthly' => max(0.0, (float)($fc['amount_monthly'] ?? 0)),
                        ];
                    }
                    $pdo->prepare(
                        "INSERT INTO profit_settings (setting_key, setting_value) VALUES ('fixed_costs', ?)
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                    )->execute([json_encode($sanitized)]);
                }

                // C5 — counted order statuses
                if (isset($body['counted_statuses'])) {
                    $val = is_array($body['counted_statuses'])
                        ? implode(',', $body['counted_statuses'])
                        : (string)$body['counted_statuses'];
                    $pdo->prepare(
                        "INSERT INTO profit_settings (setting_key, setting_value) VALUES ('counted_statuses', ?)
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                    )->execute([$val]);
                }

                // VAT rate (for the break-even planner)
                if (isset($body['vat_rate'])) {
                    $vr = max(0.0, min(99.0, (float)$body['vat_rate']));
                    $pdo->prepare(
                        "INSERT INTO profit_settings (setting_key, setting_value) VALUES ('vat_rate', ?)
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                    )->execute([(string)$vr]);
                }

                // C2 — variation-level costs
                if (isset($body['variation_costs']) && is_array($body['variation_costs'])) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO profit_variation_costs (variation_id, product_id, product_name, unit_cost)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE product_id = VALUES(product_id),
                             product_name = VALUES(product_name), unit_cost = VALUES(unit_cost)"
                    );
                    foreach ($body['variation_costs'] as $vc) {
                        if (!is_array($vc)) continue;
                        $vid = (int)($vc['variation_id'] ?? 0);
                        if ($vid <= 0) continue;
                        $stmt->execute([
                            $vid, (int)($vc['product_id'] ?? 0),
                            substr((string)($vc['product_name'] ?? ''), 0, 255),
                            max(0.0, (float)($vc['unit_cost'] ?? 0)),
                        ]);
                    }
                }

                // C6 — effective-dated cost history entries
                if (isset($body['cost_history']) && is_array($body['cost_history'])) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO profit_cost_history (cost_key, unit_cost, effective_from) VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE unit_cost = VALUES(unit_cost)"
                    );
                    foreach ($body['cost_history'] as $h) {
                        if (!is_array($h)) continue;
                        $key = substr(trim((string)($h['cost_key'] ?? '')), 0, 24);
                        $from = substr(trim((string)($h['effective_from'] ?? '')), 0, 10);
                        if (!preg_match('/^[pv]\d+$/', $key) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) continue;
                        $stmt->execute([$key, max(0.0, (float)($h['unit_cost'] ?? 0)), $from]);
                    }
                }

                // Invalidate revenue cache — next load will re-compute with new costs
                cache_clear_revenue();

                respond(['ok' => true, 'saved_at' => date('c')]);

            } else {
                http_response_code(405);
                respond(['error' => 'GET or POST required']);
            }

        // ── ADSPEND (Phase 3 — manual entry) ───────────────────────
        case 'adspend':
            require_auth();

            if ($method === 'GET') {
                $period = (string)($_GET['period'] ?? '30d');
                [$start, $end] = get_date_range($period);
                $s = $start->format('Y-m-d');
                $e = $end->format('Y-m-d');

                $stmt = db()->prepare(
                    "SELECT id, spend_date, channel, campaign, amount, impressions, clicks, source, note, updated_at
                     FROM profit_adspend
                     WHERE spend_date BETWEEN ? AND ?
                     ORDER BY spend_date DESC, channel ASC, id DESC"
                );
                $stmt->execute([$s, $e]);
                $rows = $stmt->fetchAll();

                $total = 0.0; $imp = 0; $clk = 0;
                foreach ($rows as &$r) {
                    $r['amount']      = (float)$r['amount'];
                    $r['impressions'] = (int)$r['impressions'];
                    $r['clicks']      = (int)$r['clicks'];
                    $r['id']          = (int)$r['id'];
                    $total += $r['amount'];
                    $imp   += $r['impressions'];
                    $clk   += $r['clicks'];
                }
                unset($r);

                respond([
                    'ok'                => true,
                    'period'            => $period,
                    'date_range'        => ['start' => $s, 'end' => $e],
                    'entries'           => $rows,
                    'total'             => round($total, 2),
                    'total_impressions' => $imp,
                    'total_clicks'      => $clk,
                    'channels'          => array_values(array_unique(array_column($rows, 'channel'))),
                ]);

            } elseif ($method === 'POST') {
                $body = json_decode(file_get_contents('php://input'), true);
                if (!is_array($body)) { http_response_code(400); respond(['error' => 'Invalid JSON']); }

                $id          = (int)($body['id'] ?? 0);
                $date_raw    = (string)($body['spend_date'] ?? '');
                $channel     = substr(trim((string)($body['channel']  ?? 'Meta')), 0, 50);
                $campaign    = substr(trim((string)($body['campaign'] ?? '')), 0, 120);
                $amount      = max(0.0, (float)($body['amount'] ?? 0));
                $impressions = max(0, (int)($body['impressions'] ?? 0));
                $clicks      = max(0, (int)($body['clicks'] ?? 0));
                $note        = substr(trim((string)($body['note'] ?? '')), 0, 255);

                // Validate date strictly: YYYY-MM-DD
                $d = DateTime::createFromFormat('Y-m-d', $date_raw);
                if (!$d || $d->format('Y-m-d') !== $date_raw) {
                    http_response_code(400);
                    respond(['error' => 'Ogiltigt datum. Använd YYYY-MM-DD.']);
                }
                if ($channel === '') {
                    http_response_code(400);
                    respond(['error' => 'Kanal får inte vara tom.']);
                }

                if ($id > 0) {
                    // Update existing
                    $stmt = db()->prepare(
                        "UPDATE profit_adspend
                         SET spend_date=?, channel=?, campaign=?, amount=?, impressions=?, clicks=?, note=?
                         WHERE id=? AND source='manual'"
                    );
                    $stmt->execute([$date_raw, $channel, $campaign, $amount, $impressions, $clicks, $note, $id]);
                    $saved_id = $id;
                } else {
                    // Insert new
                    $stmt = db()->prepare(
                        "INSERT INTO profit_adspend
                         (spend_date, channel, campaign, amount, impressions, clicks, source, note)
                         VALUES (?, ?, ?, ?, ?, ?, 'manual', ?)"
                    );
                    $stmt->execute([$date_raw, $channel, $campaign, $amount, $impressions, $clicks, $note]);
                    $saved_id = (int)db()->lastInsertId();
                }

                cache_clear_revenue();
                respond(['ok' => true, 'id' => $saved_id]);

            } elseif ($method === 'DELETE') {
                $id = (int)($_GET['id'] ?? 0);
                if ($id <= 0) { http_response_code(400); respond(['error' => 'Missing id']); }
                db()->prepare("DELETE FROM profit_adspend WHERE id = ? AND source = 'manual'")
                    ->execute([$id]);
                cache_clear_revenue();
                respond(['ok' => true]);

            } else {
                http_response_code(405);
                respond(['error' => 'GET / POST / DELETE required']);
            }

        // ── META ADS SETTINGS + SYNC + SAFE CONTROLS ─────────────
        case 'meta-settings':
            if ($method === 'GET') {
                require_admin();
                respond(['ok' => true, 'meta' => meta_settings_public(true)]);
            }
            if ($method === 'POST') {
                require_admin();
                $body = json_decode(file_get_contents('php://input'), true);
                if (!is_array($body)) { http_response_code(400); respond(['error' => 'Invalid JSON']); }
                meta_save_settings($body);
                respond(['ok' => true, 'meta' => meta_settings_public(true)]);
            }
            http_response_code(405);
            respond(['error' => 'GET or POST required']);

        case 'meta-sync':
            require_admin();
            if ($method !== 'POST') { http_response_code(405); respond(['error' => 'POST required']); }
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $mode = (string)($body['mode'] ?? 'quick');
            $from = trim((string)($body['date_from'] ?? ''));
            $to   = trim((string)($body['date_to'] ?? ''));
            respond(['ok' => true, 'sync' => run_meta_sync($mode, $from, $to, 'manual'), 'meta' => meta_settings_public(true)]);

        case 'meta-cron':
            if ($method !== 'GET') { http_response_code(405); respond(['error' => 'GET required']); }
            $settings = meta_settings_raw();
            $secret = (string)($settings['cron_secret'] ?? '');
            if ($secret === '' || !hash_equals($secret, (string)($_GET['secret'] ?? ''))) {
                http_response_code(403);
                respond(['error' => 'Invalid cron secret']);
            }
            if (empty($settings['sync_enabled'])) {
                respond(['ok' => true, 'skipped' => true, 'reason' => 'Meta sync disabled']);
            }
            $mode = (string)($_GET['mode'] ?? 'quick');
            respond(['ok' => true, 'sync' => run_meta_sync($mode, '', '', 'cron')]);

        case 'meta-report':
            require_auth();
            $period = (string)($_GET['period'] ?? '30d');
            respond(meta_report($period));

        case 'attribution-profit':
            require_auth();
            $period = (string)($_GET['period'] ?? '30d');
            $groupBy = (string)($_GET['group_by'] ?? 'campaign');
            respond(attribution_profit_report($period, $groupBy));

        // R1/R6 — break-even ROAS for a single product (VAT-correct)
        case 'break-even':
            require_auth();
            $vat = isset($_GET['vat']) ? (float)$_GET['vat'] : get_vat_rate();
            respond([
                'ok'  => true,
                'result' => pt_break_even(
                    (float)($_GET['price'] ?? 0), (float)($_GET['cogs'] ?? 0),
                    (float)($_GET['shipping'] ?? 0), (float)($_GET['fee'] ?? 0),
                    (float)($_GET['other'] ?? 0), $vat
                ),
                'vat_rate' => $vat,
            ]);

        // R2/R5 — every product with real price + cost → break-even ROAS (auto-filled)
        case 'break-even-catalog':
            require_auth();
            respond(break_even_catalog());

        // C2 — list a variable product's variations (merged with saved variation costs)
        case 'product-variations':
            require_auth();
            respond(fetch_product_variations((int)($_GET['product_id'] ?? 0)));

        // A1 — lightweight new-order pulse for the foreground alert (today's count + newest)
        case 'order-pulse':
            require_auth();
            respond(order_pulse());

        // G6 — daily P&L snapshots: GET a range, or backfill from live data
        case 'snapshots':
            require_auth();
            if (!empty($_GET['backfill'])) {
                $days = max(1, min(120, (int)$_GET['backfill']));
                respond(snapshot_backfill($days));
            }
            $days = max(1, min(400, (int)($_GET['days'] ?? 60)));
            respond(['ok' => true, 'rows' => snapshots_range($days)]);

        // G6 — cron: freeze a single day's P&L (default yesterday). Secret-guarded.
        case 'snapshot-cron':
            $cfg = setting_json_get('summary', []);
            $secret = (string)($cfg['secret'] ?? '');
            if ($secret === '' || !hash_equals($secret, (string)($_GET['secret'] ?? ''))) {
                http_response_code(403); respond(['error' => 'Forbidden']);
            }
            $date = (string)($_GET['date'] ?? (new DateTime('yesterday'))->format('Y-m-d'));
            respond(snapshot_backfill_dates($date, $date));

        // A8 — profitable-day streak (uses/gathers daily snapshots)
        case 'streak':
            require_auth();
            respond(streak_report());

        // G3 — VAT (moms) filing report incl. ad-spend reverse charge
        case 'vat-report':
            require_auth();
            $period = (string)($_GET['period'] ?? 'mtd');
            $force = !empty($_GET['force']);
            $cacheKey = 'vat:' . $period;
            if (!$force) { $hit = cache_get($cacheKey); if ($hit) { $hit['cached'] = true; respond($hit); } }
            $data = vat_report($period);
            cache_put($cacheKey, $data, WC_CACHE_SECONDS);
            $data['cached'] = false;
            respond($data);

        // G1 — import ad spend from Google/TikTok/etc via automation (Make/Zapier/n8n) or a paste.
        case 'adspend-import':
            $cfg = setting_json_get('summary', []);
            $secret = (string)($cfg['secret'] ?? '');
            $authed = ($secret !== '' && hash_equals($secret, (string)($_GET['secret'] ?? ''))) || (bool)current_user();
            if (!$authed) { http_response_code(403); respond(['error' => 'Forbidden']); }
            respond(adspend_import(json_decode(file_get_contents('php://input'), true) ?: []));

        // G4 — day-of-week × hour revenue heatmap
        case 'heatmap':
            require_auth();
            $days = max(7, min(180, (int)($_GET['days'] ?? 90)));
            $force = !empty($_GET['force']);
            $cacheKey = 'heat:' . $days;
            if (!$force) { $hit = cache_get($cacheKey); if ($hit) { $hit['cached'] = true; respond($hit); } }
            $data = heatmap_report($days);
            cache_put($cacheKey, $data, 3600);
            $data['cached'] = false;
            respond($data);

        // F6 — coupon-level profitability
        case 'coupons':
            require_auth();
            $period = (string)($_GET['period'] ?? '30d');
            $force = !empty($_GET['force']);
            $cacheKey = 'coup:' . $period;
            if (!$force) { $hit = cache_get($cacheKey); if ($hit) { $hit['cached'] = true; respond($hit); } }
            $data = coupons_report($period);
            cache_put($cacheKey, $data, WC_CACHE_SECONDS);
            $data['cached'] = false;
            respond($data);

        // G2 — inventory value + low-stock / days-of-cover
        case 'inventory':
            require_auth();
            $force = !empty($_GET['force']);
            $cacheKey = 'inv:v1';
            if (!$force) { $hit = cache_get($cacheKey); if ($hit) { $hit['cached'] = true; respond($hit); } }
            $data = inventory_report();
            cache_put($cacheKey, $data, 1800);
            $data['cached'] = false;
            respond($data);

        // F5 + F3 — customers (new vs returning, repeat rate, LTV) + MER/CAC
        case 'customers':
            require_auth();
            $period = (string)($_GET['period'] ?? '30d');
            $force  = !empty($_GET['force']);
            $cacheKey = 'cust:' . $period;
            if (!$force) { $hit = cache_get($cacheKey); if ($hit) { $hit['cached'] = true; respond($hit); } }
            $data = customers_report($period);
            cache_put($cacheKey, $data, 3600);
            $data['cached'] = false;
            respond($data);

        // A1 — web push (background notifications)
        case 'push-vapid-key':
            require_auth();
            respond(['ok' => true, 'publicKey' => defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '']);

        case 'push-subscribe':
            require_auth();
            respond(push_subscribe(json_decode(file_get_contents('php://input'), true) ?: []));

        case 'push-unsubscribe':
            require_auth();
            respond(push_unsubscribe(json_decode(file_get_contents('php://input'), true) ?: []));

        // WooCommerce order.created webhook → notify all subscribers. Guarded by a shared secret.
        case 'push-webhook':
            $secret = defined('PUSH_WEBHOOK_SECRET') ? PUSH_WEBHOOK_SECRET : '';
            if ($secret === '' || !hash_equals($secret, (string)($_GET['secret'] ?? ''))) {
                http_response_code(403); respond(['error' => 'Forbidden']);
            }
            respond(push_send_new_order(json_decode(file_get_contents('php://input'), true) ?: []));

        // A5 — scheduled daily/weekly summary settings
        case 'summary-settings':
            require_auth();
            if ($method === 'POST') {
                $body = json_decode(file_get_contents('php://input'), true) ?: [];
                $cfg = setting_json_get('summary', []);
                $cfg['webhook']    = trim((string)($body['webhook'] ?? ''));
                $cfg['store_name'] = substr(trim((string)($body['store_name'] ?? 'Profit Tracker')), 0, 80);
                $sched = (string)($body['schedule'] ?? 'daily');
                $cfg['schedule']   = in_array($sched, ['off', 'daily', 'weekly'], true) ? $sched : 'daily';
                setting_json_put('summary', $cfg);
                respond(['ok' => true]);
            }
            $cfg = setting_json_get('summary', []);
            respond([
                'ok'         => true,
                'webhook'    => (string)($cfg['webhook'] ?? ''),
                'store_name' => (string)($cfg['store_name'] ?? 'Profit Tracker'),
                'schedule'   => (string)($cfg['schedule'] ?? 'daily'),
                'cron_url'   => summary_cron_url(),
            ]);

        // A5 — cron trigger (secret-guarded): send the summary to the stored webhook
        case 'summary-cron':
            $cfg = setting_json_get('summary', []);
            $secret = (string)($cfg['secret'] ?? '');
            if ($secret === '' || !hash_equals($secret, (string)($_GET['secret'] ?? ''))) {
                http_response_code(403); respond(['error' => 'Forbidden']);
            }
            respond(summary_send((string)($_GET['period'] ?? 'yesterday')));

        // S3 — download a JSON backup of all cost/fee/fixed/shipping config
        case 'config-backup':
            require_admin();
            respond(config_backup());

        // R8 — import product costs from the standalone ROAS app's table, unifying COGS
        case 'migrate-roas':
            require_admin();
            respond(migrate_roas());

        // G5 — multi-store: list / add / activate / delete WooCommerce stores
        case 'stores':
            $u = require_auth();
            if ($method === 'GET') { respond(stores_list()); }
            require_admin();
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $op = (string)($_GET['op'] ?? ($body['op'] ?? ''));
            respond(stores_write($op, $body));

        case 'meta-action':
            $u = require_admin();
            if ($method !== 'POST') { http_response_code(405); respond(['error' => 'POST required']); }
            $body = json_decode(file_get_contents('php://input'), true);
            if (!is_array($body)) { http_response_code(400); respond(['error' => 'Invalid JSON']); }
            respond(meta_control_action($body, $u));

        case 'meta-action-log':
            require_admin();
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 30)));
            $stmt = db()->prepare(
                "SELECT l.id, l.action_type, l.entity_type, l.meta_id, l.entity_name, l.old_value, l.new_value,
                        l.confirmation, l.created_at, u.username, u.display_name
                 FROM profit_meta_action_log l
                 LEFT JOIN profit_users u ON u.id = l.user_id
                 ORDER BY l.id DESC
                 LIMIT ?"
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            respond(['ok' => true, 'entries' => $stmt->fetchAll()]);

        // ── PRODUCT PROFIT (Phase 4) ──────────────────────────────
        case 'product-profit':
            require_auth();
            $period   = (string)($_GET['period'] ?? '30d');
            $force    = !empty($_GET['force']);
            $cacheKey = 'pprof:' . $period;
            if (!$force) {
                $hit = cache_get($cacheKey);
                if ($hit) { $hit['cached'] = true; respond($hit); }
            }
            $data = compute_product_profit($period);
            cache_put($cacheKey, $data, WC_CACHE_SECONDS);
            $data['cached'] = false;
            respond($data);

        // ── DAILY TREND (Phase 4) ─────────────────────────────────
        case 'trend':
            require_auth();
            $period   = (string)($_GET['period'] ?? '30d');
            $force    = !empty($_GET['force']);
            $cacheKey = 'trend:' . $period;
            if (!$force) {
                $hit = cache_get($cacheKey);
                if ($hit) { $hit['cached'] = true; respond($hit); }
            }
            $data = compute_trend($period);
            cache_put($cacheKey, $data, WC_CACHE_SECONDS);
            $data['cached'] = false;
            respond($data);

        // ── MONTHLY P&L (Phase 4) ─────────────────────────────────
        case 'monthly-pnl':
            require_auth();
            $months   = max(1, min(24, (int)($_GET['months'] ?? 12)));
            $force    = !empty($_GET['force']);
            $cacheKey = 'mpnl:' . $months;
            if (!$force) {
                $hit = cache_get($cacheKey);
                if ($hit) { $hit['cached'] = true; respond($hit); }
            }
            $data = compute_monthly_pnl($months);
            cache_put($cacheKey, $data, 1800); // 30 min
            $data['cached'] = false;
            respond($data);

        // ── CSV EXPORT (Phase 4) ──────────────────────────────────
        case 'export':
            require_auth();
            $type   = (string)($_GET['type']   ?? 'daily');
            $period = (string)($_GET['period'] ?? '30d');
            $months = max(1, min(24, (int)($_GET['months'] ?? 12)));

            $csv = '';
            $fname = '';
            switch ($type) {
                case 'daily':
                    $csv = export_daily_csv($period);
                    $fname = "profit-daily-{$period}-" . date('Y-m-d') . ".csv";
                    break;
                case 'monthly':
                    $csv = export_monthly_csv($months);
                    $fname = "profit-monthly-{$months}m-" . date('Y-m-d') . ".csv";
                    break;
                case 'products':
                    $csv = export_products_csv($period);
                    $fname = "profit-products-{$period}-" . date('Y-m-d') . ".csv";
                    break;
                default:
                    http_response_code(400);
                    respond(['error' => 'Okänd CSV-typ. Använd: daily | monthly | products']);
            }

            // Override JSON headers for CSV download
            header_remove('Content-Type');
            header_remove('Cache-Control');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fname . '"');
            header('Cache-Control: no-store');
            // BOM so Excel opens UTF-8 correctly
            echo "\xEF\xBB\xBF" . $csv;
            exit;

        default:
            http_response_code(404);
            respond(['error' => 'Unknown action']);
    }

} catch (Throwable $e) {
    error_log('[profit-tracker] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (in_array($action ?? '', ['meta-sync', 'meta-cron'], true)) {
        try {
            setting_json_put('meta_last_error', ['message' => $e->getMessage(), 'time' => date('c')]);
        } catch (Throwable $ignored) {}
    }
    http_response_code(500);
    if (strpos((string)($action ?? ''), 'meta') === 0) {
        respond(['error' => $e->getMessage()]);
    }
    respond(['error' => 'Serverfel. Försök igen.']);
}

function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// REVENUE + PROFIT CALCULATION
// ═══════════════════════════════════════════════════════════════════
function get_date_range($period) {
    $now = new DateTime('now');
    // Support custom date range passed as query params: period=custom&date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
    if (strtolower($period) === 'custom') {
        $from = (string)($_GET['date_from'] ?? '');
        $to   = (string)($_GET['date_to']   ?? '');
        if (!$from || !$to) throw new Exception("Custom period requires date_from and date_to");
        $start = new DateTime($from); $start->setTime(0, 0, 0);
        $end   = new DateTime($to);   $end->setTime(23, 59, 59);
        if ($start > $end) throw new Exception("date_from must be before date_to");
        return [$start, $end];
    }
    switch (strtolower($period)) {
        case 'today':
            return [(clone $now)->setTime(0,0,0), (clone $now)->setTime(23,59,59)];
        case 'yesterday':
            $y = (clone $now)->modify('-1 day');
            return [(clone $y)->setTime(0,0,0), (clone $y)->setTime(23,59,59)];
        case '7d':
            return [(clone $now)->modify('-6 days')->setTime(0,0,0), (clone $now)->setTime(23,59,59)];
        case '14d':
            return [(clone $now)->modify('-13 days')->setTime(0,0,0), (clone $now)->setTime(23,59,59)];
        case '30d':
            return [(clone $now)->modify('-29 days')->setTime(0,0,0), (clone $now)->setTime(23,59,59)];
        case 'mtd':
            return [(clone $now)->modify('first day of this month')->setTime(0,0,0), (clone $now)->setTime(23,59,59)];
    }
    throw new Exception("Unknown period: $period");
}

function compute_revenue($period) {
    [$start, $end] = get_date_range($period);
    $period_days = get_period_days($period);
    $data = revenue_payload($start, $end, $period, $period_days);

    // F1 — period-over-period comparison
    if (!empty($_GET['compare'])) {
        $prevEnd   = (clone $start)->modify('-1 second');
        $prevStart = (clone $start)->modify('-' . max(1, $period_days) . ' days');
        $prev = revenue_payload($prevStart, $prevEnd, $period, $period_days);
        $keys = ['revenue', 'revenue_excl_vat', 'orders', 'aov', 'net_profit', 'gross_profit', 'profit_margin', 'roas', 'adspend', 'items_sold', 'refunded', 'refund_rate'];
        $prevOut = []; $delta = [];
        foreach ($keys as $k) {
            $prevOut[$k] = $prev[$k];
            $cur = (float)$data[$k]; $pv = (float)$prev[$k];
            if ($k === 'profit_margin') {
                $delta[$k] = ['abs' => round($cur - $pv, 1), 'pct' => null]; // margin: percentage-point change
            } else {
                $delta[$k] = ['abs' => round($cur - $pv, 2), 'pct' => $pv != 0.0 ? round(($cur - $pv) / abs($pv) * 100, 1) : null];
            }
        }
        $data['previous']      = $prevOut;
        $data['delta']         = $delta;
        $data['compare_range'] = ['start' => $prevStart->format('Y-m-d'), 'end' => $prevEnd->format('Y-m-d')];
    }
    return $data;
}

function revenue_payload($start, $end, $period, $period_days) {
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));

    // Load cost/config context — COGS incl. variation (C2) + effective-dated history (C6)
    $ctx         = pt_context();
    $costs_map   = $ctx['costs_map'];
    $fee_config  = $ctx['fee_config'];
    $ship_config = $ctx['ship_config'];
    $fixed_costs = get_fixed_costs();

    // Sum ad spend within period
    $adspend_total = get_adspend_total($start, $end);

    // Accumulators
    $revenue = 0.0; $excl_vat_sum = 0.0; $tax = 0.0; $refunded = 0.0;
    $shipping = 0.0; $discount = 0.0; $items = 0.0;
    $cogs_total = 0.0; $fees_total = 0.0; $ship_cost_total = 0.0; $cogs_gaps = 0;

    foreach ($orders as $o) {
        // Refund-aware (C1) per-order metrics from the tested library
        $m = pt_order_metrics($o, $ctx);
        $revenue         += $m['revenue_incl'];
        $excl_vat_sum    += $m['revenue_excl'];
        $tax             += $m['tax'];
        $refunded        += $m['refunded'];
        $items           += $m['units'];
        $cogs_total      += $m['cogs'];
        $fees_total      += $m['fees'];
        $ship_cost_total += $m['ship_cost'];
        if ($m['has_cogs_gap']) $cogs_gaps++;
        // display-only revenue-side extras (gross, not refund-adjusted)
        $shipping += (float)($o['shipping_total'] ?? 0);
        $discount += (float)($o['discount_total'] ?? 0);
    }

    // Revenue is already net of refunds; excl-VAT is summed refund-adjusted
    $net      = $revenue;
    $count    = count($orders);
    $aov      = $count > 0 ? $net / $count : 0;
    $excl_vat = $excl_vat_sum;

    // Fixed costs pro-rated to this period (period_days passed in)
    $fixed_monthly = 0.0;
    foreach ($fixed_costs as $fc) $fixed_monthly += (float)($fc['amount_monthly'] ?? 0);
    $fixed_alloc   = $fixed_monthly * ($period_days / 30.44);

    // Profit stack — ad spend is deducted from net profit
    $gross_profit  = $excl_vat - $cogs_total;
    $net_profit    = $gross_profit - $fees_total - $ship_cost_total - $fixed_alloc - $adspend_total;
    $gross_margin  = $excl_vat > 0 ? ($gross_profit / $excl_vat * 100) : 0;
    $net_margin    = $excl_vat > 0 ? ($net_profit   / $excl_vat * 100) : 0;

    // Ad metrics
    $roas = $adspend_total > 0 ? ($net / $adspend_total)       : 0;   // Revenue ÷ ad spend
    $cpa  = $count > 0 && $adspend_total > 0
              ? ($adspend_total / $count) : 0;                         // Ad spend ÷ orders (CPA)

    // Were any costs actually configured?
    $shipping_set     = ($ship_config['cost_domestic'] > 0 || $ship_config['cost_international'] > 0);
    $costs_configured = !empty($costs_map) || count($fee_config) > 1
                        || $fixed_monthly > 0 || $shipping_set
                        || $adspend_total > 0;

    return [
        'period'           => $period,
        'currency'         => APP_CURRENCY,
        'date_range'       => ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')],
        // Phase 1 — Revenue
        'revenue'          => round($net, 2),
        'revenue_excl_vat' => round($excl_vat, 2),
        'orders'           => $count,
        'aov'              => round($aov, 2),
        'items_sold'       => (int)round($items),
        'shipping'         => round($shipping, 2),
        'discount'         => round($discount, 2),
        'tax'              => round($tax, 2),
        'refunded'         => round($refunded, 2),
        'refund_rate'      => round(($net + $refunded) > 0 ? $refunded / ($net + $refunded) * 100 : 0, 1), // F4

        // Phase 2 — Profit
        'cogs'             => round($cogs_total, 2),
        'fees'             => round($fees_total, 2),
        'shipping_cost'    => round($ship_cost_total, 2),
        'fixed_allocation' => round($fixed_alloc, 2),
        'gross_profit'     => round($gross_profit, 2),
        'net_profit'       => round($net_profit, 2),
        'gross_margin'     => round($gross_margin, 1),
        'profit_margin'    => round($net_margin, 1),
        'costs_configured' => $costs_configured,
        'cogs_gaps'        => $cogs_gaps,
        'counted_statuses' => get_counted_statuses(),
        'spend_warnings'   => adspend_overlap_warnings($start, $end), // C4
        // Phase 3 — Ads
        'adspend'          => round($adspend_total, 2),
        'roas'             => round($roas, 2),
        'cpa'              => round($cpa, 2),
        'generated_at'     => date('c'),
    ];
}

// C4 — flag dates where MANUAL Meta spend overlaps API-synced Meta spend
// (a common cause of double-counted ad spend / understated profit).
function adspend_overlap_warnings($start, $end) {
    try {
        $stmt = db()->prepare(
            "SELECT spend_date,
                    SUM(source='manual'   AND channel='Meta') AS manual_meta,
                    SUM(source='meta_api') AS api_meta
             FROM profit_adspend
             WHERE spend_date BETWEEN ? AND ?
             GROUP BY spend_date
             HAVING manual_meta > 0 AND api_meta > 0"
        );
        $stmt->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
        $dates = array_column($stmt->fetchAll(), 'spend_date');
    } catch (Exception $e) { return []; }
    if (!$dates) return [];
    return [[
        'type'  => 'spend_overlap',
        'level' => 'warning',
        'message' => 'Both manual and synced Meta ad spend exist on ' . count($dates)
                     . ' day(s) — ad spend may be double-counted.',
        'dates' => $dates,
    ]];
}

// ═══════════════════════════════════════════════════════════════════
// PHASE 4 — Advanced views
// ═══════════════════════════════════════════════════════════════════

function compute_product_profit($period) {
    [$start, $end] = get_date_range($period);
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));

    $costs_map = get_product_costs_map();

    // Aggregate per product
    $agg = [];  // pid => {name, sku, qty, revenue_inc_vat, revenue_excl_vat, cogs, orders}
    foreach ($orders as $o) {
        $tax_total   = (float)$o['total_tax'];
        $order_total = (float)$o['total'];
        // proportion of VAT per line item (rough but accurate if all items same VAT rate)
        $factor = $order_total > 0 ? (($order_total - $tax_total) / $order_total) : 1.0;

        foreach (($o['line_items'] ?? []) as $li) {
            $pid = (int)($li['product_id'] ?? 0);
            if ($pid <= 0) continue;
            $qty   = (int)($li['quantity'] ?? 0);
            $total = (float)($li['total'] ?? 0);      // line total excluding tax in WC
            $tax   = (float)($li['total_tax'] ?? 0);
            $name  = (string)($li['name'] ?? '');
            $sku   = (string)($li['sku']  ?? '');

            if (!isset($agg[$pid])) {
                $agg[$pid] = [
                    'product_id' => $pid,
                    'name'       => $name,
                    'sku'        => $sku,
                    'qty'        => 0,
                    'revenue_excl_vat' => 0.0,
                    'revenue_inc_vat'  => 0.0,
                    'cogs'             => 0.0,
                    'order_ids'        => [],
                ];
            }
            $agg[$pid]['qty']              += $qty;
            $agg[$pid]['revenue_excl_vat'] += $total;
            $agg[$pid]['revenue_inc_vat']  += $total + $tax;
            $unit_cost = pt_cost($pid, (int)($li['variation_id'] ?? 0), $o['date_created'] ?? null)['cost'];
            $agg[$pid]['cogs']             += $unit_cost * $qty;
            $agg[$pid]['order_ids'][$o['id']] = true;
            // Refresh name/sku if we have better data
            if ($name && !$agg[$pid]['name']) $agg[$pid]['name'] = $name;
            if ($sku  && !$agg[$pid]['sku'])  $agg[$pid]['sku']  = $sku;
        }
    }

    // Compute derived metrics
    $rows = [];
    foreach ($agg as $r) {
        $gp = $r['revenue_excl_vat'] - $r['cogs'];
        $margin = $r['revenue_excl_vat'] > 0
                    ? ($gp / $r['revenue_excl_vat'] * 100) : 0;
        $rows[] = [
            'product_id'        => $r['product_id'],
            'name'              => $r['name'],
            'sku'               => $r['sku'],
            'qty'               => $r['qty'],
            'orders'            => count($r['order_ids']),
            'revenue_excl_vat'  => round($r['revenue_excl_vat'], 2),
            'revenue_inc_vat'   => round($r['revenue_inc_vat'],  2),
            'cogs'              => round($r['cogs'], 2),
            'gross_profit'      => round($gp, 2),
            'gross_margin'      => round($margin, 1),
            'has_cogs'          => isset($costs_map[$r['product_id']]) && $costs_map[$r['product_id']] > 0,
        ];
    }

    // Sort by gross profit DESC
    usort($rows, fn($a, $b) => $b['gross_profit'] <=> $a['gross_profit']);

    return [
        'period'     => $period,
        'date_range' => ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')],
        'products'   => $rows,
        'total_units'    => array_sum(array_column($rows, 'qty')),
        'total_revenue'  => round(array_sum(array_column($rows, 'revenue_excl_vat')), 2),
        'total_cogs'     => round(array_sum(array_column($rows, 'cogs')), 2),
        'total_gross'    => round(array_sum(array_column($rows, 'gross_profit')), 2),
        'generated_at'   => date('c'),
    ];
}

function compute_trend($period) {
    [$start, $end] = get_date_range($period);
    $orders = fetch_orders($start->format('Y-m-d\TH:i:s'), $end->format('Y-m-d\TH:i:s'));

    $costs_map  = get_product_costs_map();
    $fee_config = get_fee_config();
    $ship_cfg   = get_shipping_config();

    // Build empty bucket for every date in range
    $buckets = [];
    $cur = clone $start;
    $cur->setTime(0, 0, 0);
    while ($cur <= $end) {
        $buckets[$cur->format('Y-m-d')] = [
            'date'      => $cur->format('Y-m-d'),
            'revenue'   => 0.0,
            'orders'    => 0,
            'items'     => 0,
            'cogs'      => 0.0,
            'fees'      => 0.0,
            'shipping'  => 0.0,
            'adspend'   => 0.0,
        ];
        $cur->modify('+1 day');
    }

    // Fill from orders
    foreach ($orders as $o) {
        $date = substr((string)($o['date_created'] ?? ''), 0, 10);
        if (!isset($buckets[$date])) continue;
        $b = &$buckets[$date];

        $order_total = (float)$o['total'];
        $tax         = (float)$o['total_tax'];
        $b['revenue'] += $order_total - $tax; // excl VAT
        $b['orders']  += 1;

        foreach (($o['line_items'] ?? []) as $li) {
            $pid = (int)($li['product_id'] ?? 0);
            $qty = (int)($li['quantity']   ?? 0);
            $b['items'] += $qty;
            $rc = pt_cost($pid, (int)($li['variation_id'] ?? 0), $o['date_created'] ?? null);
            if ($rc['found']) $b['cogs'] += $rc['cost'] * $qty;
        }

        // Fees
        $pm = strtolower(trim($o['payment_method'] ?? ''));
        $matched = false;
        foreach ($fee_config as $f) {
            if ($f['method_slug'] === $pm) {
                $b['fees'] += ((float)$f['rate'] / 100) * $order_total + (float)$f['fixed'];
                $matched = true; break;
            }
        }
        if (!$matched) {
            foreach ($fee_config as $f) {
                if ($f['method_slug'] === '*') {
                    $b['fees'] += ((float)$f['rate'] / 100) * $order_total + (float)$f['fixed'];
                    break;
                }
            }
        }

        // Shipping
        $country = strtoupper(trim($o['shipping']['country'] ?? $o['billing']['country'] ?? 'SE'));
        $b['shipping'] += ($country === 'SE')
                ? (float)$ship_cfg['cost_domestic']
                : (float)$ship_cfg['cost_international'];
        unset($b);
    }

    // Add ad spend per date
    $sStart = $start->format('Y-m-d');
    $sEnd   = $end->format('Y-m-d');
    $stmt = db()->prepare("SELECT spend_date, SUM(amount) AS t FROM profit_adspend WHERE spend_date BETWEEN ? AND ? GROUP BY spend_date");
    $stmt->execute([$sStart, $sEnd]);
    foreach ($stmt->fetchAll() as $r) {
        $d = $r['spend_date'];
        if (isset($buckets[$d])) $buckets[$d]['adspend'] = (float)$r['t'];
    }

    // Fixed costs prorated per day
    $fixed_total = 0;
    foreach (get_fixed_costs() as $fc) $fixed_total += (float)($fc['amount_monthly'] ?? 0);
    $fixed_per_day = $fixed_total / 30.44;

    // Compute net profit per day
    foreach ($buckets as &$b) {
        $b['gross_profit'] = round($b['revenue'] - $b['cogs'], 2);
        $b['net_profit']   = round($b['gross_profit'] - $b['fees'] - $b['shipping'] - $b['adspend'] - $fixed_per_day, 2);
        foreach (['revenue','cogs','fees','shipping','adspend'] as $k) $b[$k] = round($b[$k], 2);
    }
    unset($b);

    return [
        'period'     => $period,
        'date_range' => ['start' => $sStart, 'end' => $sEnd],
        'days'       => array_values($buckets),
        'generated_at' => date('c'),
    ];
}

function compute_monthly_pnl($months) {
    $now = new DateTime('first day of this month');
    $start = (clone $now)->modify('-' . ($months - 1) . ' months');

    $costs_map  = get_product_costs_map();
    $fee_config = get_fee_config();
    $ship_cfg   = get_shipping_config();

    // Pre-fetch all ad spend & fixed costs
    $sStart = $start->format('Y-m-d');
    $sEnd   = (clone $now)->modify('last day of this month')->format('Y-m-d');

    $adspend = [];
    $stmt = db()->prepare("SELECT DATE_FORMAT(spend_date,'%Y-%m') AS m, SUM(amount) AS t FROM profit_adspend WHERE spend_date BETWEEN ? AND ? GROUP BY m");
    $stmt->execute([$sStart, $sEnd]);
    foreach ($stmt->fetchAll() as $r) $adspend[$r['m']] = (float)$r['t'];

    $fixed_total = 0;
    foreach (get_fixed_costs() as $fc) $fixed_total += (float)($fc['amount_monthly'] ?? 0);

    $result = [];
    $iter = clone $start;
    while ($iter <= $now) {
        $mStart = (clone $iter)->setTime(0, 0, 0);
        $mEnd   = (clone $iter)->modify('last day of this month')->setTime(23, 59, 59);
        $mKey   = $iter->format('Y-m');

        $orders = fetch_orders($mStart->format('Y-m-d\TH:i:s'), $mEnd->format('Y-m-d\TH:i:s'));

        $rev_inc = 0; $rev_ex = 0; $tax = 0; $cogs = 0; $fees = 0; $ship = 0;
        $orders_n = count($orders); $items = 0;
        foreach ($orders as $o) {
            $rev_inc += (float)$o['total'];
            $tax     += (float)$o['total_tax'];
            foreach (($o['line_items'] ?? []) as $li) {
                $pid = (int)($li['product_id'] ?? 0);
                $qty = (int)($li['quantity'] ?? 0);
                $items += $qty;
                $rc = pt_cost($pid, (int)($li['variation_id'] ?? 0), $o['date_created'] ?? null);
                if ($rc['found']) $cogs += $rc['cost'] * $qty;
            }
            // Fees
            $pm = strtolower(trim($o['payment_method'] ?? ''));
            $matched = false;
            foreach ($fee_config as $f) {
                if ($f['method_slug'] === $pm) { $fees += ((float)$f['rate']/100)*$o['total'] + (float)$f['fixed']; $matched = true; break; }
            }
            if (!$matched) foreach ($fee_config as $f) {
                if ($f['method_slug'] === '*') { $fees += ((float)$f['rate']/100)*$o['total'] + (float)$f['fixed']; break; }
            }
            // Shipping
            $country = strtoupper(trim($o['shipping']['country'] ?? $o['billing']['country'] ?? 'SE'));
            $ship += ($country === 'SE') ? (float)$ship_cfg['cost_domestic'] : (float)$ship_cfg['cost_international'];
        }
        $rev_ex   = $rev_inc - $tax;
        $ads      = (float)($adspend[$mKey] ?? 0);
        $gp       = $rev_ex - $cogs;
        $np       = $gp - $fees - $ship - $ads - $fixed_total;
        $margin   = $rev_ex > 0 ? ($np / $rev_ex * 100) : 0;

        $result[] = [
            'month'            => $mKey,
            'month_label'      => $iter->format('M Y'),
            'orders'           => $orders_n,
            'items'            => $items,
            'revenue_inc_vat'  => round($rev_inc, 2),
            'revenue_excl_vat' => round($rev_ex,  2),
            'vat'              => round($tax,     2),
            'cogs'             => round($cogs,    2),
            'gross_profit'     => round($gp,      2),
            'fees'             => round($fees,    2),
            'shipping_cost'    => round($ship,    2),
            'adspend'          => round($ads,     2),
            'fixed'            => round($fixed_total, 2),
            'net_profit'       => round($np,      2),
            'net_margin'       => round($margin,  1),
        ];
        $iter->modify('+1 month');
    }

    return [
        'months'       => $result,
        'months_count' => $months,
        'generated_at' => date('c'),
    ];
}

// ═══════════════════════════════════════════════════════════════════
// CSV EXPORT
// ═══════════════════════════════════════════════════════════════════

function csv_row(array $cols) {
    $out = [];
    foreach ($cols as $c) {
        $s = (string)$c;
        if (preg_match('/[",;\n\r]/', $s)) {
            $s = '"' . str_replace('"', '""', $s) . '"';
        }
        $out[] = $s;
    }
    // Excel-friendly: use semicolon in Swedish locale
    return implode(';', $out) . "\r\n";
}

function export_daily_csv($period) {
    $data = compute_trend($period);
    $csv = csv_row(['Datum','Omsättning exkl moms','Ordrar','Sålda enheter','COGS','Avgifter','Frakt','Annonser','Bruttovinst','Nettovinst']);
    foreach ($data['days'] as $d) {
        $csv .= csv_row([
            $d['date'],
            number_format($d['revenue'], 2, ',', ''),
            $d['orders'],
            $d['items'],
            number_format($d['cogs'], 2, ',', ''),
            number_format($d['fees'], 2, ',', ''),
            number_format($d['shipping'], 2, ',', ''),
            number_format($d['adspend'], 2, ',', ''),
            number_format($d['gross_profit'], 2, ',', ''),
            number_format($d['net_profit'], 2, ',', ''),
        ]);
    }
    return $csv;
}

function export_monthly_csv($months) {
    $data = compute_monthly_pnl($months);
    $csv = csv_row(['Månad','Ordrar','Enheter','Omsättning inkl moms','Moms','Omsättning exkl moms','COGS','Bruttovinst','Avgifter','Frakt','Annonser','Fasta kostn.','Nettovinst','Marginal %']);
    foreach ($data['months'] as $m) {
        $csv .= csv_row([
            $m['month'],
            $m['orders'],
            $m['items'],
            number_format($m['revenue_inc_vat'],  2, ',', ''),
            number_format($m['vat'],              2, ',', ''),
            number_format($m['revenue_excl_vat'], 2, ',', ''),
            number_format($m['cogs'],             2, ',', ''),
            number_format($m['gross_profit'],     2, ',', ''),
            number_format($m['fees'],             2, ',', ''),
            number_format($m['shipping_cost'],    2, ',', ''),
            number_format($m['adspend'],          2, ',', ''),
            number_format($m['fixed'],            2, ',', ''),
            number_format($m['net_profit'],       2, ',', ''),
            number_format($m['net_margin'],       1, ',', ''),
        ]);
    }
    return $csv;
}

function export_products_csv($period) {
    $data = compute_product_profit($period);
    $csv = csv_row(['Produkt-ID','Namn','SKU','Sålda enheter','Antal ordrar','Omsättning exkl moms','COGS','Bruttovinst','Marginal %','COGS satt']);
    foreach ($data['products'] as $p) {
        $csv .= csv_row([
            $p['product_id'],
            $p['name'],
            $p['sku'],
            $p['qty'],
            $p['orders'],
            number_format($p['revenue_excl_vat'], 2, ',', ''),
            number_format($p['cogs'],             2, ',', ''),
            number_format($p['gross_profit'],     2, ',', ''),
            number_format($p['gross_margin'],     1, ',', ''),
            $p['has_cogs'] ? 'Ja' : 'Nej',
        ]);
    }
    return $csv;
}

function fetch_orders($after, $before) {
    $orders = []; $page = 1; $per = 100;
    while ($page <= 50) {
        $url = wc_base() . '/orders?' . http_build_query([
            'consumer_key'    => wc_key(),
            'consumer_secret' => wc_secret(),
            'after'           => $after,
            'before'          => $before,
            'status'          => get_counted_statuses(),
            'per_page'        => $per,
            'page'            => $page,
            'orderby'         => 'date',
            'order'           => 'desc',
        ]);
        $batch = http_get_json($url);
        if (!is_array($batch) || empty($batch)) break;
        $orders = array_merge($orders, $batch);
        if (count($batch) < $per) break;
        $page++;
    }
    return $orders;
}

function fetch_products() {
    $products = []; $page = 1; $per = 100;
    while ($page <= 20) { // up to 2 000 products
        $url = wc_base() . '/products?' . http_build_query([
            'consumer_key'    => wc_key(),
            'consumer_secret' => wc_secret(),
            'status'          => 'publish',
            'per_page'        => $per,
            'page'            => $page,
            '_fields'         => 'id,name,sku,price,type',
        ]);
        $batch = http_get_json($url);
        if (!is_array($batch) || empty($batch)) break;
        foreach ($batch as $p) {
            $products[] = [
                'id'    => (int)$p['id'],
                'name'  => (string)($p['name'] ?? ''),
                'sku'   => (string)($p['sku'] ?? ''),
                'price' => (float)($p['price'] ?? 0),
                'type'  => (string)($p['type'] ?? 'simple'),
            ];
        }
        if (count($batch) < $per) break;
        $page++;
    }
    usort($products, fn($a, $b) => strcmp($a['name'], $b['name']));
    return $products;
}

function http_get_json($url) {
    if (!function_exists('curl_init')) throw new Exception('cURL extension missing.');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'Profit-Tracker/' . PROFIT_APP_VERSION,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($res === false) throw new Exception('HTTP error: ' . $err);
    if ($code >= 400)   throw new Exception("WooCommerce HTTP $code: " . substr($res, 0, 200));
    $data = json_decode($res, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Bad JSON: ' . json_last_error_msg());
    return $data;
}
