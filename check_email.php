<?php
require_once __DIR__ . '/config/database.php';
$email = 'vugatime@gmail.com';
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
if ($user) {
    echo "Found: " . $user['fullname'] . " (ID: " . $user['id'] . ")";
} else {
    echo "Email NOT found. Database is: " . DB_NAME;
}