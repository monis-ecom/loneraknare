<?php
/**
 * ONE-TIME SCRIPT — Generate VAPID keys and auto-save to config.json
 * Visit this URL once, then DELETE this file immediately.
 */

$configPath = __DIR__ . '/config.json';

// ── Load existing config ─────────────────────────────────────────────────────
if (!file_exists($configPath)) {
    die(json_encode(['error' => 'config.json not found']));
}
$config = json_decode(file_get_contents($configPath), true);
if (!$config) {
    die(json_encode(['error' => 'config.json is invalid JSON']));
}

// ── Skip if already generated ────────────────────────────────────────────────
if (!empty($config['vapid_public_key'])) {
    echo '<pre style="font-family:monospace;font-size:14px;padding:20px;background:#1e293b;color:#22d3ee">';
    echo "✅ VAPID keys already exist in config.json — nothing to do.\n\n";
    echo "Public key: " . $config['vapid_public_key'] . "\n\n";
    echo "DELETE this file now!\n";
    echo '</pre>';
    exit();
}

// ── Generate EC key pair (P-256) ─────────────────────────────────────────────
$key = openssl_pkey_new([
    'curve_name'       => 'prime256v1',
    'private_key_type' => OPENSSL_KEYTYPE_EC,
]);

if (!$key) {
    die('<pre>ERROR: openssl_pkey_new() failed. Check PHP openssl extension.</pre>');
}

$details = openssl_pkey_get_details($key);
$x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
$y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
$publicKeyRaw = "\x04" . $x . $y;

function b64u(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$publicKey = b64u($publicKeyRaw);
openssl_pkey_export($key, $privatePem);

// ── Save into config.json ────────────────────────────────────────────────────
$config['vapid_public_key']  = $publicKey;
$config['vapid_private_key'] = $privatePem;

$saved = file_put_contents(
    $configPath,
    json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    LOCK_EX
);

// ── Output result ─────────────────────────────────────────────────────────────
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>VAPID Setup</title></head>
<body style="background:#07090F;color:#e2e8f0;font-family:monospace;padding:40px;line-height:1.8">
<?php if ($saved === false): ?>
  <h2 style="color:#f87171">❌ Failed to write config.json</h2>
  <p>Check file permissions on config.json (needs to be writable).</p>
  <p>Manually add these to config.json:</p>
  <pre style="background:#1e293b;padding:20px;border-radius:8px;color:#22d3ee;overflow-x:auto">
"vapid_public_key": "<?= $publicKey ?>",
"vapid_private_key": "<?= addslashes(str_replace("\n", '\n', $privatePem)) ?>"</pre>
<?php else: ?>
  <h2 style="color:#34d399">✅ VAPID keys saved to config.json</h2>
  <table style="border-collapse:collapse;width:100%;max-width:700px">
    <tr>
      <td style="padding:10px;color:#64748b;white-space:nowrap">Public key</td>
      <td style="padding:10px;background:#1e293b;border-radius:6px;color:#22d3ee;word-break:break-all;font-size:13px"><?= htmlspecialchars($publicKey) ?></td>
    </tr>
    <tr>
      <td style="padding:10px;color:#64748b;white-space:nowrap">Private key</td>
      <td style="padding:10px;color:#34d399;font-size:13px">✅ Saved (PEM format, hidden for security)</td>
    </tr>
  </table>
  <br>
  <div style="background:#1e293b;border:1px solid #f87171;border-radius:10px;padding:20px;max-width:500px">
    <strong style="color:#f87171">⚠️ IMPORTANT: Delete this file now!</strong><br>
    <span style="color:#94a3b8;font-size:14px">Go to your cPanel File Manager and delete <code>generate-vapid.php</code> immediately.</span>
  </div>
  <br>
  <p style="color:#64748b;font-size:14px">Next: set up the WooCommerce webhook, then re-add the app to your iPhone home screen.</p>
<?php endif; ?>
</body>
</html>
