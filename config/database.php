<?php
$mysqlUrl = getenv('MYSQL_URL');

if ($mysqlUrl) {
    $url = parse_url($mysqlUrl);
    define('DB_HOST', $url['host']);
    define('DB_PORT', $url['port'] ?? 3306);
    define('DB_USER', $url['user']);
    define('DB_PASS', $url['pass']);
    define('DB_NAME', ltrim($url['path'], '/'));
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ikimina_ai');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}
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
    die("Database connection failed.");
}
