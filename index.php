<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$currentUser = getCurrentUser($pdo);
$userId = $_SESSION['user_id'];

// Filter by specific friend
$filterUserId = (int)($_GET['user'] ?? 0);

// Fetch friends list for dropdown
$fStmt = $pdo->prepare("
    SELECT u.id, u.username, u.avatar
    FROM friendships f
    JOIN users u ON (CASE WHEN f.requester_id=? THEN f.receiver_id ELSE f.requester_id END = u.id)
    WHERE (f.requester_id=? OR f.receiver_id=?) AND f.status='accepted'
    ORDER BY u.username
");
$fStmt->execute([$userId,$userId,$userId]);
$friendsList = $fStmt->fetchAll();
$filterUser = null;
if ($filterUserId) {
    foreach ($friendsList as $fl) { if ($fl['id']==$filterUserId) { $filterUser=$fl; break; } }
    if (!$filterUser) $filterUserId = 0; // reset if not a friend
}

// Fetch posts
if ($filterUserId) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.avatar
        FROM posts p JOIN users u ON p.user_id = u.id
        WHERE p.user_id = ? ORDER BY p.created_at DESC
    ");
    $stmt->execute([$filterUserId]);
} else {
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
}
$posts = $stmt->fetchAll();

$rxMap=[]; $myRxMap=[]; $cmtMap=[];
if (!empty($posts)) {
    $ids = array_column($posts,'id');
    $ph  = implode(',', array_fill(0,count($ids),'?'));
    $s=$pdo->prepare("SELECT post_id,emoji,COUNT(*) cnt FROM reactions WHERE post_id IN($ph) GROUP BY post_id,emoji");
    $s->execute($ids); foreach($s->fetchAll() as $r) $rxMap[$r['post_id']][$r['emoji']]=(int)$r['cnt'];
    $s=$pdo->prepare("SELECT post_id,emoji FROM reactions WHERE post_id IN($ph) AND user_id=?");
    $s->execute([...$ids,$userId]); foreach($s->fetchAll() as $r) $myRxMap[$r['post_id']]=$r['emoji'];
    $s=$pdo->prepare("SELECT c.id,c.post_id,c.content,u.username FROM comments c JOIN users u ON c.user_id=u.id WHERE c.post_id IN($ph) ORDER BY c.id ASC");
    $s->execute($ids); foreach($s->fetchAll() as $c) $cmtMap[$c['post_id']][]=$c;
}

$EK = ["\u{2764}\u{FE0F}","\u{1F525}","\u{1F970}","\u{1F60A}"];
$ED = ['&#x2764;&#xFE0F;','&#x1F525;','&#x1F970;','&#x1F60A;'];

$pageTitle = 'Home';
include 'includes/header.php';
?>


<style>
/* Fixed top bar */
.top-bar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 60;
  background: linear-gradient(to bottom, rgba(0,0,0,.85) 0%, transparent 100%);
  padding: 14px 16px 24px;
  display: flex; align-items: center; justify-content: space-between;
  pointer-events: none;
}
.top-bar > * { pointer-events: all; }

/* Snap scroll feed */
.feed-wrap {
  height: 100dvh;
  overflow-y: scroll;
  scroll-snap-type: y mandatory;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior-y: contain;
}
.feed-wrap::-webkit-scrollbar { display: none; }

/* Each snap item */
.feed-item {
  height: 100dvh;
  scroll-snap-align: start;
  scroll-snap-stop: always;
  display: flex; flex-direction: column;
  padding: 70px 12px 88px;
  max-width: 480px; margin: 0 auto;
  opacity: 0; transition: opacity .4s ease;
}
.feed-item.visible { opacity: 1; }

/* Photo fills remaining space */
.photo-wrap {
  flex: 1; min-height: 0;
  border-radius: 28px; overflow: hidden;
  position: relative; background: #111;
}
.photo-wrap img {
  width: 100%; height: 100%; object-fit: cover; display: block;
}
.caption-overlay {
  position: absolute; bottom: 0; left: 0; right: 0;
  background: linear-gradient(to top, rgba(0,0,0,.6) 0%, transparent 100%);
  padding: 40px 16px 16px; text-align: center;
}

/* Sender row */
.sender-row {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 2px 6px; flex-shrink: 0;
}

