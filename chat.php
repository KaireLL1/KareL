<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$currentUser = getCurrentUser($pdo);
$me = $_SESSION['user_id'];

// Friends list
$stmt = $pdo->prepare("SELECT u.id,u.username,u.avatar FROM friendships f
    JOIN users u ON (CASE WHEN f.requester_id=? THEN f.receiver_id ELSE f.requester_id END = u.id)
    WHERE (f.requester_id=? OR f.receiver_id=?) AND f.status='accepted' ORDER BY u.username");
$stmt->execute([$me,$me,$me]);
$friends = $stmt->fetchAll();

$withId   = (int)($_GET['with'] ?? 0);
$withUser = null;
$messages = [];
$chatError = '';

try {
    if ($withId) {
        $s = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $s->execute([$withId]);
        $withUser = $s->fetch();
        if ($withUser) {
            $s = $pdo->prepare("SELECT m.*,u.username FROM messages m
                JOIN users u ON m.sender_id=u.id
                WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
                ORDER BY m.id ASC LIMIT 100");
            $s->execute([$me,$withId,$withId,$me]);
            $messages = $s->fetchAll();
            $pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?")
                ->execute([$withId,$me]);
        }
    }
} catch(Exception $e) {
    $chatError = true;
}

$pageTitle = 'Chat';
include 'includes/header.php';
?>

<div style="max-width:480px;margin:0 auto;padding:0 0 90px;">

<?php if ($chatError): ?>
<div style="margin:40px 20px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:20px;padding:24px;text-align:center;">
  <p style="color:#f87171;font-size:15px;font-weight:700;margin-bottom:8px;">⚠️ Tabel messages belum ada</p>
  <p style="color:#666;font-size:13px;">Buka phpMyAdmin → <b>karel_db</b> → Import → pilih <b>karel_messages.sql</b></p>
</div>

<?php elseif (!$withUser): ?>
<!-- Friends list (Messages screen) -->
<div style="padding:20px 16px;">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
    <a href="index.php" style="text-decoration:none;">
      <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 style="font-size:22px;font-weight:900;">Messages</h1>
  </div>

  <?php if (empty($friends)): ?>
  <div style="text-align:center;padding:60px 20px;color:#444;">
    <p style="font-size:15px;margin-bottom:12px;">Belum ada teman</p>
    <a href="friends.php" style="color:#FFD60A;font-weight:700;text-decoration:none;">Cari teman →</a>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:4px;">
    <?php foreach ($friends as $f): ?>
    <a href="chat.php?with=<?= $f['id'] ?>" id="chat-<?= $f['id'] ?>"
       style="display:flex;align-items:center;gap:14px;padding:12px 8px;text-decoration:none;border-radius:16px;transition:background .15s;"
       onmouseover="this.style.background='#111'" onmouseout="this.style.background='transparent'">
      <?php if (!empty($f['avatar'])): ?>
        <img src="<?= htmlspecialchars($f['avatar']) ?>" style="width:52px;height:52px;border-radius:50%;object-fit:cover;flex-shrink:0;">
      <?php else: ?>
        <div style="width:52px;height:52px;border-radius:50%;background:#222;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#555;">
          <?= strtoupper(substr($f['username'],0,2)) ?>
        </div>
      <?php endif; ?>
      <div style="flex:1;min-width:0;">
        <p style="font-size:15px;font-weight:700;color:#fff;"><?= htmlspecialchars($f['username']) ?></p>
        <p style="font-size:13px;color:#555;margin-top:2px;">Start the convo!</p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- Chat view -->
<div style="display:flex;flex-direction:column;height:calc(100dvh - 90px);">
  <!-- Header -->
  <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid #111;position:sticky;top:0;z-index:10;background:#000;">
    <a href="chat.php" style="text-decoration:none;">
      <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <?php if (!empty($withUser['avatar'])): ?>
      <img src="<?= htmlspecialchars($withUser['avatar']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
    <?php else: ?>
      <div style="width:36px;height:36px;border-radius:50%;background:#222;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#666;"><?= strtoupper(substr($withUser['username'],0,2)) ?></div>
    <?php endif; ?>
    <p style="font-size:16px;font-weight:800;"><?= htmlspecialchars($withUser['username']) ?></p>
  </div>

  <!-- Messages area -->
  <div id="msg-area" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:6px;">
    <?php foreach ($messages as $msg): $isMe = $msg['sender_id']==$me; ?>
    <div id="m<?= $msg['id'] ?>" style="display:flex;justify-content:<?= $isMe?'flex-end':'flex-start' ?>;">
      <div style="max-width:72%;padding:10px 14px;border-radius:<?= $isMe?'18px 18px 4px 18px':'18px 18px 18px 4px' ?>;background:<?= $isMe?'#FFD60A':'#1a1a1a' ?>;color:<?= $isMe?'#000':'#fff' ?>;">
        <p style="font-size:14px;line-height:1.4;word-break:break-word;"><?= htmlspecialchars($msg['content']) ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Input -->
  <div style="padding:10px 14px;border-top:1px solid #111;background:#000;display:flex;gap:10px;align-items:center;">
    <div style="flex:1;display:flex;align-items:center;background:#111;border:1.5px solid #222;border-radius:100px;padding:8px 8px 8px 16px;gap:8px;" onfocusin="this.style.borderColor='#FFD60A'" onfocusout="this.style.borderColor='#222'">
      <input id="msg-input" type="text" maxlength="1000" placeholder="Pesan..."
        style="flex:1;background:none;border:none;outline:none;color:#fff;font-size:14px;"
        onkeydown="if(event.key==='Enter')sendMsg()">
      <button onclick="sendMsg()"
        style="width:32px;height:32px;background:#FFD60A;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="14" height="14" fill="none" stroke="#000" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
      </button>
    </div>
  </div>
</div>

<script>
const WITH_ID = <?= $withId ?>;
const MY_ID   = <?= $me ?>;
let lastId    = <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>;

(function(){ const a=document.getElementById('msg-area'); if(a) a.scrollTop=a.scrollHeight; })();

function appendMsg(id,content,isMe){
  const area=document.getElementById('msg-area');
  const d=document.createElement('div');
  d.id='m'+id;
  d.style.cssText='display:flex;justify-content:'+(isMe?'flex-end':'flex-start');
  const esc=content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  d.innerHTML=`<div style="max-width:72%;padding:10px 14px;border-radius:${isMe?'18px 18px 4px 18px':'18px 18px 18px 4px'};background:${isMe?'#FFD60A':'#1a1a1a'};color:${isMe?'#000':'#fff'}"><p style="font-size:14px;line-height:1.4;word-break:break-word;">${esc}</p></div>`;
  area.appendChild(d);
  area.scrollTop=area.scrollHeight;
}

function sendMsg(){
  const input=document.getElementById('msg-input');
  const text=input.value.trim(); if(!text)return;
  input.value='';
  fetch('api/messages.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'to='+WITH_ID+'&content='+encodeURIComponent(text)
  }).then(r=>r.json()).then(d=>{
    if(d.ok){appendMsg(d.id,text,true);lastId=d.id;}
  });
}

setInterval(()=>{
  fetch('api/messages.php?with='+WITH_ID+'&after='+lastId)
  .then(r=>r.json()).then(d=>{
    if(!d.ok||!d.messages.length)return;
    d.messages.forEach(m=>{
      if(document.getElementById('m'+m.id))return;
      appendMsg(m.id,m.content,parseInt(m.sender_id)===MY_ID);
      lastId=Math.max(lastId,parseInt(m.id));
    });
  });
},2500);
</script>
<?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
