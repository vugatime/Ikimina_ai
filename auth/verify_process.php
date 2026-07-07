<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['verify'])) {
    header('Location: ../forgot_password.php');
    exit;
}

$code = trim($_POST['code']);
$email = $_SESSION['reset_email'] ?? '';

if (empty($code) || empty($email)) {
    header('Location: ../forgot_password.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, reset_token, reset_expires FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && $user['reset_token'] && strtotime($user['reset_expires']) > time()) {
        if (password_verify($code, $user['reset_token'])) {
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_user_id'] = $user['id'];
            header('Location: ../reset_password.php');
            exit;
        }
    }
    
    header('Location: ../verify_code.php?error=invalid');
    exit;
} catch (PDOException $e) {
    header('Location: ../verify_code.php?error=invalid');
    exit;
}