/* Reaction bar */
.rxbar {
  display: flex; align-items: center; gap: 5px;
  background: rgba(255,255,255,.08);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 100px; padding: 8px 10px 8px 14px;
  flex-shrink: 0;
}

/* Menu btn */
.menu-btn {
  width: 32px; height: 32px;
  background: rgba(0,0,0,.4); backdrop-filter: blur(8px);
  border: none; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #fff; flex-shrink: 0;
}

/* Comments panel */
.cbox {
  display: none; margin-top: 6px;
  background: rgba(20,20,20,.95); backdrop-filter: blur(16px);
  border-radius: 20px; overflow: hidden; flex-shrink: 0;
}

/* ── Desktop: normal scroll (no snap) ── */
@media (min-width: 768px) {
  .top-bar { left: 256px; }

  /* Disable snap-scroll container on desktop */
  .feed-wrap {
    height: auto;
    overflow-y: visible;
    scroll-snap-type: none;
  }

  /* Each item auto height on desktop */
  .feed-item {
    height: auto;
    scroll-snap-align: none;
    scroll-snap-stop: unset;
    padding: 80px 12px 40px;
    opacity: 1; /* always visible on desktop */
  }

  /* Photo fixed aspect on desktop */
  .photo-wrap {
    flex: none;
    height: 420px;
  }
}


@keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
</style>

<!-- FIXED TOP BAR -->
<div class="top-bar">
  <a href="profile.php" style="text-decoration:none;">
    <?php if(!empty($currentUser['avatar'])): ?>
      <img src="<?=htmlspecialchars($currentUser['avatar'])?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #333;">
    <?php else: ?>
      <div style="width:36px;height:36px;border-radius:50%;background:#1a1a1a;border:2px solid #2a2a2a;display:flex;align-items:center;justify-content:center;"><svg width="16" height="16" fill="#666" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></div>
    <?php endif; ?>
  </a>

  <!-- Friends Dropdown -->
  <div style="position:relative;">
    <button id="filter-btn" onclick="toggleDropdown()" style="background:rgba(255,255,255,.12);backdrop-filter:blur(10px);border:none;border-radius:100px;padding:8px 14px;display:flex;align-items:center;gap:6px;cursor:pointer;color:#fff;">
      <?php if($filterUser): ?>
        <?php if(!empty($filterUser['avatar'])): ?>
          <img src="<?=htmlspecialchars($filterUser['avatar'])?>" style="width:20px;height:20px;border-radius:50%;object-fit:cover;">
        <?php endif; ?>
        <span style="font-size:14px;font-weight:700;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($filterUser['username'])?></span>
      <?php else: ?>
        <span style="font-size:14px;font-weight:700;">All Friends</span>
      <?php endif; ?>
      <svg id="filter-arrow" width="12" height="12" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24" style="transition:transform .2s;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <!-- Dropdown panel -->
    <div id="filter-dropdown" style="display:none;position:absolute;top:calc(100% + 10px);left:50%;transform:translateX(-50%);background:#111;border:1px solid #222;border-radius:20px;overflow:hidden;min-width:180px;max-height:320px;overflow-y:auto;z-index:80;box-shadow:0 12px 40px rgba(0,0,0,.8);">
      <!-- All Friends option -->
      <a href="index.php" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;background:<?=$filterUserId?'transparent':'rgba(255,214,10,.08)'?>;border-bottom:1px solid #1a1a1a;">
        <div style="width:32px;height:32px;border-radius:50%;background:#1e1e1e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="15" height="15" fill="none" stroke="#aaa" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        </div>
        <span style="font-size:14px;font-weight:700;color:<?=$filterUserId?'#aaa':'#FFD60A'?>;">All Friends</span>
        <?php if(!$filterUserId): ?><svg width="14" height="14" fill="#FFD60A" viewBox="0 0 24 24" style="margin-left:auto;flex-shrink:0;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg><?php endif; ?>
      </a>

      <!-- Friend rows -->
      <?php foreach($friendsList as $fl): ?>
      <a href="index.php?user=<?=$fl['id']?>" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;background:<?=$filterUserId==$fl['id']?'rgba(255,214,10,.08)':'transparent'?>;" onmouseover="this.style.background='rgba(255,255,255,.04)'" onmouseout="this.style.background='<?=$filterUserId==$fl['id']?'rgba(255,214,10,.08)':'transparent'?>'">
        <?php if(!empty($fl['avatar'])): ?>
          <img src="<?=htmlspecialchars($fl['avatar'])?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
        <?php else: ?>
          <div style="width:32px;height:32px;border-radius:50%;background:#222;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;font-weight:800;color:#555;"><?=strtoupper(substr($fl['username'],0,2))?></div>
        <?php endif; ?>
        <span style="font-size:14px;font-weight:600;color:<?=$filterUserId==$fl['id']?'#FFD60A':'#fff'?>;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($fl['username'])?></span>
        <?php if($filterUserId==$fl['id']): ?><svg width="14" height="14" fill="#FFD60A" viewBox="0 0 24 24" style="flex-shrink:0;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg><?php endif; ?>
      </a>
      <?php endforeach; ?>

      <?php if(empty($friendsList)): ?>
      <div style="padding:16px;text-align:center;color:#444;font-size:13px;">Belum ada teman</div>
      <?php endif; ?>
    </div>
  </div>

  <a href="chat.php" style="width:36px;height:36px;background:rgba(255,255,255,.12);backdrop-filter:blur(10px);border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;">
    <svg width="17" height="17" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
  </a>
