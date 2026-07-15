<?php
/**
 * Nordic Trend Dashboard — API Proxy
 * Keeps all WooCommerce + Jetpack credentials server-side.
 * Frontend calls this file instead of hitting the APIs directly.
 *
 * Usage:
 *   api.php?action=orders
 *   api.php?action=visitors
 *   api.php?action=meta-report&period=today
 */

// ─── CORS Headers ────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ─── Config (PHP file — never served as source on any web server) ─────────────
$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) {
    $config = require $configPath;
} elseif (is_file(__DIR__ . '/config.json')) {
    // Legacy fallback — old JSON config is auto-migrated to config.php on first write.
    $config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
} else {
    $config = null;
}
if (!is_array($config)) {
    http_response_code(500);
    echo json_encode(['error' => 'Config not found or invalid']);
    exit();
}

$SITE        = rtrim((string)($config['site_url'] ?? ''), '/');
$CK          = (string)($config['wc_consumer_key'] ?? '');
$CS          = (string)($config['wc_consumer_secret'] ?? '');
$WP_USER     = (string)($config['wp_user'] ?? '');
$WP_PASS     = (string)($config['wp_app_password'] ?? '');
$APP_VERSION = $config['app_version'] ?? '2.0.0';
$BRIDGE_BASE = rtrim((string)($config['business_bridge_base'] ?? "{$SITE}/wp-json/as-business/v1"), '/');
$BRIDGE_KEY  = (string)($config['business_bridge_key'] ?? '');
$ROAS_DB     = is_array($config['roas_db'] ?? null) ? $config['roas_db'] : [];

require_once __DIR__ . '/push.php';

// ─── Login Helpers ──────────────────────────────────────────────────────────
function sessionsPath(): string {
    return __DIR__ . '/nordic_sessions.json';
}

function readSessions(): array {
    $path = sessionsPath();
    if (!is_file($path)) return [];
    $raw = file_get_contents($path);
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function writeSessions(array $sessions): void {
    $path = sessionsPath();
    $fp = fopen($path, 'c+');
    if (!$fp) throw new Exception('Session store is not writable');
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($sessions));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function getBearer(): string {
    $headers = function_exists('getallheaders') ? array_change_key_case(getallheaders() ?: [], CASE_LOWER) : [];
    $auth = $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    return stripos($auth, 'Bearer ') === 0 ? substr($auth, 7) : '';
}

function authUser(array $config): ?array {
    $token = getBearer();
    if (strlen($token) !== 64) return null;
    $sessions = readSessions();
    $hash = hash('sha256', $token);
    $row = $sessions[$hash] ?? null;
    if (!$row || (int)($row['expires'] ?? 0) < time()) return null;
    return [
        'username' => (string)($row['username'] ?? $config['auth_user'] ?? 'admin'),
        'display_name' => 'Nordic Trend Admin',
        'role' => 'admin',
    ];
}

function requireAuth(array $config): array {
    $user = authUser($config);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    return $user;
}

function jsonBody(): array {
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

function respondJson(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function writeConfig(array $config): void {
    $path = __DIR__ . '/config.php';
    $php = "<?php\n// Nordic Trend — secrets & settings. Generated file. Never commit real values.\nreturn "
         . var_export($config, true) . ";\n";
    file_put_contents($path, $php, LOCK_EX);
    // config.php is a PHP file, so it can be OPcache-cached; invalidate so the
    // next request reads the freshly-written values instead of a stale compile.
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($path, true);
    }
}

// ─── Helper: cURL request ────────────────────────────────────────────────────
function apiRequest(string $url, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'NordicTrend-Dashboard/' . ($GLOBALS['APP_VERSION'] ?? '1.5.0'),
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    return [
        'body'     => $response,
        'httpCode' => $httpCode,
        'error'    => $error,
    ];
}

function allowedBridgeQuery(array $source, array $allowed): array {
    $out = [];
    foreach ($allowed as $key) {
        if (isset($source[$key]) && $source[$key] !== '') {
            $out[$key] = substr((string)$source[$key], 0, 80);
        }
    }
    return $out;
}

function bridgeRequest(string $path, array $params = [], string $method = 'GET', array $body = []): array {
    $base = rtrim((string)($GLOBALS['BRIDGE_BASE'] ?? ''), '/');
    $key = (string)($GLOBALS['BRIDGE_KEY'] ?? '');
    if ($base === '' || $key === '') {
        return [
            'ok' => false,
            'configured' => false,
            'message' => 'Business Data Bridge is not configured yet.',
        ];
    }
    $url = $base . '/' . ltrim($path, '/');
    if ($params) $url .= '?' . http_build_query($params);
    $headers = ['X-AS-Business-Key: ' . $key, 'Accept: application/json'];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'NordicTrend-Dashboard/' . ($GLOBALS['APP_VERSION'] ?? '1.5.0'),
        CURLOPT_HTTPHEADER     => strtoupper($method) === 'POST'
            ? array_merge($headers, ['Content-Type: application/json'])
            : $headers,
    ]);
    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error || $httpCode < 200 || $httpCode >= 300) {
        http_response_code(502);
        respondJson([
            'error' => 'Business Data Bridge error',
            'details' => $error ?: "HTTP {$httpCode}",
        ]);
    }
    $data = json_decode((string)$response, true);
    return is_array($data) ? $data : ['ok' => false, 'message' => 'Invalid bridge response'];
}

