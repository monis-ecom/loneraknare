<?php
// ─────────────────────────────────────────────────────────────────────────────
// Nordic Trend — Web Push library (pure PHP: VAPID + RFC 8291 aes128gcm).
// No Composer / external deps. Requires PHP 7.3+ (openssl_pkey_derive, hash_hkdf).
// ─────────────────────────────────────────────────────────────────────────────

function push_b64u_encode(string $x): string { return rtrim(strtr(base64_encode($x), '+/', '-_'), '='); }
function push_b64u_decode(string $x): string {
    $x = strtr($x, '-_', '+/');
    $pad = strlen($x) % 4;
    if ($pad) $x .= str_repeat('=', 4 - $pad);
    return base64_decode($x);
}

// Build a PEM public key from a raw 65-byte P-256 point (0x04 || X || Y).
function push_p256_pubkey_pem(string $raw): string {
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw;
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

// DER ECDSA signature -> raw 64-byte r||s (for ES256 JWT).
function push_ecdsa_der_to_raw(string $der): string {
    $off = 0;
    if (ord($der[$off++]) !== 0x30) return '';
    $seqLen = ord($der[$off++]);
    if ($seqLen & 0x80) { $off += ($seqLen & 0x7f); }
    $readInt = function () use (&$off, $der): string {
        if (ord($der[$off++]) !== 0x02) return '';
        $l = ord($der[$off++]);
        $v = substr($der, $off, $l); $off += $l;
        $v = ltrim($v, "\x00");
        return str_pad($v, 32, "\x00", STR_PAD_LEFT);
    };
    return $readInt() . $readInt();
}

// Raw 64-byte r||s -> DER (for verifying in the self-test).
function push_ecdsa_raw_to_der(string $raw): string {
    $r = ltrim(substr($raw, 0, 32), "\x00"); if ($r === '' || (ord($r[0]) & 0x80)) $r = "\x00" . $r;
    $s = ltrim(substr($raw, 32, 32), "\x00"); if ($s === '' || (ord($s[0]) & 0x80)) $s = "\x00" . $s;
    $seq = "\x02" . chr(strlen($r)) . $r . "\x02" . chr(strlen($s)) . $s;
    return "\x30" . chr(strlen($seq)) . $seq;
}

// Build a signed VAPID ES256 JWT for the given audience (push-service origin).
function push_vapid_jwt(string $aud, string $subject, string $privPem): string {
    $header = push_b64u_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $payload = push_b64u_encode(json_encode(['aud' => $aud, 'exp' => time() + 43200, 'sub' => $subject]));
    $signingInput = $header . '.' . $payload;
    $key = openssl_pkey_get_private($privPem);
    if (!$key) throw new Exception('Invalid VAPID private key');
    $der = '';
    openssl_sign($signingInput, $der, $key, OPENSSL_ALGO_SHA256);
    return $signingInput . '.' . push_b64u_encode(push_ecdsa_der_to_raw($der));
}

// Encrypt a payload for a subscription (RFC 8291 + RFC 8188 aes128gcm single record).
function push_encrypt(string $payload, string $uaPublicRaw, string $authSecret): string {
    $as = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    $asDet = openssl_pkey_get_details($as);
    $asPublic = "\x04" . $asDet['ec']['x'] . $asDet['ec']['y'];
    $uaKey = openssl_pkey_get_public(push_p256_pubkey_pem($uaPublicRaw));
    $ecdh = openssl_pkey_derive($uaKey, $as);
    if ($ecdh === false) throw new Exception('ECDH derive failed');

    $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\x00" . $uaPublicRaw . $asPublic, $authSecret);
    $salt = random_bytes(16);
    $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);

    $tag = '';
    $cipher = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    $body = $salt . pack('N', 4096) . chr(65) . $asPublic . $cipher . $tag;
    return $body;
}

// Send one push. Returns ['ok'=>bool,'code'=>int,'error'=>string].
function webpush_send(array $sub, string $payloadJson, array $cfg): array {
    $endpoint = (string)($sub['endpoint'] ?? '');
    $p256dh = push_b64u_decode((string)($sub['keys']['p256dh'] ?? ''));
    $auth = push_b64u_decode((string)($sub['keys']['auth'] ?? ''));
    if ($endpoint === '' || strlen($p256dh) !== 65 || strlen($auth) < 16) {
        return ['ok' => false, 'code' => 0, 'error' => 'bad subscription'];
    }
    try {
        $body = push_encrypt($payloadJson, $p256dh, $auth);
        $u = parse_url($endpoint);
        $aud = $u['scheme'] . '://' . $u['host'];
        $jwt = push_vapid_jwt($aud, (string)($cfg['push_subject'] ?? 'mailto:admin@example.com'), (string)$cfg['vapid_private_pem']);
    } catch (Exception $e) {
        return ['ok' => false, 'code' => 0, 'error' => $e->getMessage()];
    }
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Authorization: vapid t=' . $jwt . ', k=' . (string)$cfg['vapid_public'],
            'Content-Encoding: aes128gcm',
            'TTL: 86400',
            'Content-Type: application/octet-stream',
            'Content-Length: ' . strlen($body),
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    // 404/410 = subscription expired (caller should prune).
    return ['ok' => ($code >= 200 && $code < 300), 'code' => $code, 'error' => $err ?: (string)$resp];
}

// Self-test: sign+verify a VAPID JWT and encrypt->decrypt a payload round-trip.
function webpush_selftest(string $privPem): array {
    $out = ['jwt' => false, 'encrypt' => false];
    try {
        $jwt = push_vapid_jwt('https://example.com', 'mailto:test@example.com', $privPem);
        $parts = explode('.', $jwt);
        $der = push_ecdsa_raw_to_der(push_b64u_decode($parts[2]));
        $pub = openssl_pkey_get_public(openssl_pkey_get_details(openssl_pkey_get_private($privPem))['key']);
        $out['jwt'] = openssl_verify($parts[0] . '.' . $parts[1], $der, $pub, OPENSSL_ALGO_SHA256) === 1;
    } catch (Exception $e) { $out['jwt_error'] = $e->getMessage(); }
    try {
        $ua = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $uaDet = openssl_pkey_get_details($ua);
        $uaPublic = "\x04" . $uaDet['ec']['x'] . $uaDet['ec']['y'];
        $authSecret = random_bytes(16);
        $payload = json_encode(['title' => 'Nordic Trend', 'body' => 'round-trip']);
        $body = push_encrypt($payload, $uaPublic, $authSecret);
        $salt = substr($body, 0, 16);
        $idlen = ord($body[20]);
        $asPublic = substr($body, 21, $idlen);
        $ct = substr($body, 21 + $idlen);
        openssl_pkey_export($ua, $uaPriv);
        $ecdh = openssl_pkey_derive(openssl_pkey_get_public(push_p256_pubkey_pem($asPublic)), openssl_pkey_get_private($uaPriv));
        $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\x00" . $uaPublic . $asPublic, $authSecret);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);
        $tag = substr($ct, -16); $data = substr($ct, 0, -16);
        $plain = openssl_decrypt($data, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '');
        $out['encrypt'] = ($plain !== false) && (rtrim($plain, "\x02") === $payload);
    } catch (Exception $e) { $out['encrypt_error'] = $e->getMessage(); }
    return $out;
}
