<?php
define('NEDA', true);
require dirname(__DIR__) . '/includes/init.php';
neda_require_post();
neda_csrf_check();

$body = neda_body();
$username = trim((string) ($body['username'] ?? ''));
$password = (string) ($body['password'] ?? '');

if (neda_len($username) < 3 || neda_len($username) > 40) {
    neda_json(['ok' => false, 'error' => 'نام کاربری باید بین ۳ تا ۴۰ نویسه باشد.'], 422);
}
if (!preg_match('/^[\p{L}\p{N}._-]+$/u', $username)) {
    neda_json(['ok' => false, 'error' => 'نام کاربری فقط می‌تواند حرف، عدد، نقطه، خط تیره و زیرخط باشد.'], 422);
}
if (strlen($password) < 8) {
    neda_json(['ok' => false, 'error' => 'رمز عبور حداقل ۸ نویسه باشد.'], 422);
}

$pdo = neda_pdo();
$exists = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$exists->execute([$username]);
if ($exists->fetch()) {
    neda_json(['ok' => false, 'error' => 'این نام کاربری قبلاً گرفته شده است.'], 409);
}

$publicId = neda_public_id();
for ($i = 0; $i < 8; $i++) {
    $check = $pdo->prepare('SELECT id FROM users WHERE public_id = ? LIMIT 1');
    $check->execute([$publicId]);
    if (!$check->fetch()) {
        break;
    }
    $publicId = neda_public_id();
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$ins = $pdo->prepare('INSERT INTO users (public_id, username, password_hash, created_at, last_seen) VALUES (?, ?, ?, ?, ?)');
$now = neda_now();
$ins->execute([$publicId, $username, $hash, $now, $now]);

$_SESSION['user_id'] = (int) $pdo->lastInsertId();
session_regenerate_id(true);

neda_json(['ok' => true, 'redirect' => 'app.php']);
