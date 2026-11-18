<?php
defined('NEDA') or exit;

function neda_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $st = neda_pdo()->prepare('SELECT id, public_id, username, created_at, last_seen FROM users WHERE id = ? LIMIT 1');
    $st->execute([(int) $_SESSION['user_id']]);
    $user = $st->fetch();
    return $user ?: null;
}

function neda_require_user(): array
{
    $user = neda_user();
    if (!$user) {
        if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
            neda_json(['ok' => false, 'error' => 'وارد حساب شوید.'], 401);
        }
        neda_redirect('login.php');
    }
    return $user;
}

function neda_touch(int $userId): void
{
    $st = neda_pdo()->prepare('UPDATE users SET last_seen = ? WHERE id = ?');
    $st->execute([neda_now(), $userId]);
}

function neda_find_by_public_id(string $publicId): ?array
{
    $st = neda_pdo()->prepare('SELECT id, public_id, username, last_seen FROM users WHERE public_id = ? LIMIT 1');
    $st->execute([$publicId]);
    $row = $st->fetch();
    return $row ?: null;
}

function neda_is_online(?string $lastSeen): bool
{
    if (!$lastSeen) {
        return false;
    }
    return (strtotime($lastSeen . ' UTC') >= time() - 20);
}

function neda_conversation_id(int $a, int $b, bool $create = true): ?int
{
    [$low, $high] = neda_pair_ids($a, $b);
    $pdo = neda_pdo();
    $st = $pdo->prepare('SELECT id FROM conversations WHERE user_low = ? AND user_high = ? LIMIT 1');
    $st->execute([$low, $high]);
    $id = $st->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    if (!$create) {
        return null;
    }
    $ins = $pdo->prepare('INSERT INTO conversations (user_low, user_high, updated_at) VALUES (?, ?, ?)');
    $ins->execute([$low, $high, neda_now()]);
    return (int) $pdo->lastInsertId();
}

function neda_peer_in_conversation(int $conversationId, int $me): ?array
{
    $st = neda_pdo()->prepare('SELECT user_low, user_high FROM conversations WHERE id = ? LIMIT 1');
    $st->execute([$conversationId]);
    $row = $st->fetch();
    if (!$row) {
        return null;
    }
    $peerId = ((int) $row['user_low'] === $me) ? (int) $row['user_high'] : (int) $row['user_low'];
    if ($peerId === $me) {
        return null;
    }
    $u = neda_pdo()->prepare('SELECT id, public_id, username, last_seen FROM users WHERE id = ? LIMIT 1');
    $u->execute([$peerId]);
    $peer = $u->fetch();
    return $peer ?: null;
}
