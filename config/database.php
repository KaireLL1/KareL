<?php
// Railway: pakai MYSQL_URL jika ada, fallback ke individual vars, fallback ke localhost
$mysqlUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: null;

if ($mysqlUrl) {
    $parts    = parse_url($mysqlUrl);
    $host     = $parts['host'];
    $port     = $parts['port'] ?? 3306;
    $dbname   = ltrim($parts['path'] ?? '/railway', '/');
    $username = $parts['user'] ?? 'root';
    $password = $parts['pass'] ?? '';
} else {
    $host     = getenv('MYSQLHOST')     ?: 'localhost';
    $port     = getenv('MYSQLPORT')     ?: '3306';
    $dbname   = getenv('MYSQLDATABASE') ?: 'karel_db';
    $username = getenv('MYSQLUSER')     ?: 'root';
    $password = getenv('MYSQLPASSWORD') ?: '';
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die('<div style="padding:20px;background:#0a0a0a;color:#f87171;font-family:monospace;border-radius:12px;margin:20px;">
        <strong>DB Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
    </div>');
}
?>
