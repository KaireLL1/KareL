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

<div style="max-width:480px;margin:0 auto;padding:20px 16px;">

  <h1 style="font-size:22px;font-weight:900;letter-spacing:-.5px;margin-bottom:20px;">Teman</h1>

  <!-- Search -->
  <form method="GET" id="search-form" style="margin-bottom:24px;">
    <div style="display:flex;gap:10px;">
      <input id="search-input" type="text" name="q" value="<?= htmlspecialchars($search) ?>"
        placeholder="Cari username..."
        style="flex:1;background:#111;border:1.5px solid #222;border-radius:14px;padding:13px 16px;color:#fff;font-size:15px;transition:border-color .2s;"
        onfocus="this.style.borderColor='#FFD60A'" onblur="this.style.borderColor='#222'">
      <button id="search-btn" type="submit"
        style="background:#FFD60A;color:#000;border:none;border-radius:14px;padding:0 20px;font-size:14px;font-weight:800;cursor:pointer;">
        Cari
      </button>
    </div>
  </form>

  <?php if ($search): ?>
  <!-- Search Results -->
  <section style="margin-bottom:28px;">
    <p style="font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">Hasil Pencarian</p>
    <?php if (empty($searchResults)): ?>
      <div style="background:#111;border:1px solid #1a1a1a;border-radius:20px;padding:24px;text-align:center;color:#444;font-size:14px;">
        Tidak ada user dengan username "<?= htmlspecialchars($search) ?>"
      </div>
    <?php else: ?>
    <div id="search-results" style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($searchResults as $u): ?>
      <?php $fs = $fsMap[$u['id']] ?? null; ?>
      <div id="sr-<?= $u['id'] ?>" style="display:flex;align-items:center;gap:12px;background:#111;border:1px solid #1a1a1a;border-radius:20px;padding:12px 14px;">
        <a href="profile.php?id=<?= $u['id'] ?>" style="text-decoration:none;">
          <?php if (!empty($u['avatar'])): ?>
            <img src="<?= htmlspecialchars($u['avatar']) ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #222;">
          <?php else: ?>
            <div style="width:44px;height:44px;border-radius:50%;background:#1a1a1a;border:2px solid #222;display:flex;align-items:center;justify-content:center;">
              <svg width="22" height="22" fill="#555" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
          <?php endif; ?>
        </a>
        <div style="flex:1;min-width:0;">
          <p style="font-size:15px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($u['username']) ?></p>
        </div>
        <?php if (!$fs): ?>
          <a href="friends.php?action=add&user_id=<?= $u['id'] ?>&q=<?= urlencode($search) ?>" id="add-<?= $u['id'] ?>"
             style="background:#FFD60A;color:#000;border-radius:100px;padding:8px 16px;font-size:13px;font-weight:800;text-decoration:none;white-space:nowrap;">
            + Tambah
          </a>
        <?php elseif ($fs['status']==='accepted'): ?>
          <span style="background:rgba(34,197,94,.1);color:#4ade80;border-radius:100px;padding:8px 14px;font-size:13px;font-weight:700;white-space:nowrap;">Teman ✓</span>
        <?php elseif ($fs['status']==='pending' && $fs['requester_id']==$me): ?>
          <span style="background:#1a1a1a;color:#555;border-radius:100px;padding:8px 14px;font-size:13px;font-weight:700;white-space:nowrap;">Terkirim</span>
        <?php elseif ($fs['status']==='pending'): ?>
          <a href="friends.php?action=accept&user_id=<?= $u['id'] ?>&q=<?= urlencode($search) ?>" id="acc-<?= $u['id'] ?>"
             style="background:#FFD60A;color:#000;border-radius:100px;padding:8px 16px;font-size:13px;font-weight:800;text-decoration:none;white-space:nowrap;">
            Terima
          </a>
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
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
      <p style="font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.8px;">Permintaan Masuk</p>
      <span style="background:#FFD60A;color:#000;border-radius:100px;padding:2px 8px;font-size:11px;font-weight:800;"><?= count($pending) ?></span>
    </div>
    <div id="pending-list" style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($pending as $r): ?>
      <div id="pend-<?= $r['id'] ?>" style="display:flex;align-items:center;gap:12px;background:#111;border:1.5px solid rgba(255,214,10,.15);border-radius:20px;padding:12px 14px;">
        <a href="profile.php?id=<?= $r['id'] ?>" style="text-decoration:none;">
          <?php if (!empty($r['avatar'])): ?>
            <img src="<?= htmlspecialchars($r['avatar']) ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #222;">
          <?php else: ?>
            <div style="width:44px;height:44px;border-radius:50%;background:#1a1a1a;border:2px solid #222;display:flex;align-items:center;justify-content:center;">
              <svg width="22" height="22" fill="#555" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
          <?php endif; ?>
        </a>
        <div style="flex:1;min-width:0;">
          <p style="font-size:15px;font-weight:700;"><?= htmlspecialchars($r['username']) ?></p>
          <p style="font-size:12px;color:#555;"><?= timeAgo($r['created_at']) ?></p>
        </div>
        <div style="display:flex;gap:8px;">
          <a href="friends.php?action=reject&user_id=<?= $r['id'] ?>" id="rej-<?= $r['id'] ?>"
             style="width:38px;height:38px;background:#1a1a1a;border:1.5px solid #2a2a2a;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;">
            <svg width="16" height="16" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </a>
          <a href="friends.php?action=accept&user_id=<?= $r['id'] ?>" id="acc-req-<?= $r['id'] ?>"
             style="width:38px;height:38px;background:#FFD60A;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;">
            <svg width="16" height="16" fill="none" stroke="#000" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Friends List -->
  <section style="margin-bottom:32px;">
    <p style="font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">
      Teman (<?= count($friends) ?>)
    </p>
    <?php if (empty($friends)): ?>
    <div style="background:#111;border:1px solid #1a1a1a;border-radius:20px;padding:32px;text-align:center;">
      <p style="color:#555;font-size:14px;">Cari teman di atas 👆</p>
    </div>
    <?php else: ?>
    <div id="friends-list" style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($friends as $f): ?>
      <div id="fr-<?= $f['id'] ?>" style="display:flex;align-items:center;gap:12px;background:#111;border:1px solid #1a1a1a;border-radius:20px;padding:12px 14px;">
        <a href="profile.php?id=<?= $f['id'] ?>" style="text-decoration:none;">
          <?php if (!empty($f['avatar'])): ?>
            <img src="<?= htmlspecialchars($f['avatar']) ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #222;">
          <?php else: ?>
            <div style="width:44px;height:44px;border-radius:50%;background:#1a1a1a;border:2px solid #222;display:flex;align-items:center;justify-content:center;">
              <svg width="22" height="22" fill="#555" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
          <?php endif; ?>
        </a>
        <a href="profile.php?id=<?= $f['id'] ?>" style="flex:1;min-width:0;text-decoration:none;">
          <p style="font-size:15px;font-weight:700;color:#fff;"><?= htmlspecialchars($f['username']) ?></p>
        </a>
        <a href="friends.php?action=remove&user_id=<?= $f['id'] ?>" id="rm-<?= $f['id'] ?>"
           onclick="return confirm('Hapus <?= htmlspecialchars($f['username']) ?> dari teman?')"
           style="width:34px;height:34px;background:#1a1a1a;border:1.5px solid #2a2a2a;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;">
          <svg width="15" height="15" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/></svg>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</div>

<?php include 'includes/footer.php'; ?>
