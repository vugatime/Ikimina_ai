<?php
$dbHost = 'reseau.proxy.rlwy.net';
$dbPort = '53655';
$dbUser = 'root';
$dbPass = 'CiTjTLdJzANGZkJEuhCSfsglVycowXCb';
$dbName = 'railway';
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . $dbHost . ";port=" . $dbPort . ";dbname=" . $dbName . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
