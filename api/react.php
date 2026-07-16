<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$postId  = (int)($_POST['post_id']   ?? 0);
$emojiIdx = (int)($_POST['emoji_idx'] ?? -1);
$me      = $_SESSION['user_id'];
$emojis  = ["\u{2764}\u{FE0F}", "\u{1F525}", "\u{1F970}", "\u{1F60A}"];

if (!$postId || !isset($emojis[$emojiIdx])) { echo json_encode(['ok'=>false]); exit; }
$emoji = $emojis[$emojiIdx];

$s = $pdo->prepare("SELECT id, emoji FROM reactions WHERE post_id=? AND user_id=?");
$s->execute([$postId, $me]);
$existing = $s->fetch();

$myIdx = null;
if ($existing) {
    if ($existing['emoji'] === $emoji) {
        $pdo->prepare("DELETE FROM reactions WHERE id=?")->execute([$existing['id']]);
    } else {
        $pdo->prepare("UPDATE reactions SET emoji=? WHERE id=?")->execute([$emoji, $existing['id']]);
        $myIdx = $emojiIdx;
    }
} else {
    $pdo->prepare("INSERT INTO reactions (post_id,user_id,emoji) VALUES (?,?,?)")->execute([$postId,$me,$emoji]);
    $myIdx = $emojiIdx;
}

$s = $pdo->prepare("SELECT emoji, COUNT(*) as cnt FROM reactions WHERE post_id=? GROUP BY emoji");
$s->execute([$postId]);
$counts = [];
foreach ($s->fetchAll() as $r) {
    $idx = array_search($r['emoji'], $emojis);
    if ($idx !== false) $counts[$idx] = (int)$r['cnt'];
}

echo json_encode(['ok'=>true, 'counts'=>$counts, 'my_idx'=>$myIdx]);
