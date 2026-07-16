<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$currentUser = getCurrentUser($pdo);
$me = $_SESSION['user_id'];

if (isset($_GET['action'], $_GET['user_id'])) {
    $tid = (int)$_GET['user_id'];
    switch ($_GET['action']) {
        case 'add':
            try { $pdo->prepare("INSERT INTO friendships (requester_id,receiver_id) VALUES (?,?)")->execute([$me,$tid]); } catch(Exception $e){}
            break;
        case 'accept':
            $pdo->prepare("UPDATE friendships SET status='accepted' WHERE requester_id=? AND receiver_id=?")->execute([$tid,$me]);
            break;
        case 'reject':
            $pdo->prepare("UPDATE friendships SET status='rejected' WHERE requester_id=? AND receiver_id=?")->execute([$tid,$me]);
            break;
        case 'remove':
            $pdo->prepare("DELETE FROM friendships WHERE (requester_id=? AND receiver_id=?) OR (requester_id=? AND receiver_id=?)")->execute([$me,$tid,$tid,$me]);
            break;
    }
    header('Location: friends.php'); exit;
}

$search = trim($_GET['q'] ?? '');
$searchResults = [];
if ($search) {
    $s = $pdo->prepare("SELECT id,username,avatar FROM users WHERE (username LIKE ? OR email LIKE ?) AND id!=? LIMIT 20");
    $s->execute(["%$search%","%$search%",$me]);
    $searchResults = $s->fetchAll();
}
$fsMap = [];
foreach ($searchResults as $r) { $fsMap[$r['id']] = getFriendshipStatus($pdo,$me,$r['id']); }

$stmt = $pdo->prepare("SELECT u.id,u.username,u.avatar,f.created_at FROM friendships f JOIN users u ON f.requester_id=u.id WHERE f.receiver_id=? AND f.status='pending' ORDER BY f.created_at DESC");
$stmt->execute([$me]);
$pending = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT u.id,u.username,u.avatar FROM friendships f JOIN users u ON (CASE WHEN f.requester_id=? THEN f.receiver_id ELSE f.requester_id END=u.id) WHERE (f.requester_id=? OR f.receiver_id=?) AND f.status='accepted' ORDER BY u.username");
$stmt->execute([$me,$me,$me]);
$friends = $stmt->fetchAll();

$pageTitle = 'Teman';
include 'includes/header.php';
?>

<style>
.fr-wrap { max-width: 480px; margin: 0 auto; padding: 20px 14px 100px; }