function metaLogPath(): string {
    return __DIR__ . '/nordic_meta_actions.json';
}

function readMetaLog(): array {
    $path = metaLogPath();
    if (!is_file($path)) return [];
    $data = json_decode(file_get_contents($path) ?: '[]', true);
    return is_array($data) ? $data : [];
}

function writeMetaLog(array $rows): void {
    $rows = array_slice($rows, 0, 200);
    file_put_contents(metaLogPath(), json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function metaSettingsForClient(array $config): array {
    return [
        'ad_account_id' => (string)($config['meta_ad_account_id'] ?? ''),
        'api_version' => (string)($config['meta_api_version'] ?? 'v25.0'),
        'access_token_set' => !empty($config['meta_access_token']),
        'last_synced_at' => (string)($config['meta_last_synced_at'] ?? ''),
    ];
}

function requireMetaConfig(array $config): array {
    $account = preg_replace('/[^0-9]/', '', (string)($config['meta_ad_account_id'] ?? ''));
    $token = trim((string)($config['meta_access_token'] ?? ''));
    $version = trim((string)($config['meta_api_version'] ?? 'v25.0')) ?: 'v25.0';
    if ($account === '' || $token === '') {
        http_response_code(400);
        respondJson(['error' => 'Meta ad account ID and access token must be saved first.']);
    }
    return [$account, $token, $version];
}

function metaApi(array $config, string $path, array $params = [], string $method = 'GET'): array {
    [, $token, $version] = requireMetaConfig($config);
    $base = 'https://graph.facebook.com/' . rawurlencode($version) . '/' . ltrim($path, '/');
    $params['access_token'] = $token;
    $url = $base;
    $body = null;
    if (strtoupper($method) === 'GET') {
        $url .= '?' . http_build_query($params);
    } else {
        $body = http_build_query($params);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'NordicTrend-Dashboard/' . ($GLOBALS['APP_VERSION'] ?? '1.5.0'),
    ]);
    if (strtoupper($method) !== 'GET') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    $json = json_decode($response ?: 'null', true);
    if ($error || $httpCode < 200 || $httpCode >= 300) {
        http_response_code(502);
        respondJson([
            'error' => 'Meta API error',
            'details' => $error ?: ($json['error']['message'] ?? "HTTP {$httpCode}"),
        ]);
    }
    return is_array($json) ? $json : [];
}

function metaPeriodToPreset(string $period): string {
    return in_array($period, ['today','yesterday','last_7d','last_14d','last_30d'], true) ? $period : 'today';
}

// ─── Route: action parameter ─────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

if ($action === 'auth-status') {
    respondJson([
        'ok' => true,
        'app' => 'Nordic Trend',
        'version' => $APP_VERSION,
        'logged_in' => authUser($config) !== null,
    ]);
}

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        respondJson(['error' => 'POST required']);
    }
    $body = jsonBody();
    $username = trim((string)($body['username'] ?? ''));
    $password = (string)($body['password'] ?? '');
    $expectedUser = (string)($config['auth_user'] ?? 'admin');
    $hash = (string)($config['auth_password_hash'] ?? '');
    if ($username !== $expectedUser || $hash === '' || !password_verify($password, $hash)) {
        usleep(300000);
        http_response_code(401);
        respondJson(['error' => 'Fel användarnamn eller lösenord']);
    }
    $token = bin2hex(random_bytes(32));
    $sessions = readSessions();
    $sessions[hash('sha256', $token)] = [
        'username' => $expectedUser,
        'expires' => time() + (max(1, (int)($config['auth_session_days'] ?? 30)) * 86400),
        'ip' => substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 180),
        'created_at' => time(),
    ];
    writeSessions($sessions);
    respondJson(['ok' => true, 'token' => $token, 'user' => ['username' => $expectedUser, 'role' => 'admin']]);
}

