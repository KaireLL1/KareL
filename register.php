<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $pass     = $_POST['password']  ?? '';
    $pass2    = $_POST['password2'] ?? '';
    if (!$username||!$email||!$pass) { $error = 'Semua field wajib diisi!'; }
    elseif (strlen($username)<3) { $error = 'Username minimal 3 karakter!'; }
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $error = 'Format email tidak valid!'; }
    elseif (strlen($pass)<6) { $error = 'Password minimal 6 karakter!'; }
    elseif ($pass!==$pass2) { $error = 'Password tidak cocok!'; }
    else {
        $s = $pdo->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $s->execute([$email,$username]);
        if ($s->fetch()) { $error = 'Email atau username sudah dipakai!'; }
        else {
            $s = $pdo->prepare("INSERT INTO users (username,email,password) VALUES (?,?,?)");
            $s->execute([$username,$email,password_hash($pass,PASSWORD_DEFAULT)]);
            $success = 'Akun berhasil dibuat!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KareL — Daftar</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    * { font-family:'Plus Jakarta Sans',sans-serif; box-sizing:border-box; }
    body { background:#000; color:#fff; min-height:100vh; }
    .k-input { width:100%; background:#111; border:1.5px solid #222; border-radius:14px; padding:15px 16px; color:#fff; font-size:15px; transition:border-color .2s; }
    .k-input:focus { outline:none; border-color:#FFD60A; }
    .k-input::placeholder { color:#444; }
    .k-btn { background:#FFD60A; color:#000; font-weight:800; border-radius:100px; border:none; cursor:pointer; width:100%; padding:16px; font-size:16px; transition:opacity .15s, transform .1s; }
    .k-btn:hover { opacity:.9; }
    .k-btn:active { transform:scale(.97); }
  </style>
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;">

  <div style="position:fixed;inset:0;overflow:hidden;pointer-events:none;">
    <div style="position:absolute;top:20%;left:50%;transform:translateX(-50%);width:400px;height:400px;background:rgba(255,214,10,.06);border-radius:50%;filter:blur(80px);"></div>
  </div>

  <div style="width:100%;max-width:360px;position:relative;">
    <div style="text-align:center;margin-bottom:36px;">
      <div style="width:80px;height:80px;background:#FFD60A;border-radius:26px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 0 40px rgba(255,214,10,.3);">
        <svg width="42" height="42" fill="none" stroke="#000" stroke-width="2.2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
        </svg>
      </div>
      <h1 style="font-size:32px;font-weight:900;letter-spacing:-1.5px;"><span style="color:#FFD60A;">Kare</span>L</h1>
      <p style="color:#555;font-size:14px;margin-top:4px;">Bergabung dan share momenmu</p>
    </div>

    <?php if ($error): ?>
    <div id="reg-error" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;border-radius:14px;padding:12px 16px;margin-bottom:16px;font-size:14px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div id="reg-success" style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#4ade80;border-radius:14px;padding:12px 16px;margin-bottom:16px;font-size:14px;">
      <?= htmlspecialchars($success) ?> — <a href="login.php" style="color:#FFD60A;font-weight:700;">Masuk sekarang</a>
    </div>
    <?php endif; ?>

    <form method="POST" id="register-form" style="display:flex;flex-direction:column;gap:12px;">
      <input id="username-input" class="k-input" type="text" name="username" placeholder="Username" required value="<?= htmlspecialchars($_POST['username']??'') ?>">
      <input id="reg-email-input" class="k-input" type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
      <input id="reg-pass-input" class="k-input" type="password" name="password" placeholder="Password (min. 6 karakter)" required>
      <input id="reg-pass2-input" class="k-input" type="password" name="password2" placeholder="Ulangi password" required>
      <div style="margin-top:4px;">
        <button id="register-btn" class="k-btn" type="submit">Buat Akun</button>
      </div>
    </form>

    <p style="text-align:center;color:#444;font-size:14px;margin-top:24px;">
      Sudah punya akun?
      <a id="to-login" href="login.php" style="color:#FFD60A;font-weight:700;text-decoration:none;"> Masuk</a>
    </p>
  </div>
</body>
</html>
