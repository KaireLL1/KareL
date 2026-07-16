<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$currentUser = getCurrentUser($pdo);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption   = trim($_POST['caption']    ?? '');
    $imageData =      $_POST['image_data'] ?? '';
    if (empty($imageData)) { $error = 'Foto tidak boleh kosong!'; }
    else {
        $imageData  = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
        $imageBytes = base64_decode($imageData);
        if (!$imageBytes) { $error = 'Data foto tidak valid!'; }
        else {
            $dir = __DIR__ . '/uploads/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename   = 'post_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
            $publicPath = 'uploads/' . $filename;
            if (file_put_contents($dir . $filename, $imageBytes)) {
                $s = $pdo->prepare("INSERT INTO posts (user_id, image_path, caption) VALUES (?,?,?)");
                $s->execute([$_SESSION['user_id'], $publicPath, $caption ?: null]);
                header('Location: index.php?posted=1'); exit;
            } else { $error = 'Gagal menyimpan. Cek permission folder uploads/'; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>KareL — Kamera</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    * { font-family:'Plus Jakarta Sans',sans-serif; box-sizing:border-box; margin:0; padding:0; }
    body { background:#000; color:#fff; height:100vh; overflow:hidden; }
    .k-input { width:100%; background:#1a1a1a; border:1.5px solid #2a2a2a; border-radius:14px; padding:14px 16px; color:#fff; font-size:15px; }
    .k-input:focus { outline:none; border-color:#FFD60A; }
    .k-input::placeholder { color:#444; }
  </style>
</head>
<body>

<!-- Fullscreen Camera -->
<div id="cam-screen" style="position:fixed;inset:0;background:#000;z-index:10;">

  <!-- Video / Preview -->
  <video id="video-stream" autoplay playsinline muted
    style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"></video>
  <img id="preview-img" src="" alt="Preview"
    style="display:none;position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
  <canvas id="capture-canvas" style="display:none;"></canvas>

  <!-- Cam unavailable -->
  <div id="cam-error" style="display:none;position:absolute;inset:0;background:#0a0a0a;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:32px;">
    <svg width="56" height="56" fill="none" stroke="#333" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:16px;">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M12 18.75H4.5a2.25 2.25 0 01-2.25-2.25V9m12.841 9.091L16.5 19.5m-1.409-1.409c.407-.407.659-.97.659-1.591v-9a2.25 2.25 0 00-2.25-2.25h-9c-.621 0-1.184.252-1.591.659m12.182 12.182L2.909 5.909M1.5 4.5l1.409 1.409"/>
    </svg>
    <p style="font-size:16px;font-weight:700;color:#666;">Kamera tidak bisa diakses</p>
    <p style="font-size:13px;color:#444;margin-top:6px;">Izinkan akses kamera di browser</p>
  </div>

  <!-- Top bar -->
  <div style="position:absolute;top:0;left:0;right:0;padding:max(env(safe-area-inset-top),20px) 20px 0;display:flex;align-items:center;justify-content:space-between;z-index:20;">
    <a href="index.php" id="cam-back-btn"
       style="width:40px;height:40px;background:rgba(0,0,0,.5);backdrop-filter:blur(10px);border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;">
      <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <span style="font-size:16px;font-weight:800;"><span style="color:#FFD60A;">Kare</span>L</span>
    <!-- Flip camera -->
    <button id="flip-btn" onclick="flipCamera()"
       style="width:40px;height:40px;background:rgba(0,0,0,.5);backdrop-filter:blur(10px);border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;">
      <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
    </button>
  </div>

  <?php if ($error): ?>
  <div id="cam-err-msg" style="position:absolute;top:100px;left:16px;right:16px;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4);color:#f87171;border-radius:14px;padding:12px 16px;font-size:14px;z-index:20;text-align:center;">
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- Bottom controls -->
  <div id="capture-controls" style="position:absolute;bottom:0;left:0;right:0;padding:0 32px max(env(safe-area-inset-bottom),24px);display:flex;align-items:center;justify-content:center;z-index:20;padding-bottom:max(env(safe-area-inset-bottom),32px);">
    <!-- Capture button -->
    <button id="capture-btn" onclick="capturePhoto()"
      style="width:80px;height:80px;border-radius:50%;background:#fff;border:5px solid rgba(255,255,255,.3);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .1s;box-shadow:0 0 0 3px rgba(255,255,255,.15);">
      <div style="width:64px;height:64px;border-radius:50%;background:#fff;"></div>
    </button>
  </div>

  <!-- Retake button (shown after capture) -->
  <button id="retake-btn" onclick="retakePhoto()"
    style="display:none;position:absolute;bottom:max(env(safe-area-inset-bottom),32px);left:40px;z-index:20;background:rgba(0,0,0,.6);backdrop-filter:blur(10px);border:1.5px solid rgba(255,255,255,.15);color:#fff;padding:10px 20px;border-radius:100px;font-size:14px;font-weight:700;cursor:pointer;">
    ↩ Ulangi
  </button>

</div>

<!-- Caption Sheet (slides up after photo) -->
<div id="caption-sheet"
  style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:50;background:rgba(0,0,0,.8);backdrop-filter:blur(20px);border-top:1px solid #1a1a1a;border-radius:28px 28px 0 0;padding:20px 20px max(env(safe-area-inset-bottom),24px);">
  <div style="width:36px;height:4px;background:#333;border-radius:4px;margin:0 auto 20px;"></div>
  <form method="POST" id="post-form">
    <input type="hidden" id="image-data-input" name="image_data">
    <textarea id="caption-input" name="caption" rows="2"
      class="k-input" placeholder="Lagi ngapain nih... (opsional)"
      style="resize:none;font-size:15px;"></textarea>
    <button id="share-btn" type="submit"
      style="width:100%;margin-top:12px;background:#FFD60A;color:#000;border:none;border-radius:100px;padding:16px;font-size:16px;font-weight:800;cursor:pointer;">
      Share Momen 🚀
    </button>
  </form>
</div>

<script>
let videoStream = null;
let facingMode  = 'environment';

const video       = document.getElementById('video-stream');
const previewImg  = document.getElementById('preview-img');
const canvas      = document.getElementById('capture-canvas');
const captureBtn  = document.getElementById('capture-btn');
const retakeBtn   = document.getElementById('retake-btn');
const captureCtrl = document.getElementById('capture-controls');
const sheet       = document.getElementById('caption-sheet');
const imgInput    = document.getElementById('image-data-input');
const camError    = document.getElementById('cam-error');

async function startCam(facing) {
  try {
    if (videoStream) videoStream.getTracks().forEach(t=>t.stop());
    const stream = await navigator.mediaDevices.getUserMedia({
      video:{ facingMode:facing, width:{ideal:1280}, height:{ideal:1280} }, audio:false
    });
    videoStream = stream;
    video.srcObject = stream;
    video.style.display = 'block';
    camError.style.display = 'none';
  } catch(e) {
    console.warn(e);
    if (facing==='environment') {
      try {
        const s = await navigator.mediaDevices.getUserMedia({video:true,audio:false});
        videoStream=s; video.srcObject=s; facingMode='user';
        camError.style.display='none';
      } catch { camError.style.display='flex'; }
    } else { camError.style.display='flex'; }
  }
}

function flipCamera() {
  facingMode = facingMode==='environment'?'user':'environment';
  startCam(facingMode);
}

function capturePhoto() {
  if (!videoStream) return;
  captureBtn.style.transform = 'scale(.88)';
  setTimeout(()=>captureBtn.style.transform='',150);

  const w=video.videoWidth, h=video.videoHeight;
  canvas.width=w; canvas.height=h;
  const ctx=canvas.getContext('2d');
  if (facingMode==='user') { ctx.translate(w,0); ctx.scale(-1,1); }
  ctx.drawImage(video,0,0,w,h);

  const data = canvas.toDataURL('image/jpeg',.85);
  imgInput.value = data;
  previewImg.src = data;
  previewImg.style.display = 'block';
  video.style.display = 'none';
  captureCtrl.style.display = 'none';
  retakeBtn.style.display = 'block';
  sheet.style.display = 'block';
}

function retakePhoto() {
  previewImg.style.display = 'none';
  video.style.display = 'block';
  captureCtrl.style.display = 'flex';
  retakeBtn.style.display = 'none';
  sheet.style.display = 'none';
  imgInput.value = '';
}

startCam(facingMode);
</script>
</body>
</html>
