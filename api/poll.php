<?php
define('NEDA', true);
require dirname(__DIR__) . '/includes/init.php';
$me = neda_require_user();
$myId = (int) $me['id'];
neda_touch($myId);

$sinceMsg = (int) ($_GET['since_msg'] ?? 0);
$sinceSig = (int) ($_GET['since_sig'] ?? 0);
$convId = (int) ($_GET['conversation_id'] ?? 0);
$pdo = neda_pdo();

$inbox = $pdo->prepare(
    "SELECT c.id, c.status, c.media, c.caller_id, c.callee_id, u.username, u.public_id
     FROM calls c
     JOIN users u ON u.id = c.caller_id
     WHERE c.callee_id = ? AND c.status = 'ringing'
     ORDER BY c.id DESC LIMIT 1"
);
$inbox->execute([$myId]);
$incoming = $inbox->fetch() ?: null;

$active = $pdo->prepare(
    "SELECT c.id, c.status, c.media, c.caller_id, c.callee_id
     FROM calls c
     WHERE ((c.caller_id = ? OR c.callee_id = ?) AND c.status IN ('ringing','active'))
     ORDER BY c.id DESC LIMIT 1"
);
$active->execute([$myId, $myId]);
$myCall = $active->fetch() ?: null;

$signals = [];
if ($myCall) {
    $sig = $pdo->prepare('SELECT id, sender_id, payload FROM signals WHERE call_id = ? AND id > ? AND sender_id <> ? ORDER BY id ASC LIMIT 40');
    $sig->execute([(int) $myCall['id'], $sinceSig, $myId]);
    foreach ($sig as $row) {
        $decoded = json_decode($row['payload'], true);
        if (!is_array($decoded)) {
            continue;
        }
        $signals[] = [
            'id' => (int) $row['id'],
            'sender_id' => (int) $row['sender_id'],
            'payload' => $decoded,
        ];
    }
}

$messages = [];
$peer = null;
if ($convId > 0) {
    $peerRow = neda_peer_in_conversation($convId, $myId);
    if ($peerRow) {
        $peer = [
            'id' => (int) $peerRow['id'],
            'public_id' => $peerRow['public_id'],
            'username' => $peerRow['username'],
            'online' => neda_is_online($peerRow['last_seen'] ?? null),
        ];
        $msg = $pdo->prepare('SELECT id, conversation_id, sender_id, body, created_at FROM messages WHERE conversation_id = ? AND id > ? ORDER BY id ASC LIMIT 80');
        $msg->execute([$convId, $sinceMsg]);
        foreach ($msg as $row) {
            $messages[] = [
                'id' => (int) $row['id'],
                'conversation_id' => (int) $row['conversation_id'],
                'sender_id' => (int) $row['sender_id'],
                'body' => $row['body'],
                'created_at' => $row['created_at'],
                'mine' => (int) $row['sender_id'] === $myId,
            ];
        }
    }
}

$list = $pdo->prepare(
    "SELECT c.id, c.updated_at,
            CASE WHEN c.user_low = ? THEN c.user_high ELSE c.user_low END AS peer_id
     FROM conversations c
     WHERE c.user_low = ? OR c.user_high = ?
     ORDER BY c.updated_at DESC
     LIMIT 20"
);
$list->execute([$myId, $myId, $myId]);
$conversations = [];
foreach ($list as $row) {
    $u = $pdo->prepare('SELECT public_id, username, last_seen FROM users WHERE id = ? LIMIT 1');
    $u->execute([(int) $row['peer_id']]);
    $p = $u->fetch();
    if (!$p) {
        continue;
    }
    $last = $pdo->prepare('SELECT body FROM messages WHERE conversation_id = ? ORDER BY id DESC LIMIT 1');
    $last->execute([(int) $row['id']]);
    $preview = $last->fetchColumn() ?: '';
    $conversations[] = [
        'id' => (int) $row['id'],
        'updated_at' => $row['updated_at'],
        'preview' => neda_sub((string) $preview, 0, 80),
        'peer' => [
            'id' => (int) $row['peer_id'],
            'public_id' => $p['public_id'],
            'username' => $p['username'],
            'online' => neda_is_online($p['last_seen'] ?? null),
        ],
    ];
}

$incomingOut = null;
if ($incoming) {
    $convIdIncoming = neda_conversation_id($myId, (int) $incoming['caller_id'], true);
    $incomingOut = [
        'id' => (int) $incoming['id'],
        'media' => $incoming['media'],
        'conversation_id' => $convIdIncoming,
        'peer' => [
            'id' => (int) $incoming['caller_id'],
            'username' => $incoming['username'],
            'public_id' => $incoming['public_id'],
            'online' => true,
        ],
    ];
}

$callOut = null;
if ($myCall) {
    $role = ((int) $myCall['caller_id'] === $myId) ? 'caller' : 'callee';
    $callOut = [
        'id' => (int) $myCall['id'],
        'status' => $myCall['status'],
        'media' => $myCall['media'],
        'role' => $role,
    ];
}

neda_json([
    'ok' => true,
    'me' => [
        'id' => $myId,
        'public_id' => $me['public_id'],
        'username' => $me['username'],
    ],
    'incoming' => $incomingOut,
    'call' => $callOut,
    'signals' => $signals,
    'messages' => $messages,
    'conversations' => $conversations,
    'peer' => $peer,
]);
