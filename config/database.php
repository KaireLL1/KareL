<?php
// Railway environment variables (otomatis diisi Railway)
$host     = getenv('MYSQLHOST')     ?: 'localhost';
$dbname   = getenv('MYSQLDATABASE') ?: 'karel_db';
$username = getenv('MYSQLUSER')     ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$port     = getenv('MYSQLPORT')     ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('<div style="padding:20px;background:#09090b;color:#f87171;font-family:monospace;border-radius:12px;margin:20px;">
        <strong>Database Error:</strong> ' . $e->getMessage() . '
    </div>');
}
?>