</div>

<script>
function toggleDropdown(){
  const d=document.getElementById('filter-dropdown');
  const a=document.getElementById('filter-arrow');
  const open=d.style.display==='none';
  d.style.display=open?'block':'none';
  a.style.transform=open?'rotate(180deg)':'rotate(0deg)';
}
document.addEventListener('click',e=>{
  if(!e.target.closest('#filter-btn')&&!e.target.closest('#filter-dropdown')){
    const d=document.getElementById('filter-dropdown');
    const a=document.getElementById('filter-arrow');
    if(d){d.style.display='none'; a.style.transform='rotate(0deg)';}
  }
});
</script>


<?php if(empty($posts)): ?>
<!-- Empty state (non-snap) -->
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100dvh;text-align:center;padding:32px;">
  <div style="width:72px;height:72px;border-radius:50%;background:#111;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
    <svg width="32" height="32" fill="none" stroke="#333" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
  </div>
  <h2 style="font-size:18px;font-weight:800;margin-bottom:8px;">Belum ada momen</h2>
  <p style="color:#555;font-size:13px;margin-bottom:24px;">Tambah teman atau share momen pertamamu!</p>
  <div style="display:flex;gap:10px;">
    <a href="friends.php" style="padding:11px 18px;background:#111;border:1.5px solid #222;color:#fff;border-radius:100px;font-size:13px;font-weight:700;text-decoration:none;">Cari Teman</a>
    <a href="camera.php" style="padding:11px 18px;background:#FFD60A;color:#000;border-radius:100px;font-size:13px;font-weight:800;text-decoration:none;">📸 Post</a>
  </div>
</div>

