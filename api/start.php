<?php
define('NEDA', true);
require dirname(__DIR__) . '/includes/init.php';
neda_require_post();
neda_csrf_check();
$body = neda_body();
$me = neda_require_user();

$peerCode = neda_en_digits(trim((string) ($body['public_id'] ?? '')));
if (!preg_match('/^\d{6}$/', $peerCode)) {
    neda_json(['ok' => false, 'error' => 'شناسه باید ۶ رقم باشد.'], 422);
}

$peer = neda_find_by_public_id($peerCode);
if (!$peer) {
    neda_json(['ok' => false, 'error' => 'کاربری با این شناسه پیدا نشد.'], 404);
}
if ((int) $peer['id'] === (int) $me['id']) {
    neda_json(['ok' => false, 'error' => 'نمی‌توانید با خودتان گفتگو کنید.'], 422);
}

$convId = neda_conversation_id((int) $me['id'], (int) $peer['id'], true);
neda_json([
    'ok' => true,
    'conversation_id' => $convId,
    'peer' => [
        'id' => (int) $peer['id'],
        'public_id' => $peer['public_id'],
        'username' => $peer['username'],
        'online' => neda_is_online($peer['last_seen'] ?? null),
    ],
]);
