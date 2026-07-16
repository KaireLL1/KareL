<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$currentUser = getCurrentUser($pdo);

$profileId = (int)($_GET['id'] ?? $_SESSION['user_id']);
$isOwn     = $profileId === $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$profileId]);
$profileUser = $stmt->fetch();
if (!$profileUser) { header('Location: index.php'); exit; }

$error = $success = '';
if ($isOwn && $_SERVER['REQUEST_METHOD']==='POST') {
    $username = trim($_POST['username'] ?? '');
    $bio      = trim($_POST['bio']      ?? '');
    $avatar   = $profileUser['avatar'];
    if (!$username) { $error = 'Username wajib diisi!'; }
    else {
        $s = $pdo->prepare("SELECT id FROM users WHERE username=? AND id!=?");
        $s->execute([$username,$_SESSION['user_id']]);
        if ($s->fetch()) { $error = 'Username sudah dipakai!'; }
        else {
            if (!empty($_POST['avatar_data'])) {
                $d = preg_replace('/^data:image\/\w+;base64,/','',$_POST['avatar_data']);
                $b = base64_decode($d);
                if ($b) {
                    $dir = __DIR__.'/uploads/avatars/';
                    if (!is_dir($dir)) mkdir($dir,0755,true);
                    $fn = 'avatar_'.$_SESSION['user_id'].'_'.time().'.jpg';
                    file_put_contents($dir.$fn,$b);
                    $avatar = 'uploads/avatars/'.$fn;
                }
            }
            $pdo->prepare("UPDATE users SET username=?,bio=?,avatar=? WHERE id=?")->execute([$username,$bio?:null,$avatar,$_SESSION['user_id']]);
            $_SESSION['username'] = $username;
            $success = 'Profil diperbarui!';
            $profileUser = array_merge($profileUser,['username'=>$username,'bio'=>$bio,'avatar'=>$avatar]);
            $currentUser = $profileUser;
        }
    }
}

// Friend actions
if (!$isOwn && isset($_GET['action'])) {
    $me = $_SESSION['user_id'];
    switch($_GET['action']) {
        case 'add':
            try { $pdo->prepare("INSERT INTO friendships(requester_id,receiver_id)VALUES(?,?)")->execute([$me,$profileId]); } catch(Exception $e){}
            break;
        case 'accept':
            $pdo->prepare("UPDATE friendships SET status='accepted' WHERE requester_id=? AND receiver_id=?")->execute([$profileId,$me]);
            break;
        case 'remove':
            $pdo->prepare("DELETE FROM friendships WHERE (requester_id=? AND receiver_id=?) OR (requester_id=? AND receiver_id=?)")->execute([$me,$profileId,$profileId,$me]);
            break;
    }
    header("Location: profile.php?id=$profileId"); exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$profileId]);
$userPosts = $stmt->fetchAll();
$friendStatus = !$isOwn ? getFriendshipStatus($pdo,$_SESSION['user_id'],$profileId) : null;

$pageTitle = $profileUser['username'];
include 'includes/header.php';
?>

