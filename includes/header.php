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
    *, *::before, *::after {
      font-family: 'Plus Jakarta Sans', sans-serif;
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
    }
    html { overflow-x: hidden; }
    body {
      background: #000; color: #fff;
      min-height: 100vh; min-height: 100dvh;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }

    /* ── Bottom Nav (Locket 3-item) ── */
    .nav-bar {
      position: fixed; bottom: 0; left: 0; right: 0; z-index: 99;
      background: rgba(0,0,0,.92);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-top: 1px solid #111;
      padding-bottom: env(safe-area-inset-bottom, 6px);
    }
    .nav-inner {
      display: flex; align-items: center; justify-content: space-around;
      padding: 8px 20px 10px;
      max-width: 500px; margin: 0 auto;
    }
    .nav-item {
      display: flex; flex-direction: column; align-items: center;
      cursor: pointer; text-decoration: none;
      min-width: 48px; min-height: 44px; justify-content: center;
    }
    .nav-icon { width: 26px; height: 26px; }
    .nav-item.active .nav-icon  { color: #fff; }
    .nav-item:not(.active) .nav-icon { color: #444; }

    /* Locket camera circle */
    .nav-capture {
      width: 60px; height: 60px; border-radius: 50%;
      background: #fff;
      display: flex; align-items: center; justify-content: center;
      margin-top: -14px;
      border: 3px solid rgba(255,255,255,.2);
      box-shadow: 0 0 0 3px #000;
      transition: transform .15s ease;
      flex-shrink: 0;
    }
    .nav-capture:active { transform: scale(.88); }
    .nav-capture-inner { width: 48px; height: 48px; border-radius: 50%; background: #fff; }

    /* Cards / Buttons / Inputs */
    .k-card { background: var(--card); border: 1px solid var(--border); border-radius: 24px; overflow: hidden; }
    .k-btn {
      background: var(--yellow); color: #000; font-weight: 700;
      border-radius: 100px; border: none; cursor: pointer; transition: opacity .15s;
    }
    .k-btn:active { opacity: .75; transform: scale(.97); }
    .k-input {
      width: 100%; background: #1a1a1a; border: 1.5px solid #2a2a2a;
      border-radius: 14px; padding: 14px 16px; color: #fff;
      /* font-size 16px prevents iOS zoom on focus */
      font-size: 16px; transition: border-color .2s;
      appearance: none; -webkit-appearance: none;
    }
    .k-input:focus { outline: none; border-color: var(--yellow); }
    .k-input::placeholder { color: #444; }

    /* Post card */
    .moment-card { border-radius: 28px; overflow: hidden; position: relative; background: #111; }
    .moment-img  { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; }
    .moment-overlay {
      position: absolute; bottom: 0; left: 0; right: 0;
      background: linear-gradient(to top, rgba(0,0,0,.7) 0%, transparent 100%);
      padding: 40px 16px 16px;
    }

    /* Touch helpers */
    a, button { touch-action: manipulation; }

    /* Animations */
    @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
    .fade-up { animation: fadeUp .4s ease forwards; }

    /* Scrollbar hidden */
    ::-webkit-scrollbar { width: 0; height: 0; }

    /* Small phones (<380px) */
    @media (max-width: 380px) {
      .nav-inner { padding: 6px 10px 8px; }
      .nav-capture { width: 52px; height: 52px; margin-top: -10px; }
      .nav-capture-inner { width: 40px; height: 40px; }
      .nav-icon { width: 22px; height: 22px; }
    }
  </style>

</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- Bottom Nav — 3 item: Home | Camera | Dashboard -->
<nav class="nav-bar md:hidden">
  <div class="nav-inner">

    <!-- Home -->
    <a href="index.php" id="nav-home" class="nav-item <?= $currentPage==='index.php'?'active':'' ?>">
      <svg class="nav-icon" fill="<?= $currentPage==='index.php'?'currentColor':'none' ?>" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z
             M3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25z
             M13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6z
             M13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
      </svg>
    </a>

    <!-- Camera (Locket big white circle) -->
    <a href="camera.php" id="nav-camera" class="nav-capture">
      <div class="nav-capture-inner" style="display:flex;align-items:center;justify-content:center;">
        <svg width="22" height="22" fill="none" stroke="#000" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
        </svg>
      </div>
    </a>

    <!-- Dashboard -->
    <a href="dashboard.php" id="nav-dashboard" class="nav-item <?= $currentPage==='dashboard.php'?'active':'' ?>">
      <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M3.75 3h6.5v6.5H3.75V3zM3.75 14.5h6.5V21H3.75v-6.5zM13.75 3h6.5v6.5h-6.5V3zM13.75 14.5h6.5V21h-6.5v-6.5z"/>
      </svg>
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
    <a href="chat.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= $currentPage==='chat.php'?'bg-[#FFD60A]/10 text-[#FFD60A]':'text-[#666] hover:text-white hover:bg-white/5' ?>">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
      <span class="font-600 text-sm">Chat</span>
    </a>
    <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= $currentPage==='dashboard.php'?'bg-[#FFD60A]/10 text-[#FFD60A]':'text-[#666] hover:text-white hover:bg-white/5' ?>">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
      <span class="font-600 text-sm">Dashboard</span>
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
