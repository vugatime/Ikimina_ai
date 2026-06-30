<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reset'])) {
    header('Location: ../forgot_password.php');
    exit;
}

if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['reset_user_id'])) {
    header('Location: ../forgot_password.php');
    exit;
}

$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

if (strlen($password) < 6) {
    header('Location: ../reset_password.php?error=short');
    exit;
}
if ($password !== $confirm) {
    header('Location: ../reset_password.php?error=mismatch');
    exit;
}

try {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_expires = NULL WHERE id = :id");
    $stmt->execute([':password' => $hash, ':id' => $_SESSION['reset_user_id']]);
    
    unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_verified'], $_SESSION['reset_user_id']);
    
    header('Location: ../login.php?reset=1');
    exit;
} catch (PDOException $e) {
    header('Location: ../reset_password.php?error=short');
    exit;
}