<div style="max-width:480px;margin:0 auto;padding:0;">

  <?php if (!$isOwn): ?>
  <!-- Back button for viewing others' profiles -->
  <div style="padding:16px 16px 0;">
    <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:6px;color:#555;font-size:14px;font-weight:600;text-decoration:none;">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Kembali
    </a>
  </div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div style="margin:12px 16px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#4ade80;border-radius:14px;padding:12px 16px;font-size:14px;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div style="margin:12px 16px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;border-radius:14px;padding:12px 16px;font-size:14px;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Profile Header -->
  <div style="padding:20px 16px 16px;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
      <!-- Avatar -->
      <div style="position:relative;flex-shrink:0;">
        <?php if (!empty($profileUser['avatar'])): ?>
          <img id="avatar-display" src="<?= htmlspecialchars($profileUser['avatar']) ?>"
               style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #FFD60A;">
        <?php else: ?>
          <div id="avatar-display" style="width:80px;height:80px;border-radius:50%;background:#1a1a1a;border:3px solid #333;display:flex;align-items:center;justify-content:center;">
            <svg width="40" height="40" fill="#444" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
          </div>
        <?php endif; ?>
        <?php if ($isOwn): ?>
        <button id="change-avatar-btn" onclick="openAvatarModal()"
          style="position:absolute;bottom:-2px;right:-2px;width:26px;height:26px;background:#FFD60A;border:2.5px solid #000;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;">
          <svg width="13" height="13" fill="none" stroke="#000" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
        </button>
        <?php endif; ?>
      </div>
      <!-- Info -->
      <div style="flex:1;min-width:0;">
        <h2 style="font-size:20px;font-weight:900;letter-spacing:-.5px;"><?= htmlspecialchars($profileUser['username']) ?></h2>
        <?php if (!empty($profileUser['bio'])): ?>
          <p style="color:#666;font-size:13px;margin-top:4px;line-height:1.4;"><?= htmlspecialchars($profileUser['bio']) ?></p>
        <?php endif; ?>
        <p style="color:#333;font-size:12px;margin-top:4px;font-weight:600;"><?= count($userPosts) ?> momen</p>
      </div>
    </div>

    <!-- Action buttons -->
    <?php if ($isOwn): ?>
    <div style="display:flex;gap:10px;">
      <button id="toggle-edit-btn" onclick="document.getElementById('edit-section').style.display=document.getElementById('edit-section').style.display==='none'?'block':'none'"
        style="flex:1;padding:11px;background:#111;border:1.5px solid #222;color:#fff;border-radius:100px;font-size:14px;font-weight:700;cursor:pointer;">
        Edit Profil
      </button>
      <a href="logout.php" id="logout-btn"
        style="padding:11px 18px;background:#111;border:1.5px solid #2a2a2a;color:#ef4444;border-radius:100px;font-size:14px;font-weight:700;text-decoration:none;">
        Keluar
      </a>
    </div>

    <!-- Edit form -->
    <div id="edit-section" style="display:none;margin-top:16px;background:#111;border:1px solid #1a1a1a;border-radius:20px;padding:16px;">
      <form method="POST" id="edit-profile-form">
        <input type="hidden" id="avatar-data-input" name="avatar_data">
        <div style="margin-bottom:10px;">
          <label style="font-size:11px;color:#555;font-weight:700;text-transform:uppercase;letter-spacing:.6px;display:block;margin-bottom:6px;">Username</label>
          <input id="edit-username" type="text" name="username" required value="<?= htmlspecialchars($profileUser['username']) ?>"
            style="width:100%;background:#000;border:1.5px solid #222;border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;"
            onfocus="this.style.borderColor='#FFD60A'" onblur="this.style.borderColor='#222'">
        </div>
        <div style="margin-bottom:14px;">
          <label style="font-size:11px;color:#555;font-weight:700;text-transform:uppercase;letter-spacing:.6px;display:block;margin-bottom:6px;">Bio</label>
          <textarea id="edit-bio" name="bio" rows="2"
            style="width:100%;background:#000;border:1.5px solid #222;border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;resize:none;"
            placeholder="Ceritain dirimu..."
            onfocus="this.style.borderColor='#FFD60A'" onblur="this.style.borderColor='#222'"><?= htmlspecialchars($profileUser['bio']??'') ?></textarea>
        </div>
        <button id="save-profile-btn" type="submit"
          style="width:100%;background:#FFD60A;color:#000;border:none;border-radius:100px;padding:13px;font-size:15px;font-weight:800;cursor:pointer;">
          Simpan
        </button>
      </form>
    </div>

    <?php else: ?>
    <!-- Friend actions for others' profiles -->
    <div style="display:flex;gap:10px;">
      <?php if (!$friendStatus): ?>
        <a href="profile.php?id=<?= $profileId ?>&action=add" id="add-friend-btn"
           style="flex:1;padding:11px;background:#FFD60A;color:#000;border-radius:100px;font-size:14px;font-weight:800;text-align:center;text-decoration:none;">
          + Tambah Teman
        </a>
      <?php elseif ($friendStatus['status']==='pending' && $friendStatus['requester_id']==$_SESSION['user_id']): ?>
        <span style="flex:1;padding:11px;background:#111;border:1.5px solid #222;color:#555;border-radius:100px;font-size:14px;font-weight:700;text-align:center;">Request Terkirim</span>
        <a href="profile.php?id=<?= $profileId ?>&action=remove" style="padding:11px 16px;background:#111;border:1.5px solid #2a2a2a;color:#ef4444;border-radius:100px;font-size:14px;font-weight:700;text-decoration:none;">Batal</a>
      <?php elseif ($friendStatus['status']==='pending'): ?>
        <a href="profile.php?id=<?= $profileId ?>&action=accept" id="accept-btn"
           style="flex:1;padding:11px;background:#FFD60A;color:#000;border-radius:100px;font-size:14px;font-weight:800;text-align:center;text-decoration:none;">
          Terima Request
        </a>
      <?php elseif ($friendStatus['status']==='accepted'): ?>
        <span style="flex:1;padding:11px;background:rgba(34,197,94,.08);border:1.5px solid rgba(34,197,94,.2);color:#4ade80;border-radius:100px;font-size:14px;font-weight:700;text-align:center;">✓ Berteman</span>
        <a href="profile.php?id=<?= $profileId ?>&action=remove" style="padding:11px 16px;background:#111;border:1.5px solid #2a2a2a;color:#ef4444;border-radius:100px;font-size:14px;font-weight:700;text-decoration:none;">Hapus</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Posts Grid -->
  <?php if (!empty($userPosts)): ?>
  <div id="posts-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:2px;padding:0 0 8px;">
    <?php foreach ($userPosts as $p): ?>
    <a href="<?= $isOwn?'edit_post.php?id='.$p['id']:'#' ?>" id="gp-<?= $p['id'] ?>"
       style="aspect-ratio:1;overflow:hidden;background:#111;display:block;text-decoration:none;">
      <img src="<?= htmlspecialchars($p['image_path']) ?>"
           style="width:100%;height:100%;object-fit:cover;transition:opacity .2s;" loading="lazy"
           onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
    </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="padding:40px 16px;text-align:center;color:#333;font-size:14px;">Belum ada momen</div>
  <?php endif; ?>
