<?php
/* ================================================================
   Fluenta — Web Push library (pure PHP, no Composer).
   Implements VAPID (RFC 8292, ES256 JWT) + aes128gcm payload
   encryption (RFC 8291 / RFC 8188) using only ext-openssl.
   Works on standard Hostinger shared hosting (PHP 7.4+/8.x).
   ================================================================ */

function b64u_encode($d) { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
function b64u_decode($d) { $d = strtr($d, '-_', '+/'); $p = strlen($d) % 4; if ($p) $d .= str_repeat('=', 4 - $p); return base64_decode($d); }

/* HKDF (RFC 5869) split into extract + expand. */
function hkdf_extract($salt, $ikm) { return hash_hmac('sha256', $ikm, $salt, true); }
function hkdf_expand($prk, $info, $len) {
  $out = ''; $t = ''; $i = 1;
  while (strlen($out) < $len) { $t = hash_hmac('sha256', $t . $info . chr($i), $prk, true); $out .= $t; $i++; }
  return substr($out, 0, $len);
}
function hkdf($salt, $ikm, $info, $len) { return hkdf_expand(hkdf_extract($salt, $ikm), $info, $len); }

/* Build a PEM public key from a raw uncompressed P-256 point (0x04||X||Y). */
function p256_pub_pem($raw65) {
  $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw65;
  return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
}
function pad32($s) { return str_pad($s, 32, "\x00", STR_PAD_LEFT); }

/* Raw 65-byte public point from an openssl EC key. */
function ec_public_raw($pkey) {
  $d = openssl_pkey_get_details($pkey);
  return "\x04" . pad32($d['ec']['x']) . pad32($d['ec']['y']);
}

/* Get (creating + persisting on first use) the server VAPID keypair.
   Returns ['pub_b64u' => ..., 'priv_pem' => ..., 'pub_raw' => 65 bytes]. */
function get_vapid() {
  static $v = null;
  if ($v) return $v;
  $rows = [];
  foreach (db()->query('SELECT k, v FROM ' . TBL_SETTINGS) as $r) $rows[$r['k']] = $r['v'];
  $priv = $rows['vapid_priv'] ?? '';
  $pub  = $rows['vapid_pub']  ?? '';
  if ($priv === '' || $pub === '') {
    $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    if (!$key) json_out(['error' => 'vapid_keygen_failed'], 500);
    openssl_pkey_export($key, $priv);
    $pub = b64u_encode(ec_public_raw($key));
    set_setting('vapid_priv', $priv);
    set_setting('vapid_pub', $pub);
  }
  $v = ['pub_b64u' => $pub, 'priv_pem' => $priv, 'pub_raw' => b64u_decode($pub)];
  return $v;
}

/* DER ECDSA signature -> raw 64-byte r||s (JOSE / ES256 format). */
function der_to_raw_sig($der) {
  $off = 0;
  if (ord($der[$off++]) !== 0x30) return false;            // SEQUENCE
  if (ord($der[$off]) & 0x80) $off += 1 + (ord($der[$off]) & 0x7f); else $off++; // seq length
  $readInt = function () use ($der, &$off) {
    if (ord($der[$off++]) !== 0x02) return false;          // INTEGER
    $len = ord($der[$off++]);
    $val = substr($der, $off, $len); $off += $len;
    $val = ltrim($val, "\x00");
    return str_pad($val, 32, "\x00", STR_PAD_LEFT);
  };
  $r = $readInt(); $s = $readInt();
  if ($r === false || $s === false) return false;
  return $r . $s;
}

/* VAPID Authorization header value for a given push endpoint. */
function vapid_auth_header($endpoint, $subject = 'mailto:admin@fluenta.klivrapps.com') {
  $v = get_vapid();
  $u = parse_url($endpoint);
  $aud = $u['scheme'] . '://' . $u['host'];
  $header  = b64u_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
  $payload = b64u_encode(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => $subject]));
  $signing = $header . '.' . $payload;
  $der = '';
  openssl_sign($signing, $der, $v['priv_pem'], OPENSSL_ALGO_SHA256);
  $raw = der_to_raw_sig($der);
  $jwt = $signing . '.' . b64u_encode($raw);
  return 'vapid t=' . $jwt . ', k=' . $v['pub_b64u'];
}

/* Encrypt a UTF-8 payload for a subscription (aes128gcm content coding). */
function encrypt_push_payload($ua_public_raw, $auth_secret, $plaintext) {
  $as = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
  $as_public = ec_public_raw($as);
  $ua_pkey = openssl_pkey_get_public(p256_pub_pem($ua_public_raw));
  $shared = openssl_pkey_derive($ua_pkey, $as);
  if ($shared === false) return false;
  $shared = substr($shared, -32);   // P-256 shared secret is 32 bytes

  $key_info = 'WebPush: info' . "\x00" . $ua_public_raw . $as_public;
  $ikm = hkdf($auth_secret, $shared, $key_info, 32);

  $salt = random_bytes(16);
  $prk  = hkdf_extract($salt, $ikm);
  $cek  = hkdf_expand($prk, "Content-Encoding: aes128gcm\x00", 16);
  $nonce = hkdf_expand($prk, "Content-Encoding: nonce\x00", 12);

  $tag = '';
  $cipher = openssl_encrypt($plaintext . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
  $record = $cipher . $tag;

  // aes128gcm header: salt(16) | rs(4) | idlen(1) | keyid(as_public 65) | ciphertext
  return $salt . pack('N', 4096) . chr(strlen($as_public)) . $as_public . $record;
}

/* Send one push. $payload = string|null (null = a wake-only push). */
function web_push_send($endpoint, $p256dh_b64u, $auth_b64u, $payload = null) {
  if (!function_exists('openssl_pkey_derive')) return [0, 'openssl_pkey_derive missing'];
  $headers = [
    'Authorization: ' . vapid_auth_header($endpoint),
    'TTL: 2419200',
  ];
  $body = '';
  if ($payload !== null && $payload !== '') {
    $enc = encrypt_push_payload(b64u_decode($p256dh_b64u), b64u_decode($auth_b64u), $payload);
    if ($enc === false) return [0, 'encrypt_failed'];
    $body = $enc;
    $headers[] = 'Content-Encoding: aes128gcm';
    $headers[] = 'Content-Type: application/octet-stream';
  } else {
    $headers[] = 'Content-Length: 0';
  }
  $ctx = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => implode("\r\n", $headers),
    'content' => $body,
    'timeout' => 15,
    'ignore_errors' => true,
  ]]);
  $res = @file_get_contents($endpoint, false, $ctx);
  $code = 0;
  if (isset($http_response_header) && preg_match('#\s(\d{3})\s#', $http_response_header[0] ?? '', $m)) $code = (int)$m[1];
  return [$code, $res];
}
