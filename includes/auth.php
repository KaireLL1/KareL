<?php
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Asia/Jakarta');

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getPendingCount($pdo) {
    if (!isLoggedIn()) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM friendships WHERE receiver_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

function getFriendshipStatus($pdo, $myId, $theirId) {
    $stmt = $pdo->prepare("SELECT *, requester_id FROM friendships WHERE (requester_id=? AND receiver_id=?) OR (requester_id=? AND receiver_id=?)");
    $stmt->execute([$myId, $theirId, $theirId, $myId]);
    return $stmt->fetch();
}

function timeAgo($datetime) {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}
?>
