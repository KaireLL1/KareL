<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$postId = (int)($_GET['id'] ?? 0);
if (!$postId) { header('Location: index.php'); exit; }

// Only owner can delete
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id=? AND user_id=?");
$stmt->execute([$postId, $_SESSION['user_id']]);
$post = $stmt->fetch();

if ($post) {
    // Delete image file
    $filepath = __DIR__ . '/' . $post['image_path'];
    if (file_exists($filepath)) @unlink($filepath);

    // Delete DB record
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id=? AND user_id=?");
    $stmt->execute([$postId, $_SESSION['user_id']]);
}

header('Location: index.php?deleted=1');
exit;
?>