<?php else: ?>
<!-- SNAP SCROLL FEED -->
<div class="feed-wrap" id="feed">
  <?php foreach($posts as $i=>$post):
    $pid     = $post['id'];
    $isOwn   = $post['user_id']==$userId;
    $myCounts= $rxMap[$pid]   ?? [];
    $myEmoji = $myRxMap[$pid] ?? null;
    $comments= $cmtMap[$pid]  ?? [];
  ?>
  <div class="feed-item" id="fi-<?=$pid?>">

    <!-- PHOTO -->
    <div class="photo-wrap">
      <img src="<?=htmlspecialchars($post['image_path'])?>" loading="<?=$i<2?'eager':'lazy'?>" alt="momen">

      <?php if(!empty($post['caption'])): ?>
      <div class="caption-overlay">
        <span style="color:#fff;font-size:14px;font-weight:500;text-shadow:0 1px 6px rgba(0,0,0,.7);"><?=htmlspecialchars($post['caption'])?></span>
      </div>
      <?php endif; ?>

      <?php if($isOwn): ?>
      <div style="position:absolute;top:12px;right:12px;z-index:5;">
        <button class="menu-btn" onclick="toggleMenu(<?=$pid?>)">
          <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
        </button>
        <div id="menu-<?=$pid?>" style="display:none;position:absolute;right:0;top:38px;background:#1a1a1a;border:1px solid #2a2a2a;border-radius:14px;overflow:hidden;min-width:130px;z-index:20;box-shadow:0 8px 24px rgba(0,0,0,.7);">
          <a href="edit_post.php?id=<?=$pid?>" style="display:flex;align-items:center;gap:8px;padding:11px 14px;color:#fff;text-decoration:none;font-size:13px;font-weight:600;">✎ Edit</a>
          <button onclick="confirmDelete(<?=$pid?>)" style="display:flex;align-items:center;gap:8px;padding:11px 14px;color:#ef4444;background:none;border:none;cursor:pointer;font-size:13px;font-weight:600;width:100%;">✕ Hapus</button>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- SENDER ROW -->
    <div class="sender-row">
      <?php if(!empty($post['avatar'])): ?>
        <img src="<?=htmlspecialchars($post['avatar'])?>" style="width:26px;height:26px;border-radius:50%;object-fit:cover;flex-shrink:0;">
      <?php else: ?>
        <div style="width:26px;height:26px;border-radius:50%;background:#222;flex-shrink:0;display:flex;align-items:center;justify-content:center;"><svg width="13" height="13" fill="#555" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></div>
      <?php endif; ?>
      <a href="profile.php?id=<?=$post['user_id']?>" style="font-size:14px;font-weight:800;color:#fff;text-decoration:none;"><?=htmlspecialchars($post['username'])?></a>
      <span style="font-size:12px;color:#555;"><?=timeAgo($post['created_at'])?></span>
    </div>

    <!-- REACTION BAR (friends only) -->
    <?php if(!$isOwn): ?>
    <div class="rxbar">
      <a href="chat.php?with=<?=$post['user_id']?>" style="flex:1;min-width:0;color:#aaa;font-size:13px;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Send message...</a>
      <?php foreach($EK as $ei=>$eKey):
        $active = ($myEmoji===$eKey);
      ?>
      <button onclick="react(<?=$pid?>,<?=$ei?>)" id="rb-<?=$pid?>-<?=$ei?>"
        style="background:none;border:none;cursor:pointer;font-size:22px;line-height:1;min-width:34px;min-height:34px;display:flex;align-items:center;justify-content:center;opacity:<?=$active?1:.6?>;transform:<?=$active?'scale(1.2)':'scale(1)'?>;transition:all .15s;">
        <?=$ED[$ei]?>
      </button>
      <?php endforeach; ?>
      <button onclick="toggleComments(<?=$pid?>)" style="width:30px;height:30px;background:rgba(255,255,255,.1);border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="13" height="13" fill="none" stroke="#ccc" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5c4.97 0 9 3.582 9 8s-4.03 8-9 8a9.77 9.77 0 01-4.204-.946L3 21l1.5-4.5A7.93 7.93 0 013 12.5c0-4.418 4.03-8 9-8z"/></svg>
      </button>
    </div>

    <!-- COMMENTS BOX -->
    <div class="cbox" id="cbox-<?=$pid?>">
      <div id="clist-<?=$pid?>" style="padding:10px 14px 6px;display:flex;flex-direction:column;gap:4px;max-height:120px;overflow-y:auto;">
        <?php foreach(array_slice($comments,-5) as $c): ?>
        <p style="font-size:13px;color:#bbb;line-height:1.4;"><span style="font-weight:700;color:#fff;"><?=htmlspecialchars($c['username'])?> </span><?=htmlspecialchars($c['content'])?></p>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;align-items:center;gap:8px;padding:6px 10px 10px;">
        <div style="flex:1;display:flex;background:#111;border-radius:100px;padding:5px 6px 5px 12px;gap:6px;border:1.5px solid #222;">
          <input id="ci-<?=$pid?>" type="text" maxlength="200" placeholder="Tulis komentar..."
            style="flex:1;background:none;border:none;outline:none;color:#fff;font-size:16px;"
            onkeydown="if(event.key==='Enter')sendComment(<?=$pid?>)">
          <button onclick="sendComment(<?=$pid?>)" style="width:26px;height:26px;background:#FFD60A;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="11" height="11" fill="none" stroke="#000" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
          </button>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /feed-item -->
  <?php endforeach; ?>
</div><!-- /feed-wrap -->
<?php endif; ?>

