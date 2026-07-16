<?php
require_once 'config/database.php';

$results = [];
$errors  = [];

$queries = [
    'Tabel users' => "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `avatar` VARCHAR(255) DEFAULT NULL,
        `bio` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    'Tabel posts' => "CREATE TABLE IF NOT EXISTS `posts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `caption` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    'Tabel friendships' => "CREATE TABLE IF NOT EXISTS `friendships` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `requester_id` INT NOT NULL,
        `receiver_id` INT NOT NULL,
        `status` ENUM('pending','accepted','rejected') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_friendship` (`requester_id`, `receiver_id`)
    ) ENGINE=InnoDB",
];

foreach ($queries as $name => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = "✅ $name berhasil dibuat";
    } catch (PDOException $e) {
        $errors[] = "❌ $name gagal: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KareL Setup</title>
  <style>
    * { font-family: monospace; box-sizing: border-box; }
    body { background: #000; color: #fff; padding: 40px 20px; max-width: 600px; margin: 0 auto; }
    h1 { color: #FFD60A; font-size: 24px; margin-bottom: 24px; }
    .ok  { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #4ade80; padding: 12px 16px; border-radius: 8px; margin-bottom: 8px; }
    .err { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #f87171;  padding: 12px 16px; border-radius: 8px; margin-bottom: 8px; }
    .btn { display: inline-block; margin-top: 24px; background: #FFD60A; color: #000; padding: 12px 24px; border-radius: 100px; text-decoration: none; font-weight: bold; font-family: sans-serif; }
    .info { color: #555; font-size: 13px; margin-top: 20px; }
  </style>
</head>
<body>
  <h1>⚙️ KareL Setup</h1>

  <?php foreach ($results as $r): ?>
    <div class="ok"><?= $r ?></div>
  <?php endforeach; ?>

  <?php foreach ($errors as $e): ?>
    <div class="err"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <?php if (empty($errors)): ?>
    <a href="register.php" class="btn">🚀 Buka KareL</a>
    <p class="info">Hapus file setup.php setelah setup selesai.</p>
  <?php endif; ?>
</body>
</html>
