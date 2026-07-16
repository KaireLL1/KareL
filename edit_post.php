<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$currentUser = getCurrentUser($pdo);

$postId = (int)($_GET['id'] ?? 0);
if (!$postId) { header('Location: index.php'); exit; }

// Fetch post — only owner
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id=? AND user_id=?");
$stmt->execute([$postId, $_SESSION['user_id']]);
$post = $stmt->fetch();
if (!$post) { header('Location: index.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = trim($_POST['caption'] ?? '');
    $stmt    = $pdo->prepare("UPDATE posts SET caption=? WHERE id=? AND user_id=?");
    $stmt->execute([$caption ?: null, $postId, $_SESSION['user_id']]);
    $success = 'Caption berhasil diperbarui!';
    $post['caption'] = $caption;
}

$pageTitle = 'Edit Post';
include 'includes/header.php';
?>

<div class="max-w-lg mx-auto px-4 pt-2">
  <div class="flex items-center gap-3 mb-5">
    <a href="index.php" id="back-edit-btn"
       class="w-9 h-9 rounded-full bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 flex items-center justify-center transition-colors">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-lg font-bold">Edit Post</h1>
  </div>

  <?php if ($success): ?>
  <div id="edit-success" class="bg-green-500/10 border border-green-500/30 text-green-400 rounded-2xl px-4 py-3 mb-4 text-sm flex items-center gap-2">
    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
  <?php endif; ?>

  <!-- Photo Preview -->
  <div class="rounded-3xl overflow-hidden bg-zinc-900 border border-zinc-800 mb-4">
    <img src="<?= htmlspecialchars($post['image_path']) ?>" class="w-full object-cover max-h-64" alt="Post photo">
    <p class="text-xs text-zinc-500 text-center py-2">Foto tidak dapat diubah</p>
  </div>

  <!-- Edit Form -->
  <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-5">
    <form method="POST" id="edit-form" class="space-y-4">
      <div>
        <label class="text-xs text-zinc-400 font-medium uppercase tracking-wide mb-1.5 block">Caption</label>
        <textarea id="edit-caption" name="caption" rows="3"
          class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 placeholder-zinc-500 resize-none focus:outline-none focus:border-amber-400 transition-colors text-sm"
          placeholder="Tulis caption..."><?= htmlspecialchars($post['caption'] ?? '') ?></textarea>
      </div>
      <div class="flex gap-3">
        <a href="index.php" id="cancel-edit-btn"
           class="flex-1 py-3 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-zinc-300 rounded-2xl text-sm font-medium transition-colors text-center">
          Batal
        </a>
        <button id="save-edit-btn" type="submit"
          class="flex-1 py-3 bg-amber-400 hover:bg-amber-300 text-zinc-950 rounded-2xl text-sm font-semibold transition-colors">
          Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