<!-- Delete Modal -->
<div id="delete-modal" style="display:none;position:fixed;inset:0;z-index:200;align-items:flex-end;justify-content:center;padding:0 16px 20px;">
  <div onclick="closeDeleteModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);"></div>
  <div style="position:relative;background:#111;border:1px solid #222;border-radius:28px;padding:24px;width:100%;max-width:400px;">
    <h3 style="font-size:17px;font-weight:800;margin-bottom:6px;">Hapus Momen?</h3>
    <p style="color:#555;font-size:13px;margin-bottom:18px;">Foto ini akan dihapus permanen.</p>
    <div style="display:flex;gap:10px;">
      <button onclick="closeDeleteModal()" style="flex:1;padding:13px;background:#1a1a1a;border:1.5px solid #2a2a2a;color:#aaa;border-radius:100px;font-size:14px;font-weight:700;cursor:pointer;">Batal</button>
      <a id="confirm-delete-link" href="#" style="flex:1;padding:13px;background:#ef4444;color:#fff;border-radius:100px;font-size:14px;font-weight:700;text-align:center;text-decoration:none;">Hapus</a>
    </div>
  </div>
</div>

<script>
// On desktop (>=768px): show all items immediately (normal scroll)
// On mobile: fade-in via IntersectionObserver on snap container
const isDesktop = window.innerWidth >= 768;
if (isDesktop) {
  document.querySelectorAll('.feed-item').forEach(el => el.classList.add('visible'));
} else {
  const feed = document.getElementById('feed');
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
  }, { root: feed, threshold: 0.4 });
  document.querySelectorAll('.feed-item').forEach(el => obs.observe(el));
  const first = document.querySelector('.feed-item');
  if (first) first.classList.add('visible');
}

// Reaction
function react(pid, ei) {
  const btn = document.getElementById('rb-'+pid+'-'+ei);
  if (btn) { btn.style.transform='scale(1.5)'; setTimeout(()=>{ btn.style.transform='scale(1.2)'; },200); }
  fetch('api/react.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'post_id='+pid+'&emoji_idx='+ei})
  .then(r=>r.json()).then(d=>{
    if(!d.ok)return;
    for(let i=0;i<4;i++){
      const b=document.getElementById('rb-'+pid+'-'+i);
      if(!b)continue;
      const active=d.my_idx!==null&&d.my_idx==i;
      b.style.opacity=active?'1':'.6';
      b.style.transform=active?'scale(1.2)':'scale(1)';
    }
  }).catch(err=>console.error('React:',err));
}

// Comments
function toggleComments(pid){
  const box=document.getElementById('cbox-'+pid);
  box.style.display=box.style.display==='block'?'none':'block';
  if(box.style.display==='block'){ const inp=document.getElementById('ci-'+pid); if(inp) inp.focus(); }
}
function sendComment(pid){
  const inp=document.getElementById('ci-'+pid);
  const txt=inp.value.trim(); if(!txt)return;
  fetch('api/comment.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'post_id='+pid+'&content='+encodeURIComponent(txt)})
  .then(r=>r.json()).then(d=>{
    if(!d.ok)return; inp.value='';
    const list=document.getElementById('clist-'+pid);
    const p=document.createElement('p');
    p.style.cssText='font-size:13px;color:#bbb;line-height:1.4;';
    p.innerHTML='<span style="font-weight:700;color:#fff;">'+d.comment.username+' </span>'+d.comment.content;
    list.appendChild(p); list.scrollTop=list.scrollHeight;
  });
}

// Menu
function toggleMenu(id){
  document.querySelectorAll('[id^="menu-"]').forEach(m=>{if(m.id!=='menu-'+id)m.style.display='none';});
  const m=document.getElementById('menu-'+id);
  m.style.display=m.style.display==='none'?'block':'none';
}
document.addEventListener('click',e=>{
  if(!e.target.closest('[id^="menu-btn"]')&&!e.target.closest('[id^="menu-"]'))
    document.querySelectorAll('[id^="menu-"]').forEach(m=>m.style.display='none');
});
function confirmDelete(id){document.getElementById('confirm-delete-link').href='delete_post.php?id='+id;document.getElementById('delete-modal').style.display='flex';}
function closeDeleteModal(){document.getElementById('delete-modal').style.display='none';}
</script>

<?php include 'includes/footer.php'; ?>
