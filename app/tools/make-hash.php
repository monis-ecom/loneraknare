<?php
// Generate a bcrypt hash for the dashboard login password.
// Usage (command line):  php tools/make-hash.php "your-new-password"
// Then paste the printed hash into config.php -> auth_password_hash.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
$pw = $argv[1] ?? '';
if (strlen($pw) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}
echo password_hash($pw, PASSWORD_DEFAULT), "\n";
