<?php
$host = 'mysql';          
$db   = 'trainme_db';
$user = 'root';
$pass = 'superVarnoGeslo';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Ensure UTF-8 encoding (charset is already set in DSN, but we ensure it explicitly)
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}