<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$currentUser = getCurrentUser($pdo);
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.avatar
    FROM posts p JOIN users u ON p.user_id = u.id
    WHERE p.user_id = :uid
       OR p.user_id IN (
           SELECT CASE WHEN requester_id=:uid2 THEN receiver_id ELSE requester_id END
           FROM friendships
           WHERE (requester_id=:uid3 OR receiver_id=:uid4) AND status='accepted'
       )
    ORDER BY p.created_at DESC
");
$stmt->execute([':uid'=>$userId,':uid2'=>$userId,':uid3'=>$userId,':uid4'=>$userId]);
$posts = $stmt->fetchAll();

$pageTitle = 'Home';
include 'includes/header.php';
?>

<div style="max-width:480px;margin:0 auto;padding:16px 16px 0;">

  <!-- Header -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-top:8px;">
    <span style="font-size:22px;font-weight:900;letter-spacing:-1px;"><span style="color:#FFD60A;">Kare</span>L</span>
    <a href="camera.php" id="quick-cam-btn"
       style="width:40px;height:40px;background:#FFD60A;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;">
      <svg width="20" height="20" fill="none" stroke="#000" stroke-width="2.2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
      </svg>
    </a>
  </div>

  <?php if (empty($posts)): ?>
  <!-- Empty State -->
  <div id="empty-feed" style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;padding:32px 16px;">
    <div style="width:100px;height:100px;background:#111;border:1px solid #222;border-radius:32px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
      <svg width="52" height="52" fill="none" stroke="#333" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
      </svg>
    </div>
    <h2 style="font-size:20px;font-weight:800;margin-bottom:8px;">Belum ada momen</h2>
    <p style="color:#555;font-size:14px;line-height:1.6;margin-bottom:28px;">Tambah teman atau share<br>momen pertamamu!</p>
    <div style="display:flex;gap:12px;">
      <a href="friends.php" id="find-friends-cta"
         style="padding:12px 20px;background:#111;border:1.5px solid #222;color:#fff;border-radius:100px;font-size:14px;font-weight:700;text-decoration:none;">
        Cari Teman
      </a>
      <a href="camera.php" id="post-first-cta"
         style="padding:12px 20px;background:#FFD60A;color:#000;border-radius:100px;font-size:14px;font-weight:800;text-decoration:none;">
        📸 Post Momen
      </a>
    </div>
  </div>

  <?php else: ?>
  <!-- Feed -->
  <div id="posts-feed" style="display:flex;flex-direction:column;gap:16px;padding-bottom:8px;">
    <?php foreach ($posts as $i => $post): ?>
    <article id="post-<?= $post['id'] ?>"
      style="border-radius:28px;overflow:hidden;background:#111;position:relative;animation:fadeUp .4s ease <?= $i*0.05 ?>s both;">
      <!-- Photo -->
      <div style="position:relative;">
        <img src="<?= htmlspecialchars($post['image_path']) ?>"
             style="width:100%;aspect-ratio:1;object-fit:cover;display:block;" loading="lazy"
             alt="Momen dari <?= htmlspecialchars($post['username']) ?>">

        <!-- Gradient overlay -->
        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.65) 0%,transparent 45%);pointer-events:none;"></div>

        <!-- Author info (bottom-left overlay) -->
        <div style="position:absolute;bottom:14px;left:14px;display:flex;align-items:center;gap:10px;">
          <?php if (!empty($post['avatar'])): ?>
            <img src="<?= htmlspecialchars($post['avatar']) ?>"
                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.3);">
          <?php else: ?>
            <div style="width:36px;height:36px;border-radius:50%;background:#222;border:2px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;">
              <svg width="18" height="18" fill="#fff" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
          <?php endif; ?>
          <div>
            <p style="font-size:14px;font-weight:700;color:#fff;line-height:1.2;"><?= htmlspecialchars($post['username']) ?></p>
            <p style="font-size:11px;color:rgba(255,255,255,.55);"><?= timeAgo($post['created_at']) ?></p>
          </div>
        </div>

        <!-- Owner menu (top-right) -->
        <?php if ($post['user_id'] == $userId): ?>
        <div style="position:absolute;top:12px;right:12px;">
          <button id="menu-btn-<?= $post['id'] ?>" onclick="toggleMenu(<?= $post['id'] ?>)"
            style="width:34px;height:34px;background:rgba(0,0,0,.5);backdrop-filter:blur(10px);border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
          </button>
          <div id="menu-<?= $post['id'] ?>"
            style="display:none;position:absolute;right:0;top:42px;background:#1a1a1a;border:1px solid #2a2a2a;border-radius:16px;overflow:hidden;min-width:150px;z-index:20;box-shadow:0 8px 32px rgba(0,0,0,.6);">
            <a href="edit_post.php?id=<?= $post['id'] ?>" id="edit-<?= $post['id'] ?>"
               style="display:flex;align-items:center;gap:10px;padding:13px 16px;color:#fff;text-decoration:none;font-size:14px;font-weight:600;">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              Edit Caption
            </a>
            <button onclick="confirmDelete(<?= $post['id'] ?>)" id="del-<?= $post['id'] ?>"
               style="display:flex;align-items:center;gap:10px;padding:13px 16px;color:#ef4444;background:none;border:none;cursor:pointer;font-size:14px;font-weight:600;width:100%;">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              Hapus
            </button>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Caption -->
      <?php if (!empty($post['caption'])): ?>
      <div style="padding:12px 16px 14px;">
        <p style="font-size:14px;color:#ccc;line-height:1.5;">
          <span style="font-weight:700;color:#fff;"><?= htmlspecialchars($post['username']) ?> </span>
          <?= htmlspecialchars($post['caption']) ?>
        </p>
      </div>
      <?php else: ?>
      <div style="height:8px;"></div>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Delete Modal -->
