<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$postId  = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content']  ?? '');
$me      = $_SESSION['user_id'];

if (!$postId || !$content || mb_strlen($content) > 300) { echo json_encode(['ok'=>false]); exit; }

$pdo->prepare("INSERT INTO comments (post_id,user_id,content) VALUES (?,?,?)")->execute([$postId,$me,$content]);
$cid = $pdo->lastInsertId();

$s = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id=u.id WHERE c.id=?");
$s->execute([$cid]);
$c = $s->fetch();

echo json_encode(['ok'=>true,'comment'=>['username'=>$c['username'],'content'=>$c['content']]]);