.fr-search {
  display: flex; gap: 10px; margin-bottom: 24px;
}
.fr-input {
  flex: 1; background: #111; border: 1.5px solid #222;
  border-radius: 14px; padding: 13px 16px; color: #fff;
  font-size: 16px; transition: border-color .2s; outline: none;
}
.fr-input:focus { border-color: #FFD60A; }
.fr-input::placeholder { color: #444; }
.fr-search-btn {
  background: #FFD60A; color: #000; border: none;
  border-radius: 14px; padding: 0 20px;
  font-size: 14px; font-weight: 800; cursor: pointer;
  min-width: 60px; white-space: nowrap;
}

.section-label {
  font-size: 11px; font-weight: 700; color: #555;
  text-transform: uppercase; letter-spacing: .8px;
  margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
}
.badge-count {
  background: #FFD60A; color: #000;
  border-radius: 100px; padding: 2px 8px;
  font-size: 11px; font-weight: 800;
}

/* User card */
.user-card {
  display: flex; align-items: center; gap: 12px;
  background: #111; border: 1px solid #1a1a1a;
  border-radius: 20px; padding: 12px 14px;
  transition: border-color .15s;
}
.user-card:active { border-color: #2a2a2a; }

.user-avatar {
  width: 46px; height: 46px; border-radius: 50%;
  object-fit: cover; border: 2px solid #1e1e1e; flex-shrink: 0;
}
.user-avatar-placeholder {
  width: 46px; height: 46px; border-radius: 50%;
  background: #1a1a1a; border: 2px solid #1e1e1e;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.user-name { font-size: 15px; font-weight: 700; color: #fff; }

/* Action buttons */
.btn-add {
  background: #FFD60A; color: #000;
  border-radius: 100px; padding: 9px 18px;
  font-size: 13px; font-weight: 800; text-decoration: none;
  white-space: nowrap; display: inline-block; flex-shrink: 0;
}
.btn-sent {
  background: #1a1a1a; color: #555;
  border-radius: 100px; padding: 9px 14px;
  font-size: 13px; font-weight: 700; white-space: nowrap; flex-shrink: 0;
}
.btn-friend {
  background: rgba(34,197,94,.1); color: #4ade80;
  border: 1px solid rgba(34,197,94,.2);
  border-radius: 100px; padding: 9px 14px;
  font-size: 13px; font-weight: 700; white-space: nowrap; flex-shrink: 0;
}
.btn-icon {
  width: 40px; height: 40px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  text-decoration: none; flex-shrink: 0; border: 1.5px solid #2a2a2a;
}
.btn-accept { background: #FFD60A; border-color: #FFD60A; }
.btn-reject { background: #1a1a1a; }
.btn-remove { background: #1a1a1a; }

/* Pending card highlight */
.pending-card {
  border-color: rgba(255,214,10,.15) !important;
}

.empty-box {
  background: #111; border: 1px solid #1a1a1a;
  border-radius: 20px; padding: 32px 20px; text-align: center;
}
</style>

<div class="fr-wrap">

  <h1 style="font-size:22px;font-weight:900;letter-spacing:-.5px;margin-bottom:20px;">Teman</h1>

  <!-- Search -->
  <form method="GET" class="fr-search">
    <input class="fr-input" type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari username atau email...">
    <button class="fr-search-btn" type="submit">Cari</button>
  </form>

  <!-- Search Results -->
  <?php if ($search): ?>
  <section style="margin-bottom:28px;">
    <p class="section-label">Hasil Pencarian</p>
    <?php if (empty($searchResults)): ?>
      <div class="empty-box">
        <p style="color:#555;font-size:14px;">Tidak ada user <b style="color:#888;">"<?= htmlspecialchars($search) ?>"</b></p>
      </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($searchResults as $u):
        $fs = $fsMap[$u['id']] ?? null;
      ?>
      <div class="user-card" id="sr-<?= $u['id'] ?>">
        <a href="profile.php?id=<?= $u['id'] ?>" style="text-decoration:none;flex-shrink:0;">
          <?php if (!empty($u['avatar'])): ?>
            <img src="<?= htmlspecialchars($u['avatar']) ?>" class="user-avatar">
          <?php else: ?>
            <div class="user-avatar-placeholder">
              <svg width="22" height="22" fill="#555" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
          <?php endif; ?>
        </a>
        <a href="profile.php?id=<?= $u['id'] ?>" style="flex:1;min-width:0;text-decoration:none;">
          <p class="user-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($u['username']) ?></p>
        </a>
        <?php if (!$fs): ?>
          <a href="friends.php?action=add&user_id=<?= $u['id'] ?>&q=<?= urlencode($search) ?>" class="btn-add" id="add-<?= $u['id'] ?>">+ Tambah</a>
        <?php elseif ($fs['status']==='accepted'): ?>
          <span class="btn-friend">Teman</span>
        <?php elseif ($fs['status']==='pending' && $fs['requester_id']==$me): ?>
          <span class="btn-sent">Terkirim</span>
        <?php elseif ($fs['status']==='pending'): ?>
          <a href="friends.php?action=accept&user_id=<?= $u['id'] ?>&q=<?= urlencode($search) ?>" class="btn-add" id="acc-<?= $u['id'] ?>">Terima</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- Pending Requests -->
  <?php if (!empty($pending)): ?>
  <section style="margin-bottom:28px;">
    <p class="section-label">
      Permintaan Masuk
      <span class="badge-count"><?= count($pending) ?></span>
    </p>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($pending as $r): ?>
      <div class="user-card pending-card" id="pend-<?= $r['id'] ?>">
        <a href="profile.php?id=<?= $r['id'] ?>" style="text-decoration:none;flex-shrink:0;">
          <?php if (!empty($r['avatar'])): ?>
            <img src="<?= htmlspecialchars($r['avatar']) ?>" class="user-avatar">
          <?php else: ?>
            <div class="user-avatar-placeholder">
              <svg width="22" height="22" fill="#555" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
          <?php endif; ?>
        </a>
        <div style="flex:1;min-width:0;">
          <p class="user-name"><?= htmlspecialchars($r['username']) ?></p>
          <p style="font-size:12px;color:#555;margin-top:2px;"><?= timeAgo($r['created_at']) ?></p>
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0;">
          <a href="friends.php?action=reject&user_id=<?= $r['id'] ?>" class="btn-icon btn-reject" id="rej-<?= $r['id'] ?>">
            <svg width="16" height="16" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </a>
          <a href="friends.php?action=accept&user_id=<?= $r['id'] ?>" class="btn-icon btn-accept" id="acc-req-<?= $r['id'] ?>">
            <svg width="16" height="16" fill="none" stroke="#000" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Friends List -->
  <section>
    <p class="section-label">Teman (<?= count($friends) ?>)</p>
    <?php if (empty($friends)): ?>
    <div class="empty-box">
      <svg width="36" height="36" fill="none" stroke="#333" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
      <p style="color:#555;font-size:14px;">Cari teman di atas</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($friends as $f): ?>
      <div class="user-card" id="fr-<?= $f['id'] ?>">
        <a href="profile.php?id=<?= $f['id'] ?>" style="text-decoration:none;flex-shrink:0;">
          <?php if (!empty($f['avatar'])): ?>
            <img src="<?= htmlspecialchars($f['avatar']) ?>" class="user-avatar">
          <?php else: ?>
            <div class="user-avatar-placeholder">
              <svg width="22" height="22" fill="#555" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
          <?php endif; ?>
        </a>
        <a href="profile.php?id=<?= $f['id'] ?>" style="flex:1;min-width:0;text-decoration:none;">
          <p class="user-name"><?= htmlspecialchars($f['username']) ?></p>
        </a>
        <div style="display:flex;gap:8px;flex-shrink:0;">
          <a href="chat.php?with=<?= $f['id'] ?>" class="btn-icon" style="background:rgba(255,214,10,.1);border-color:rgba(255,214,10,.2);" title="Chat">
            <svg width="16" height="16" fill="none" stroke="#FFD60A" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
          </a>
          <a href="friends.php?action=remove&user_id=<?= $f['id'] ?>"
             onclick="return confirm('Hapus <?= htmlspecialchars($f['username']) ?> dari teman?')"
             class="btn-icon btn-remove" id="rm-<?= $f['id'] ?>" title="Hapus">
            <svg width="15" height="15" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/></svg>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

</div>

<?php include 'includes/footer.php'; ?>