<div id="delete-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:flex-end;justify-content:center;padding:0 16px 20px;">
  <div onclick="closeDeleteModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);"></div>
  <div style="position:relative;background:#111;border:1px solid #222;border-radius:28px;padding:24px;width:100%;max-width:400px;">
    <h3 style="font-size:18px;font-weight:800;margin-bottom:6px;">Hapus Momen?</h3>
    <p style="color:#555;font-size:14px;margin-bottom:20px;">Foto ini akan dihapus permanen.</p>
    <div style="display:flex;gap:10px;">
      <button id="cancel-delete-btn" onclick="closeDeleteModal()"
        style="flex:1;padding:14px;background:#1a1a1a;border:1.5px solid #2a2a2a;color:#aaa;border-radius:100px;font-size:15px;font-weight:700;cursor:pointer;">
        Batal
      </button>
      <a id="confirm-delete-link" href="#"
        style="flex:1;padding:14px;background:#ef4444;color:#fff;border-radius:100px;font-size:15px;font-weight:700;text-align:center;text-decoration:none;">
        Hapus
      </a>
    </div>
  </div>
</div>

<style>
@keyframes fadeUp { from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);} }
</style>
<script>
function toggleMenu(id) {
  document.querySelectorAll('[id^="menu-"]').forEach(m => { if(m.id!=='menu-'+id) m.style.display='none'; });
  const m = document.getElementById('menu-'+id);
  m.style.display = m.style.display==='none'||!m.style.display ? 'block' : 'none';
}
document.addEventListener('click', e => {
  if (!e.target.closest('[id^="menu-btn-"]') && !e.target.closest('[id^="menu-"]'))
    document.querySelectorAll('[id^="menu-"]').forEach(m => m.style.display='none');
});
function confirmDelete(id) {
  document.getElementById('confirm-delete-link').href = 'delete_post.php?id='+id;
  document.getElementById('delete-modal').style.display = 'flex';
}
function closeDeleteModal() { document.getElementById('delete-modal').style.display='none'; }
</script>

<?php include 'includes/footer.php'; ?>
