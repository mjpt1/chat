<?php
define('NEDA', true);
require dirname(__DIR__) . '/includes/init.php';
neda_require_post();
neda_csrf_check();
$me = neda_require_user();
$body = neda_body();

$convId = (int) ($body['conversation_id'] ?? 0);
$text = trim((string) ($body['body'] ?? ''));
if ($convId < 1) {
    neda_json(['ok' => false, 'error' => 'گفتگو مشخص نیست.'], 422);
}
if ($text === '' || neda_len($text) > 2000) {
    neda_json(['ok' => false, 'error' => 'متن پیام خالی است یا بیش از حد طولانی است.'], 422);
}

$peer = neda_peer_in_conversation($convId, (int) $me['id']);
if (!$peer) {
    neda_json(['ok' => false, 'error' => 'به این گفتگو دسترسی ندارید.'], 403);
}

$pdo = neda_pdo();
$ins = $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body, created_at) VALUES (?, ?, ?, ?)');
$ins->execute([$convId, (int) $me['id'], $text, neda_now()]);
$msgId = (int) $pdo->lastInsertId();
$pdo->prepare('UPDATE conversations SET updated_at = ? WHERE id = ?')->execute([neda_now(), $convId]);

neda_json([
    'ok' => true,
    'message' => [
        'id' => $msgId,
        'conversation_id' => $convId,
        'sender_id' => (int) $me['id'],
        'body' => $text,
        'created_at' => neda_now(),
        'mine' => true,
    ],
]);
