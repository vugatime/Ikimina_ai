<?php
$dbHost = getenv('MYSQLHOST') ?: 'localhost';
$dbPort = getenv('MYSQLPORT') ?: '3306';
$dbUser = getenv('MYSQLUSER') ?: 'root';
$dbPass = getenv('MYSQLPASSWORD') ?: '';
$dbName = getenv('MYSQLDATABASE') ?: 'ikimina_ai';

// Override host with proxy for external connections
if ($dbHost == 'mysql.railway.internal') {
    $dbHost = 'reseau.proxy.rlwy.net';
    $dbPort = '53655';
}

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
