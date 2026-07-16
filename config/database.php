<?php
$host = 'localhost';
$dbname = 'karel_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('<div style="padding:20px;background:#09090b;color:#f87171;font-family:monospace;border-radius:12px;margin:20px;">
        <strong>Database Error:</strong> ' . $e->getMessage() . '
        <br><small>Pastikan MySQL berjalan dan database <code>karel_db</code> sudah dibuat.</small>
    </div>');
}
?>
