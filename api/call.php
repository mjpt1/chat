<?php
define('NEDA', true);
require dirname(__DIR__) . '/includes/init.php';
neda_require_post();
neda_csrf_check();
$me = neda_require_user();
$body = neda_body();
$action = (string) ($body['action'] ?? '');
$pdo = neda_pdo();
$myId = (int) $me['id'];

if ($action === 'start') {
    $peerCode = neda_en_digits(trim((string) ($body['public_id'] ?? '')));
    $media = (($body['media'] ?? 'video') === 'audio') ? 'audio' : 'video';
    $peer = neda_find_by_public_id($peerCode);
    if (!$peer || (int) $peer['id'] === $myId) {
        neda_json(['ok' => false, 'error' => 'مخاطب معتبر نیست.'], 422);
    }
    neda_conversation_id($myId, (int) $peer['id'], true);

    $open = $pdo->prepare("SELECT id FROM calls WHERE ((caller_id = ? AND callee_id = ?) OR (caller_id = ? AND callee_id = ?)) AND status IN ('ringing','active') LIMIT 1");
    $open->execute([$myId, (int) $peer['id'], (int) $peer['id'], $myId]);
    if ($open->fetch()) {
        neda_json(['ok' => false, 'error' => 'یک تماس باز با این فرد وجود دارد.'], 409);
    }

    $ins = $pdo->prepare('INSERT INTO calls (caller_id, callee_id, media, status, created_at) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$myId, (int) $peer['id'], $media, 'ringing', neda_now()]);
    neda_json(['ok' => true, 'call' => [
        'id' => (int) $pdo->lastInsertId(),
        'role' => 'caller',
        'media' => $media,
        'status' => 'ringing',
        'peer' => ['public_id' => $peer['public_id'], 'username' => $peer['username']],
    ]]);
}

$callId = (int) ($body['call_id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM calls WHERE id = ? LIMIT 1');
$st->execute([$callId]);
$call = $st->fetch();
if (!$call || ((int) $call['caller_id'] !== $myId && (int) $call['callee_id'] !== $myId)) {
    neda_json(['ok' => false, 'error' => 'تماس پیدا نشد.'], 404);
}

if ($action === 'accept') {
    if ((int) $call['callee_id'] !== $myId || $call['status'] !== 'ringing') {
        neda_json(['ok' => false, 'error' => 'این تماس را نمی‌توانید بپذیرید.'], 422);
    }
    $pdo->prepare("UPDATE calls SET status = 'active' WHERE id = ?")->execute([$callId]);
    neda_json(['ok' => true, 'call' => ['id' => $callId, 'status' => 'active', 'media' => $call['media'], 'role' => 'callee']]);
}

if ($action === 'reject') {
    if ((int) $call['callee_id'] !== $myId || $call['status'] !== 'ringing') {
        neda_json(['ok' => false, 'error' => 'این تماس را نمی‌توانید رد کنید.'], 422);
    }
    $pdo->prepare("UPDATE calls SET status = 'rejected', ended_at = ? WHERE id = ?")->execute([neda_now(), $callId]);
    neda_json(['ok' => true]);
}

if ($action === 'hangup') {
    if (!in_array($call['status'], ['ringing', 'active'], true)) {
        neda_json(['ok' => true]);
    }
    $status = $call['status'] === 'ringing' && (int) $call['caller_id'] === $myId ? 'missed' : 'ended';
    $pdo->prepare('UPDATE calls SET status = ?, ended_at = ? WHERE id = ?')->execute([$status, neda_now(), $callId]);
    neda_json(['ok' => true]);
}

neda_json(['ok' => false, 'error' => 'عملیات نامعتبر است.'], 422);
