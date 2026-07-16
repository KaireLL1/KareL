<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$currentUser = getCurrentUser($pdo);
$me = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$me]);
$myPosts = $stmt->fetchAll();

$totalRx = 0; $totalCmt = 0;
try {
    $s = $pdo->prepare("SELECT COUNT(*) FROM reactions r JOIN posts p ON r.post_id=p.id WHERE p.user_id=?");
    $s->execute([$me]); $totalRx = (int)$s->fetchColumn();
    $s = $pdo->prepare("SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id=p.id WHERE p.user_id=?");
    $s->execute([$me]); $totalCmt = (int)$s->fetchColumn();
} catch(Exception $e) {}

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<style>
.db-wrap { max-width: 600px; margin: 0 auto; padding: 20px 14px 100px; }

/* Stats grid */
.stat-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 22px; }
.stat-card {
  background: #111; border: 1px solid #1e1e1e; border-radius: 20px;
  padding: 16px 12px; text-align: center;
}
.stat-num { font-size: 30px; font-weight: 900; color: #FFD60A; line-height: 1; }
.stat-lbl { font-size: 11px; color: #555; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }

/* CRUD badges */
.crud-row { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 22px; }
.crud-badge {
  border-radius: 100px; padding: 5px 12px; font-size: 12px; font-weight: 700;
  display: flex; align-items: center; gap: 5px; border-width: 1px; border-style: solid;
}

/* Post rows */
.post-row {
  background: #111; border: 1px solid #1e1e1e; border-radius: 20px;
  overflow: hidden; display: flex; gap: 0;
  animation: fadeUp .3s ease var(--delay, 0s) both;
  transition: border-color .2s;
}
.post-row:hover { border-color: #2a2a2a; }
.post-thumb { width: 88px; height: 88px; object-fit: cover; display: block; flex-shrink: 0; }
.post-info { flex: 1; padding: 12px 14px; display: flex; flex-direction: column; justify-content: space-between; min-width: 0; }
.post-caption { font-size: 13px; color: #ccc; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.post-meta { font-size: 11px; color: #444; margin-top: 4px; }
.post-actions { display: flex; gap: 7px; margin-top: 8px; }

.btn-edit {
  display: flex; align-items: center; gap: 5px;
  padding: 6px 13px; background: rgba(255,214,10,.08);
  border: 1.5px solid rgba(255,214,10,.2); color: #FFD60A;
  border-radius: 100px; font-size: 12px; font-weight: 700; text-decoration: none;
  transition: background .15s;
}
.btn-edit:active { background: rgba(255,214,10,.18); }

.btn-del {
  display: flex; align-items: center; gap: 5px;
  padding: 6px 13px; background: rgba(239,68,68,.08);
  border: 1.5px solid rgba(239,68,68,.2); color: #ef4444;
  border-radius: 100px; font-size: 12px; font-weight: 700; cursor: pointer;
  transition: background .15s;
}
.btn-del:active { background: rgba(239,68,68,.18); }

@keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

<div class="db-wrap">

  <!-- Header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;gap:12px;">
    <div>
      <h1 style="font-size:24px;font-weight:900;letter-spacing:-.5px;">Dashboard</h1>
      <p style="color:#555;font-size:13px;margin-top:3px;">Kelola semua postinganmu</p>
    </div>
    <a href="camera.php" id="btn-create"
       style="display:flex;align-items:center;gap:7px;background:#FFD60A;color:#000;padding:11px 18px;border-radius:100px;font-size:13px;font-weight:800;text-decoration:none;white-space:nowrap;flex-shrink:0;">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Buat Post
    </a>
  </div>

  <!-- Stats -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-num"><?= count($myPosts) ?></div>
      <div class="stat-lbl">Post</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $totalRx ?></div>
      <div class="stat-lbl">Reaksi</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $totalCmt ?></div>
      <div class="stat-lbl">Komentar</div>
    </div>
  </div>

  <!-- Divider -->
  <div style="border-top:1px solid #1a1a1a;margin-bottom:16px;"></div>

  <!-- Post list -->
  <?php if (empty($myPosts)): ?>
  <div style="text-align:center;padding:60px 20px;background:#111;border:1px solid #1e1e1e;border-radius:24px;">
    <div style="width:52px;height:52px;border-radius:50%;background:#1a1a1a;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
      <svg width="22" height="22" fill="none" stroke="#333" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/></svg>
    </div>
    <p style="color:#444;font-size:15px;font-weight:700;margin-bottom:6px;">Belum ada postingan</p>
    <p style="color:#333;font-size:13px;margin-bottom:18px;">Share momen pertamamu!</p>
    <a href="camera.php" style="background:#FFD60A;color:#000;padding:12px 24px;border-radius:100px;font-size:14px;font-weight:800;text-decoration:none;">Buat Post Pertama</a>
  </div>

  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:10px;">
    <?php foreach ($myPosts as $i => $p):
      $rxCount = 0; $cmtCount = 0;
      try {
        $s4=$pdo->prepare("SELECT COUNT(*) FROM reactions WHERE post_id=?"); $s4->execute([$p['id']]); $rxCount=(int)$s4->fetchColumn();
        $s5=$pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id=?");  $s5->execute([$p['id']]); $cmtCount=(int)$s5->fetchColumn();
      } catch(Exception $e) {}
    ?>
    <div class="post-row" id="row-<?= $p['id'] ?>" style="--delay:<?= $i*0.04 ?>s">
      <a href="<?= htmlspecialchars($p['image_path']) ?>" target="_blank" style="flex-shrink:0;">
        <img src="<?= htmlspecialchars($p['image_path']) ?>" class="post-thumb" alt="post">
      </a>
      <div class="post-info">
        <div>
          <p class="post-caption"><?= !empty($p['caption']) ? htmlspecialchars($p['caption']) : '<span style="color:#333;">Tidak ada caption</span>' ?></p>
          <p class="post-meta">
            <?= timeAgo($p['created_at']) ?>
            <span style="color:#2a2a2a;"> &nbsp;·&nbsp; </span>
            <span style="color:#555;"><?= $rxCount ?> reaksi</span>
            <span style="color:#2a2a2a;"> · </span>
            <span style="color:#555;"><?= $cmtCount ?> komentar</span>
          </p>
        </div>
        <div class="post-actions">
          <a href="edit_post.php?id=<?= $p['id'] ?>" id="edit-<?= $p['id'] ?>" class="btn-edit">
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            Edit
          </a>
          <button onclick="delPost(<?= $p['id'] ?>)" id="del-<?= $p['id'] ?>" class="btn-del">
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Hapus
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<!-- Delete Modal -->
<div id="del-modal" style="display:none;position:fixed;inset:0;z-index:100;align-items:flex-end;justify-content:center;padding:0 16px 24px;">
  <div onclick="closeDelModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(8px);"></div>
  <div style="position:relative;background:#111;border:1px solid #222;border-radius:28px;padding:24px;width:100%;max-width:400px;">
    <h3 style="font-size:18px;font-weight:800;margin-bottom:6px;">Hapus Post?</h3>
    <p style="color:#555;font-size:13px;margin-bottom:20px;">Post akan dihapus permanen dan tidak bisa dikembalikan.</p>
    <div style="display:flex;gap:10px;">
      <button onclick="closeDelModal()" style="flex:1;padding:14px;background:#1a1a1a;border:1.5px solid #2a2a2a;color:#aaa;border-radius:100px;font-size:14px;font-weight:700;cursor:pointer;">Batal</button>
      <a id="del-confirm-link" href="#" style="flex:1;padding:14px;background:#ef4444;color:#fff;border-radius:100px;font-size:14px;font-weight:700;text-align:center;text-decoration:none;">Hapus</a>
    </div>
  </div>
</div>

<script>
function delPost(id){document.getElementById('del-confirm-link').href='delete_post.php?id='+id;document.getElementById('del-modal').style.display='flex';}
function closeDelModal(){document.getElementById('del-modal').style.display='none';}
</script>

<?php include 'includes/footer.php'; ?>