if ($action === 'logout') {
    $token = getBearer();
    if ($token !== '') {
        $sessions = readSessions();
        unset($sessions[hash('sha256', $token)]);
        writeSessions($sessions);
    }
    respondJson(['ok' => true]);
}

if ($action === 'profile') {
    respondJson(['ok' => true, 'user' => requireAuth($config)]);
}

if ($action === 'change-password') {
    requireAuth($config);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        respondJson(['error' => 'POST required']);
    }
    $body = jsonBody();
    $current = (string)($body['current_password'] ?? '');
    $new = (string)($body['new_password'] ?? '');
    if (strlen($new) < 8) {
        http_response_code(400);
        respondJson(['error' => 'Nytt lösenord måste vara minst 8 tecken.']);
    }
    if (!password_verify($current, (string)($config['auth_password_hash'] ?? ''))) {
        http_response_code(400);
        respondJson(['error' => 'Nuvarande lösenord stämmer inte.']);
    }
    $config['auth_password_hash'] = password_hash($new, PASSWORD_DEFAULT);
    writeConfig($config);
    writeSessions([]);
    respondJson(['ok' => true]);
}

requireAuth($config);

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: meta-settings — Save server-side Meta credentials and ad account
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'meta-settings') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = jsonBody();
        $account = preg_replace('/[^0-9]/', '', (string)($body['ad_account_id'] ?? ''));
        $version = trim((string)($body['api_version'] ?? 'v25.0')) ?: 'v25.0';
        $token = trim((string)($body['access_token'] ?? ''));
        $config['meta_ad_account_id'] = $account;
        $config['meta_api_version'] = preg_match('/^v[0-9]+\.[0-9]+$/', $version) ? $version : 'v25.0';
        if ($token !== '') {
            $config['meta_access_token'] = $token;
        }
        writeConfig($config);
    }
    respondJson(['ok' => true, 'settings' => metaSettingsForClient($config)]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: store-settings — Save WooCommerce / WordPress API keys from the app
//   so credentials can be rotated in Settings without editing config.php by hand.
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'store-settings') {
    // Show which secrets are set (with a short masked hint) without ever
    // returning the full key/secret to the browser.
    $maskTail = function (string $value): string {
        $value = trim($value);
        if ($value === '') return '';
        $len = strlen($value);
        $tail = substr($value, -4);
        return '••••' . ($len > 4 ? $tail : '');
    };
    $storeSettingsForClient = function (array $config) use ($maskTail): array {
        return [
            'site_url'               => (string)($config['site_url'] ?? ''),
            'wp_user'                => (string)($config['wp_user'] ?? ''),
            'business_bridge_base'   => (string)($config['business_bridge_base'] ?? ''),
            'wc_consumer_key_set'    => trim((string)($config['wc_consumer_key'] ?? '')) !== '' && stripos((string)$config['wc_consumer_key'], 'PASTE') === false,
            'wc_consumer_secret_set' => trim((string)($config['wc_consumer_secret'] ?? '')) !== '' && stripos((string)$config['wc_consumer_secret'], 'PASTE') === false,
            'wp_app_password_set'    => trim((string)($config['wp_app_password'] ?? '')) !== '' && stripos((string)$config['wp_app_password'], 'PASTE') === false,
            'bridge_key_set'         => trim((string)($config['business_bridge_key'] ?? '')) !== '',
            'wc_consumer_key_hint'   => $maskTail((string)($config['wc_consumer_key'] ?? '')),
            'wp_app_password_hint'   => $maskTail((string)($config['wp_app_password'] ?? '')),
        ];
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = jsonBody();

        // Non-secret fields: always applied when a value is supplied.
        $siteUrl = trim((string)($body['site_url'] ?? ''));
        if ($siteUrl !== '') {
            if (!preg_match('#^https?://#i', $siteUrl)) $siteUrl = 'https://' . $siteUrl;
            $config['site_url'] = rtrim($siteUrl, '/');
        }
        if (array_key_exists('wp_user', $body)) {
            $config['wp_user'] = trim((string)$body['wp_user']);
        }
        if (array_key_exists('business_bridge_base', $body)) {
            $bridgeBase = trim((string)$body['business_bridge_base']);
            $config['business_bridge_base'] = $bridgeBase !== '' ? rtrim($bridgeBase, '/') : '';
        }

        // Secret fields: only overwritten when a non-empty value is provided,
        // so leaving a box blank keeps the currently-saved secret.
        $wcKey = trim((string)($body['wc_consumer_key'] ?? ''));
        if ($wcKey !== '') $config['wc_consumer_key'] = $wcKey;
        $wcSecret = trim((string)($body['wc_consumer_secret'] ?? ''));
        if ($wcSecret !== '') $config['wc_consumer_secret'] = $wcSecret;
        $wpPass = trim((string)($body['wp_app_password'] ?? ''));
        // WP application passwords are shown with spaces; the API accepts them either way.
        if ($wpPass !== '') $config['wp_app_password'] = $wpPass;
        $bridgeKey = trim((string)($body['business_bridge_key'] ?? ''));
        if ($bridgeKey !== '') $config['business_bridge_key'] = $bridgeKey;

        writeConfig($config);
    }
    respondJson(['ok' => true, 'settings' => $storeSettingsForClient($config)]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: test-connection — Verify the saved WooCommerce keys work right now.
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'test-connection') {
    $site = rtrim((string)($config['site_url'] ?? ''), '/');
    $ck   = (string)($config['wc_consumer_key'] ?? '');
    $cs   = (string)($config['wc_consumer_secret'] ?? '');
    if ($site === '' || $ck === '' || $cs === '' || stripos($ck, 'PASTE') !== false) {
        respondJson(['ok' => false, 'message' => 'WooCommerce keys are not saved yet. Add them above and press Save.']);
    }
    $url = "{$site}/wp-json/wc/v3/orders?consumer_key={$ck}&consumer_secret={$cs}&per_page=1";
    $result = apiRequest($url);
    if ($result['error'] || $result['httpCode'] !== 200) {
        respondJson([
            'ok' => false,
            'message' => 'WooCommerce rejected the keys: ' . ($result['error'] ?: "HTTP {$result['httpCode']}"),
        ]);
    }
    respondJson(['ok' => true, 'message' => 'WooCommerce connection works.']);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: meta-report — Fetch ad-level Meta spend/status/budget data
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'meta-report') {
    [$account] = requireMetaConfig($config);
    $period = metaPeriodToPreset((string)($_GET['period'] ?? 'today'));

    $insights = metaApi($config, "act_{$account}/insights", [
        'level' => 'ad',
        'date_preset' => $period,
        'fields' => 'date_start,date_stop,campaign_id,campaign_name,adset_id,adset_name,ad_id,ad_name,spend,impressions,clicks,ctr,cpc,cpm',
        'limit' => 500,
    ]);

    $ads = metaApi($config, "act_{$account}/ads", [
        'fields' => 'id,name,status,effective_status,adset{id,name,daily_budget,lifetime_budget,status,effective_status},campaign{id,name,daily_budget,lifetime_budget,status,effective_status}',
        'limit' => 500,
    ]);

    $details = [];
    foreach (($ads['data'] ?? []) as $ad) {
        $details[(string)($ad['id'] ?? '')] = $ad;
    }

    $rows = [];
    foreach (($insights['data'] ?? []) as $row) {
        $adId = (string)($row['ad_id'] ?? '');
        $detail = $details[$adId] ?? [];
        $adset = is_array($detail['adset'] ?? null) ? $detail['adset'] : [];
        $campaign = is_array($detail['campaign'] ?? null) ? $detail['campaign'] : [];
        $budgetEntity = '';
        $budgetObjectId = '';
        $budgetType = '';
        $budgetAmount = null;
        if (!empty($adset['daily_budget']) || !empty($adset['lifetime_budget'])) {
            $budgetEntity = 'adset';
            $budgetObjectId = (string)($adset['id'] ?? '');
            $budgetType = !empty($adset['daily_budget']) ? 'daily_budget' : 'lifetime_budget';
            $budgetAmount = ((float)($adset[$budgetType] ?? 0)) / 100;
        } elseif (!empty($campaign['daily_budget']) || !empty($campaign['lifetime_budget'])) {
            $budgetEntity = 'campaign';
            $budgetObjectId = (string)($campaign['id'] ?? '');
            $budgetType = !empty($campaign['daily_budget']) ? 'daily_budget' : 'lifetime_budget';
            $budgetAmount = ((float)($campaign[$budgetType] ?? 0)) / 100;
        }
        $rows[] = $row + [
            'ad_status' => (string)($detail['status'] ?? ''),
            'ad_effective_status' => (string)($detail['effective_status'] ?? ''),
            'adset_status' => (string)($adset['status'] ?? ''),
            'campaign_status' => (string)($campaign['status'] ?? ''),
            'budget_entity' => $budgetEntity,
            'budget_object_id' => $budgetObjectId,
            'budget_type' => $budgetType,
            'budget_amount' => $budgetAmount,
        ];
    }

    $config['meta_last_synced_at'] = gmdate('c');
    writeConfig($config);
    respondJson([
        'ok' => true,
        'period' => $period,
        'last_synced_at' => $config['meta_last_synced_at'],
        'rows' => $rows,
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: meta-action — Pause/activate ads and change ad set/campaign budgets
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'meta-action') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        respondJson(['error' => 'POST required']);
    }
    requireMetaConfig($config);
    $user = requireAuth($config);
    $body = jsonBody();
    $type = (string)($body['type'] ?? '');
    $objectId = preg_replace('/[^0-9]/', '', (string)($body['object_id'] ?? ''));
    $name = substr((string)($body['name'] ?? ''), 0, 180);
    $confirm = strtoupper(trim((string)($body['confirm'] ?? '')));
    if ($objectId === '') {
        http_response_code(400);
        respondJson(['error' => 'Missing Meta object ID.']);
    }

    $oldValue = (string)($body['old_value'] ?? '');
    $newValue = '';
    if ($type === 'pause' || $type === 'activate') {
        $required = $type === 'pause' ? 'PAUSE' : 'ACTIVATE';
        if ($confirm !== $required) {
            http_response_code(400);
            respondJson(['error' => "Type {$required} to confirm."]);
        }
        $status = $type === 'pause' ? 'PAUSED' : 'ACTIVE';
        metaApi($config, $objectId, ['status' => $status], 'POST');
        $newValue = $status;
    } elseif ($type === 'budget') {
        if ($confirm !== 'BUDGET') {
            http_response_code(400);
            respondJson(['error' => 'Type BUDGET to confirm.']);
        }
        $budgetType = (string)($body['budget_type'] ?? 'daily_budget');
        if (!in_array($budgetType, ['daily_budget','lifetime_budget'], true)) {
            http_response_code(400);
            respondJson(['error' => 'Invalid budget type.']);
        }
        $amount = (float)($body['amount'] ?? 0);
        if ($amount <= 0) {
            http_response_code(400);
            respondJson(['error' => 'Budget must be greater than zero.']);
        }
        metaApi($config, $objectId, [$budgetType => (string)max(100, (int)round($amount * 100))], 'POST');
        $newValue = $budgetType . ':' . number_format($amount, 2, '.', '');
    } else {
        http_response_code(400);
        respondJson(['error' => 'Invalid Meta action.']);
    }

    $log = readMetaLog();
    array_unshift($log, [
        'at' => gmdate('c'),
        'user' => $user['username'] ?? 'admin',
        'type' => $type,
        'object_id' => $objectId,
        'name' => $name,
        'old_value' => $oldValue,
        'new_value' => $newValue,
        'ip' => substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
    ]);
    writeMetaLog($log);
    respondJson(['ok' => true]);
}

if ($action === 'meta-action-log') {
    respondJson(['ok' => true, 'rows' => readMetaLog()]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: bridge-health / operations-summary / bridge-orders
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'bridge-health') {
    respondJson(bridgeRequest('health'));
}

if ($action === 'operations-summary') {
    respondJson(bridgeRequest('operations-summary', allowedBridgeQuery($_GET, ['period'])));
}

if ($action === 'bridge-orders') {
    respondJson(bridgeRequest('orders', allowedBridgeQuery($_GET, ['period', 'status', 'limit', 'page'])));
}

if ($action === 'attribution-summary') {
    respondJson(bridgeRequest('attribution-summary', allowedBridgeQuery($_GET, ['period', 'group_by', 'model'])));
}

if ($action === 'update-tracking') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        respondJson(['error' => 'POST required']);
    }

    $body = jsonBody();
    $orderId = (int)($body['order_id'] ?? 0);
    if ($orderId <= 0) {
        http_response_code(400);
        respondJson(['error' => 'Missing order_id.']);
    }

    $payload = [
        'tracking_number' => substr(trim((string)($body['tracking_number'] ?? '')), 0, 120),
        'carrier' => substr(trim((string)($body['carrier'] ?? 'YunExpress')), 0, 80),
        'carrier_url' => substr(trim((string)($body['carrier_url'] ?? '')), 0, 255),
        'shipment_status' => substr(trim((string)($body['shipment_status'] ?? 'in_transit')), 0, 40),
        'last_checkpoint' => substr(trim((string)($body['last_checkpoint'] ?? '')), 0, 500),
        'destination' => substr(trim((string)($body['destination'] ?? '')), 0, 160),
        'transit_days' => max(0, (int)($body['transit_days'] ?? 0)),
    ];

    if ($payload['tracking_number'] === '') {
        http_response_code(400);
        respondJson(['error' => 'Tracking number is required.']);
    }

    respondJson(bridgeRequest('orders/' . $orderId . '/tracking', [], 'POST', $payload));
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: orders — Fetch all WooCommerce orders (paginated, up to 500)
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'orders') {
    $allOrders = [];

    // Was capped at 5 pages (500 orders). Raised to 50 pages (5,000) with early-exit
    // when a page returns < 100, so it fetches everything without a runaway.
    for ($page = 1; $page <= 50; $page++) {
        $url = "{$SITE}/wp-json/wc/v3/orders?"
             . "consumer_key={$CK}&consumer_secret={$CS}"
             . "&per_page=100&page={$page}&orderby=date&order=desc";

        $result = apiRequest($url);

        if ($result['error'] || $result['httpCode'] !== 200) {
            http_response_code(502);
            echo json_encode([
                'error'   => 'WooCommerce API error',
                'details' => $result['error'] ?: "HTTP {$result['httpCode']}",
            ]);
            exit();
        }

        $data = json_decode($result['body'], true);
        if (!is_array($data) || empty($data)) break;

        $allOrders = array_merge($allOrders, $data);
        if (count($data) < 100) break;
    }

    echo json_encode($allOrders);
    exit();
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: visitors — Fetch Jetpack Stats (visitor data)
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'visitors') {
    $url = "{$SITE}/wp-json/jetpack/v4/module/stats/data";

    $result = apiRequest($url, [
        'Authorization: Basic ' . base64_encode("{$WP_USER}:{$WP_PASS}"),
    ]);

    if ($result['error'] || $result['httpCode'] !== 200) {
        http_response_code(502);
        echo json_encode([
            'error'   => 'Jetpack Stats API error',
            'details' => $result['error'] ?: "HTTP {$result['httpCode']}",
        ]);
        exit();
    }

    echo $result['body'];
    exit();
}

