<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['forgot'])) {
    header('Location: ../forgot_password.php');
    exit;
}

$email = trim($_POST['email']);

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../forgot_password.php?error=email');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
        $stmt->execute([
            ':token'   => password_hash($otp, PASSWORD_DEFAULT),
            ':expires' => $expires,
            ':id'      => $user['id']
        ]);
        
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        
        header('Location: ../verify_code.php');
        exit;
    } else {
        header('Location: ../forgot_password.php?sent=1');
        exit;
    }
} catch (PDOException $e) {
    header('Location: ../forgot_password.php?sent=1');
    exit;
}