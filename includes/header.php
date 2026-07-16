<?php
$pendingCount = isLoggedIn() ? getPendingCount($pdo) : 0;
$currentPage  = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= $pageTitle ?? 'KareL' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --yellow: #FFD60A;
      --yellow-dim: rgba(255,214,10,.15);
      --card: #111111;
      --border: #1F1F1F;
    }
    * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
    body { background: #000; color: #fff; min-height: 100vh; }

    /* Bottom Nav */
    .nav-bar {
      position: fixed; bottom: 0; left: 0; right: 0; z-index: 99;
      background: rgba(0,0,0,.85);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-top: 1px solid #1a1a1a;
      padding-bottom: env(safe-area-inset-bottom);
    }
    .nav-inner { display: flex; align-items: center; justify-content: space-around; padding: 10px 20px 8px; }
    .nav-item { display: flex; flex-direction: column; align-items: center; gap: 3px; cursor: pointer; text-decoration: none; }
    .nav-icon { width: 24px; height: 24px; }
    .nav-label { font-size: 10px; font-weight: 600; }
    .nav-item.active .nav-label { color: var(--yellow); }
    .nav-item.active .nav-icon { color: var(--yellow); }
    .nav-item:not(.active) .nav-label { color: #555; }
    .nav-item:not(.active) .nav-icon { color: #555; }

    /* Capture button */
    .nav-capture {
      width: 60px; height: 60px; border-radius: 50%;
      background: var(--yellow);
      display: flex; align-items: center; justify-content: center;
      margin-top: -20px;
      box-shadow: 0 0 0 4px #000, 0 0 0 6px #1a1a1a;
      transition: transform .15s ease;
    }
    .nav-capture:active { transform: scale(.92); }

    /* Card style */
    .k-card { background: var(--card); border: 1px solid var(--border); border-radius: 24px; overflow: hidden; }
    .k-btn { background: var(--yellow); color: #000; font-weight: 700; border-radius: 100px; border: none; cursor: pointer; transition: opacity .15s; }
    .k-btn:hover { opacity: .88; }
    .k-btn:active { opacity: .75; transform: scale(.97); }
    .k-input {
      width: 100%; background: #1a1a1a; border: 1.5px solid #2a2a2a;
      border-radius: 14px; padding: 14px 16px; color: #fff; font-size: 15px;
      transition: border-color .2s;
    }
    .k-input:focus { outline: none; border-color: var(--yellow); }
    .k-input::placeholder { color: #444; }

    /* Locket-style post card */
    .moment-card { border-radius: 28px; overflow: hidden; position: relative; background: #111; }
    .moment-img { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; }
    .moment-overlay {
      position: absolute; bottom: 0; left: 0; right: 0;
      background: linear-gradient(to top, rgba(0,0,0,.7) 0%, transparent 100%);
      padding: 40px 16px 16px;
    }

    /* Animations */
    @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
    .fade-up { animation: fadeUp .4s ease forwards; }
    @keyframes pulse-ring { 0%,100%{box-shadow:0 0 0 0 rgba(255,214,10,.4)} 50%{box-shadow:0 0 0 10px rgba(255,214,10,0)} }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 0; }
  </style>
</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- Bottom Nav -->
<nav class="nav-bar md:hidden">
  <div class="nav-inner">

    <a href="index.php" id="nav-home" class="nav-item <?= $currentPage==='index.php'?'active':'' ?>">
      <svg class="nav-icon" fill="<?= $currentPage==='index.php'?'currentColor':'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
      </svg>
      <span class="nav-label">Home</span>
    </a>

    <a href="friends.php" id="nav-friends" class="nav-item <?= $currentPage==='friends.php'?'active':'' ?> relative">
      <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
      </svg>
      <?php if ($pendingCount > 0): ?>
      <span style="position:absolute;top:0;right:4px;background:var(--yellow);color:#000;font-size:9px;font-weight:800;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;"><?= $pendingCount ?></span>
      <?php endif; ?>
      <span class="nav-label">Teman</span>
    </a>

    <a href="camera.php" id="nav-camera" class="nav-capture">
      <svg width="28" height="28" fill="none" stroke="#000" stroke-width="2.2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
      </svg>
    </a>

    <a href="profile.php" id="nav-profile" class="nav-item <?= $currentPage==='profile.php'?'active':'' ?>">
      <?php if (!empty($currentUser['avatar'])): ?>
        <img src="<?= htmlspecialchars($currentUser['avatar']) ?>"
             style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:2px solid <?= $currentPage==='profile.php'?'var(--yellow)':'#333' ?>">
      <?php else: ?>
        <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
        </svg>
      <?php endif; ?>
      <span class="nav-label">Profil</span>
    </a>

  </div>
</nav>

<!-- Desktop Sidebar -->
<aside class="hidden md:flex fixed left-0 top-0 bottom-0 w-64 flex-col border-r border-[#1a1a1a] z-50" style="background:#000;">
  <div class="p-6 border-b border-[#1a1a1a]">
    <span style="font-size:26px;font-weight:900;letter-spacing:-1px;">
      <span style="color:var(--yellow)">Kare</span>L
    </span>
  </div>
  <nav class="flex-1 p-4 space-y-1">
    <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= $currentPage==='index.php'?'bg-[#FFD60A]/10 text-[#FFD60A]':'text-[#666] hover:text-white hover:bg-white/5' ?>">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
      <span class="font-600 text-sm">Home</span>
    </a>
    <a href="friends.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors relative <?= $currentPage==='friends.php'?'bg-[#FFD60A]/10 text-[#FFD60A]':'text-[#666] hover:text-white hover:bg-white/5' ?>">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
      <span class="font-600 text-sm">Teman</span>
      <?php if ($pendingCount > 0): ?>
      <span style="background:var(--yellow);color:#000;font-size:10px;font-weight:800;padding:1px 6px;border-radius:100px;margin-left:auto;"><?= $pendingCount ?></span>
      <?php endif; ?>
    </a>
    <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= $currentPage==='profile.php'?'bg-[#FFD60A]/10 text-[#FFD60A]':'text-[#666] hover:text-white hover:bg-white/5' ?>">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
      <span class="font-600 text-sm">Profil</span>
    </a>
  </nav>
  <div class="p-4">
    <a href="camera.php" class="k-btn flex items-center justify-center gap-2 w-full py-3.5 text-sm">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
      Post Momen
    </a>
  </div>
</aside>
<?php endif; ?>

<div class="<?= isLoggedIn() ? 'md:ml-64' : '' ?>">
<div class="<?= isLoggedIn() ? 'pb-28 md:pb-0' : '' ?>">