// ═══════════════════════════════════════════════════════════════════════════════
// UNKNOWN ACTION
// ═══════════════════════════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: push-* — web push subscriptions + test send
// ═══════════════════════════════════════════════════════════════════════════════
function pushSubsPath(): string { return __DIR__ . '/push_subs.json'; }
function readPushSubs(): array { $p = pushSubsPath(); if (!is_file($p)) return []; $d = json_decode(file_get_contents($p) ?: '[]', true); return is_array($d) ? $d : []; }
function writePushSubs(array $subs): void { file_put_contents(pushSubsPath(), json_encode($subs), LOCK_EX); }

if ($action === 'push-vapid') {
    respondJson(['ok' => true, 'key' => (string)($config['vapid_public'] ?? '')]);
}
if ($action === 'push-selftest') {
    respondJson(['ok' => true, 'result' => webpush_selftest((string)($config['vapid_private_pem'] ?? ''))]);
}
if ($action === 'push-subscribe') {
    $body = jsonBody();
    $sub = $body['subscription'] ?? null;
    if (!is_array($sub) || empty($sub['endpoint'])) { http_response_code(400); respondJson(['error' => 'Invalid subscription']); }
    $subs = readPushSubs();
    $subs[(string)$sub['endpoint']] = $sub;
    writePushSubs($subs);
    respondJson(['ok' => true, 'count' => count($subs)]);
}
if ($action === 'push-unsubscribe') {
    $body = jsonBody();
    $subs = readPushSubs();
    unset($subs[(string)($body['endpoint'] ?? '')]);
    writePushSubs($subs);
    respondJson(['ok' => true, 'count' => count($subs)]);
}
if ($action === 'push-test') {
    $subs = readPushSubs();
    $payload = json_encode(['title' => 'Nordic Trend', 'body' => '✅ Notifications are working.', 'url' => '/']);
    $sent = 0; $failed = 0;
    foreach ($subs as $ep => $sub) {
        $r = webpush_send($sub, $payload, $config);
        if ($r['ok']) { $sent++; } else { $failed++; if (in_array($r['code'], [404, 410], true)) unset($subs[$ep]); }
    }
    writePushSubs($subs);
    respondJson(['ok' => true, 'sent' => $sent, 'failed' => $failed, 'subscribers' => count($subs)]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: products-stock — WooCommerce products with stock info (for inventory view)
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'products-stock') {
    $all = [];
    for ($page = 1; $page <= 20; $page++) {
        $url = "{$SITE}/wp-json/wc/v3/products?"
             . "consumer_key={$CK}&consumer_secret={$CS}"
             . "&per_page=100&page={$page}&status=publish"
             . "&_fields=id,name,sku,price,type,manage_stock,stock_status,stock_quantity";
        $result = apiRequest($url);
        if ($result['error'] || $result['httpCode'] !== 200) {
            http_response_code(502);
            respondJson(['error' => 'WooCommerce products error', 'details' => $result['error'] ?: "HTTP {$result['httpCode']}"]);
        }
        $data = json_decode($result['body'], true);
        if (!is_array($data) || empty($data)) break;
        $all = array_merge($all, $data);
        if (count($data) < 100) break;
    }
    respondJson(['ok' => true, 'products' => $all]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: fees — get/save payment-processor fee settings (per method)
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'fees') {
    $defaults = [
        'swish'    => ['pct' => 0,   'flat' => 2],
        'klarna'   => ['pct' => 2.5, 'flat' => 2],
        'kustom'   => ['pct' => 2.5, 'flat' => 2],
        'revolut'  => ['pct' => 1.3, 'flat' => 2],
        '_default' => ['pct' => 2.5, 'flat' => 0],
    ];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = jsonBody();
        $in = is_array($body['fees'] ?? null) ? $body['fees'] : [];
        $clean = [];
        foreach ($defaults as $key => $def) {
            $row = is_array($in[$key] ?? null) ? $in[$key] : [];
            $clean[$key] = [
                'pct'  => max(0, min(20, (float)($row['pct'] ?? $def['pct']))),
                'flat' => max(0, min(1000, (float)($row['flat'] ?? $def['flat']))),
            ];
        }
        $config['payment_fees'] = $clean;
        writeConfig($config);
        respondJson(['ok' => true, 'fees' => $clean]);
    }
    $fees = is_array($config['payment_fees'] ?? null) ? $config['payment_fees'] : $defaults;
    respondJson(['ok' => true, 'fees' => $fees, 'currency' => (string)($config['currency'] ?? 'kr')]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTION: roas-products — unit-economics products (merged from ROAS Calculator)
//   requireAuth() above already gates every method here — the old open CRUD is gone.
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'roas-products') {
    $db = $ROAS_DB;
    if (empty($db['host']) || empty($db['name'])) {
        http_response_code(500);
        respondJson(['error' => 'ROAS database is not configured (roas_db in config.php).']);
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . $db['host'] . ';dbname=' . $db['name'] . ';charset=utf8mb4',
            $db['user'] ?? '', $db['pass'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS roas_products (
                id               BIGINT NOT NULL PRIMARY KEY,
                name             VARCHAR(100) NOT NULL,
                selling_price    DECIMAL(10,2) NOT NULL DEFAULT 0,
                product_cost     DECIMAL(10,2) NOT NULL DEFAULT 0,
                shipping_cost    DECIMAL(10,2) NOT NULL DEFAULT 0,
                transaction_fee  DECIMAL(5,2)  NOT NULL DEFAULT 0,
                created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $rows = $pdo->query("SELECT * FROM roas_products ORDER BY updated_at DESC")->fetchAll();
            respondJson(['products' => array_map(function ($r) {
                return [
                    'id'             => (int)$r['id'],
                    'name'           => $r['name'],
                    'sellingPrice'   => (float)$r['selling_price'],
                    'productCost'    => (float)$r['product_cost'],
                    'shippingCost'   => (float)$r['shipping_cost'],
                    'transactionFee' => (float)$r['transaction_fee'],
                    'savedAt'        => $r['updated_at'],
                ];
            }, $rows)]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $p = jsonBody();
            if (empty($p['id']) || empty($p['name'])) {
                http_response_code(400);
                respondJson(['error' => 'id and name are required']);
            }
            $stmt = $pdo->prepare("
                INSERT INTO roas_products
                    (id, name, selling_price, product_cost, shipping_cost, transaction_fee)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name), selling_price = VALUES(selling_price),
                    product_cost = VALUES(product_cost), shipping_cost = VALUES(shipping_cost),
                    transaction_fee = VALUES(transaction_fee), updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                (int)$p['id'], substr((string)$p['name'], 0, 100),
                (float)($p['sellingPrice'] ?? 0), (float)($p['productCost'] ?? 0),
                (float)($p['shippingCost'] ?? 0), (float)($p['transactionFee'] ?? 0),
            ]);
            respondJson(['ok' => true]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM roas_products WHERE id = ?");
            $stmt->execute([$id]);
            respondJson(['ok' => true]);
        }

        http_response_code(405);
        respondJson(['error' => 'Method not allowed']);
    } catch (Exception $e) {
        http_response_code(500);
        respondJson(['error' => 'ROAS DB error: ' . $e->getMessage()]);
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action.']);
