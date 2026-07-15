<?php
/* Shared bootstrap for all Fluenta API endpoints: JSON output, a secure
   same-origin session, and a PDO connection (MySQL or SQLite).
   Fluenta uses its OWN prefixed tables (fluenta_*) so it can safely share
   a database with another app (e.g. Mission Control) without touching it.
   A local config.local.php (if present) overrides config.php for dev. */
declare(strict_types=1);
@ini_set('display_errors', '0');
error_reporting(E_ALL);

/* Default account seeded on a fresh database — login once, then you're
   forced to set your own password. */
const DEFAULT_USER     = 'monis';
const DEFAULT_PASSWORD = 'Fluenta2026!';

/* Fluenta's own tables (prefixed so they never collide with other apps). */
const TBL_USERS    = 'fluenta_users';
const TBL_PROGRESS = 'fluenta_progress';
const TBL_SETTINGS = 'fluenta_settings';
const TBL_PUSH     = 'fluenta_push';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

session_name('fluenta_sid');
session_set_cookie_params([
  'lifetime' => 60 * 60 * 24 * 30,
  'path'     => '/',
  'httponly' => true,
  'samesite' => 'Lax',
  'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

function json_out($data, int $code = 200) { http_response_code($code); echo json_encode($data); exit; }
function body(): array { $raw = file_get_contents('php://input'); $j = json_decode($raw ?: '', true); return is_array($j) ? $j : $_POST; }
function require_post() { if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') json_out(['error' => 'method_not_allowed'], 405); }

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $c = file_exists(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : require __DIR__ . '/config.php';
  try {
    if (($c['driver'] ?? 'mysql') === 'sqlite') {
      $pdo = new PDO('sqlite:' . $c['sqlite_path']);
    } else {
      $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $c['host'], $c['name'], $c['charset'] ?? 'utf8mb4');
      $pdo = new PDO($dsn, $c['user'], $c['pass']);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (Throwable $e) {
    json_out(['error' => 'db_unavailable'], 500);
  }
  $isSqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

  $usersDdl = $isSqlite
    ? 'CREATE TABLE IF NOT EXISTS ' . TBL_USERS . ' (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, pass_hash TEXT NOT NULL, name TEXT, must_change INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)'
    : 'CREATE TABLE IF NOT EXISTS ' . TBL_USERS . ' (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(190) UNIQUE NOT NULL, pass_hash VARCHAR(255) NOT NULL, name VARCHAR(120) NULL, must_change TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
  $pdo->query($usersDdl);

  // seed the default account (forced password change on first login)
  if ((int) $pdo->query('SELECT COUNT(*) FROM ' . TBL_USERS)->fetchColumn() === 0) {
    $seed = $pdo->prepare('INSERT INTO ' . TBL_USERS . ' (username, pass_hash, name, must_change) VALUES (?, ?, ?, 1)');
    $seed->execute([DEFAULT_USER, password_hash(DEFAULT_PASSWORD, PASSWORD_DEFAULT), 'Monis']);
  }

  $progDdl = $isSqlite
    ? 'CREATE TABLE IF NOT EXISTS ' . TBL_PROGRESS . ' (user_id INTEGER PRIMARY KEY, data TEXT, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)'
    : 'CREATE TABLE IF NOT EXISTS ' . TBL_PROGRESS . ' (user_id INT PRIMARY KEY, data LONGTEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
  $pdo->query($progDdl);

  $setDdl = $isSqlite
    ? 'CREATE TABLE IF NOT EXISTS ' . TBL_SETTINGS . ' (k TEXT PRIMARY KEY, v TEXT)'
    : 'CREATE TABLE IF NOT EXISTS ' . TBL_SETTINGS . ' (k VARCHAR(50) PRIMARY KEY, v TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
  $pdo->query($setDdl);

  // web-push subscriptions (one row per browser/device)
  $pushDdl = $isSqlite
    ? 'CREATE TABLE IF NOT EXISTS ' . TBL_PUSH . ' (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, endpoint TEXT UNIQUE NOT NULL, p256dh TEXT NOT NULL, auth TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP)'
    : 'CREATE TABLE IF NOT EXISTS ' . TBL_PUSH . ' (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, endpoint VARCHAR(500) UNIQUE NOT NULL, p256dh VARCHAR(200) NOT NULL, auth VARCHAR(100) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
  $pdo->query($pushDdl);
  return $pdo;
}

/* Effective AI config: config.php defaults, overlaid by anything saved in
   the settings table (set from the app's Settings screen). */
function ai_cfg(): array {
  static $c = null;
  if ($c) return $c;
  $base = file_exists(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : require __DIR__ . '/config.php';
  $c = [
    'openai_key'     => $base['openai_key']     ?? '',
    'openai_model'   => $base['openai_model']   ?? 'gpt-4o-mini',
    'stt_model'      => $base['stt_model']      ?? 'gpt-4o-mini-transcribe',
    'tts_model'      => $base['tts_model']      ?? 'gpt-4o-mini-tts',
    'realtime_model' => $base['realtime_model'] ?? 'gpt-realtime',
    'realtime_voice' => $base['realtime_voice'] ?? 'marin',
  ];
  try {
    foreach (db()->query('SELECT k, v FROM ' . TBL_SETTINGS) as $row) {
      if (array_key_exists($row['k'], $c) && $row['v'] !== '' && $row['v'] !== null) $c[$row['k']] = $row['v'];
    }
  } catch (Throwable $e) {}
  return $c;
}
function set_setting(string $k, string $v): void {
  $pdo = db();
  $upsert = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite')
    ? 'INSERT INTO ' . TBL_SETTINGS . ' (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v'
    : 'INSERT INTO ' . TBL_SETTINGS . ' (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)';
  $pdo->prepare($upsert)->execute([$k, $v]);
}

function current_user_row() {
  if (empty($_SESSION['uid'])) return null;
  $s = db()->prepare('SELECT id, username, name, must_change FROM ' . TBL_USERS . ' WHERE id = ?');
  $s->execute([$_SESSION['uid']]);
  return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}
