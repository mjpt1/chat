<?php
define('NEDA', true);
require dirname(__DIR__) . '/includes/init.php';
neda_require_post();
neda_csrf_check();

$body = neda_body();
$username = trim((string) ($body['username'] ?? ''));
$password = (string) ($body['password'] ?? '');

$st = neda_pdo()->prepare('SELECT id, password_hash FROM users WHERE username = ? LIMIT 1');
$st->execute([$username]);
$row = $st->fetch();
if (!$row || !password_verify($password, $row['password_hash'])) {
    neda_json(['ok' => false, 'error' => 'نام کاربری یا رمز عبور درست نیست.'], 401);
}

$_SESSION['user_id'] = (int) $row['id'];
session_regenerate_id(true);
neda_touch((int) $row['id']);

neda_json(['ok' => true, 'redirect' => 'app.php']);