</div>

<!-- Avatar Camera Modal -->
<?php if ($isOwn): ?>
<div id="avatar-modal" style="display:none;position:fixed;inset:0;z-index:100;">
  <div onclick="closeAvatarModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.85);backdrop-filter:blur(8px);"></div>
  <div style="position:absolute;bottom:0;left:0;right:0;background:#0d0d0d;border:1px solid #1a1a1a;border-radius:28px 28px 0 0;padding:20px 20px max(env(safe-area-inset-bottom),20px);">
    <div style="width:36px;height:4px;background:#222;border-radius:4px;margin:0 auto 16px;"></div>
    <p style="font-size:16px;font-weight:800;margin-bottom:12px;">Foto Profil</p>
    <div style="border-radius:20px;overflow:hidden;background:#111;aspect-ratio:1;position:relative;margin-bottom:12px;">
      <video id="av-video" autoplay playsinline muted style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"></video>
      <img id="av-preview" style="display:none;position:absolute;inset:0;width:100%;height:100%;object-fit:cover;" src="">
    </div>
    <canvas id="av-canvas" style="display:none;"></canvas>
    <div style="display:flex;gap:10px;">
      <button id="av-retake-btn" onclick="avRetake()" style="display:none;flex:1;padding:13px;background:#111;border:1.5px solid #222;color:#fff;border-radius:100px;font-size:14px;font-weight:700;cursor:pointer;">Ulangi</button>
      <button id="av-capture-btn" onclick="avCapture()" style="flex:1;padding:13px;background:#FFD60A;color:#000;border:none;border-radius:100px;font-size:14px;font-weight:800;cursor:pointer;">📸 Foto</button>
      <button id="av-save-btn" onclick="avSave()" style="display:none;flex:1;padding:13px;background:#22c55e;color:#fff;border:none;border-radius:100px;font-size:14px;font-weight:800;cursor:pointer;">Gunakan</button>
    </div>
  </div>
</div>

<script>
let avStream = null;
function openAvatarModal() {
  document.getElementById('avatar-modal').style.display='block';
  navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false})
    .then(s=>{ avStream=s; document.getElementById('av-video').srcObject=s; });
}
function closeAvatarModal() {
  document.getElementById('avatar-modal').style.display='none';
  if(avStream) avStream.getTracks().forEach(t=>t.stop());
  avStream=null;
  document.getElementById('av-video').srcObject=null;
  document.getElementById('av-preview').style.display='none';
  document.getElementById('av-video').style.display='block';
  document.getElementById('av-capture-btn').style.display='block';
  document.getElementById('av-save-btn').style.display='none';
  document.getElementById('av-retake-btn').style.display='none';
}
function avCapture() {
  const v=document.getElementById('av-video'), c=document.getElementById('av-canvas');
  c.width=v.videoWidth; c.height=v.videoHeight;
  const ctx=c.getContext('2d'); ctx.translate(c.width,0); ctx.scale(-1,1); ctx.drawImage(v,0,0);
  const d=c.toDataURL('image/jpeg',.8);
  document.getElementById('av-preview').src=d;
  document.getElementById('av-preview').style.display='block';
  document.getElementById('av-video').style.display='none';
  document.getElementById('av-capture-btn').style.display='none';
  document.getElementById('av-save-btn').style.display='block';
  document.getElementById('av-retake-btn').style.display='block';
}
function avRetake() {
  document.getElementById('av-preview').style.display='none';
  document.getElementById('av-video').style.display='block';
  document.getElementById('av-capture-btn').style.display='block';
  document.getElementById('av-save-btn').style.display='none';
  document.getElementById('av-retake-btn').style.display='none';
}
function avSave() {
  document.getElementById('avatar-data-input').value = document.getElementById('av-preview').src;
  document.getElementById('edit-section').style.display='block';
  closeAvatarModal();
  document.getElementById('edit-profile-form').submit();
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
