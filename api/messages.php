<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$me = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toId    = (int)($_POST['to']      ?? 0);
    $content = trim($_POST['content']  ?? '');
    if (!$toId || !$content || mb_strlen($content) > 1000) {
        echo json_encode(['ok' => false]); exit;
    }
    $pdo->prepare("INSERT INTO messages (sender_id,receiver_id,content) VALUES (?,?,?)")
        ->execute([$me, $toId, $content]);
    echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} else {
    $withId = (int)($_GET['with']  ?? 0);
    $after  = (int)($_GET['after'] ?? 0);
    if (!$withId) { echo json_encode(['ok' => false]); exit; }
    $s = $pdo->prepare("SELECT id,sender_id,content,created_at FROM messages
        WHERE ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))
          AND id > ? ORDER BY id ASC LIMIT 80");
    $s->execute([$me, $withId, $withId, $me, $after]);
    $pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?")
        ->execute([$withId, $me]);
    echo json_encode(['ok' => true, 'messages' => $s->fetchAll(), 'me' => $me]);
}
