<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
?>
