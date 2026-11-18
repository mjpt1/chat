<?php
define('NEDA', true);
require dirname(__DIR__) . '/includes/init.php';
neda_require_post();
neda_csrf_check();
$me = neda_require_user();
$body = neda_body();

$callId = (int) ($body['call_id'] ?? 0);
$payload = $body['payload'] ?? null;
if ($callId < 1 || !is_array($payload)) {
    neda_json(['ok' => false, 'error' => 'سیگنال ناقص است.'], 422);
}

$type = (string) ($payload['type'] ?? '');
if (!in_array($type, ['offer', 'answer', 'ice'], true)) {
    neda_json(['ok' => false, 'error' => 'نوع سیگنال نامعتبر است.'], 422);
}

$pdo = neda_pdo();
$st = $pdo->prepare("SELECT * FROM calls WHERE id = ? AND status IN ('ringing','active') LIMIT 1");
$st->execute([$callId]);
$call = $st->fetch();
if (!$call || ((int) $call['caller_id'] !== (int) $me['id'] && (int) $call['callee_id'] !== (int) $me['id'])) {
    neda_json(['ok' => false, 'error' => 'تماس فعال نیست.'], 404);
}

$json = json_encode($payload, JSON_UNESCAPED_UNICODE);
if ($json === false || strlen($json) > 200000) {
    neda_json(['ok' => false, 'error' => 'سیگنال بیش از حد بزرگ است.'], 422);
}

$ins = $pdo->prepare('INSERT INTO signals (call_id, sender_id, payload, created_at) VALUES (?, ?, ?, ?)');
$ins->execute([$callId, (int) $me['id'], $json, neda_now()]);
neda_json(['ok' => true]);
