<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php'); exit;
}

$action = $_POST['action'] ?? '';
$uid = $current_user_id;

if ($action === 'update_profile') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($fullname) || empty($email)) {
        header('Location: profile.php?msg=empty'); exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->execute([$fullname, $email, $phone, $uid]);

    $_SESSION['user_name'] = $fullname;
    $_SESSION['user_email'] = $email;

    header('Location: profile.php?msg=updated'); exit;
}

if ($action === 'change_password') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (strlen($newPassword) < 6) {
        header('Location: profile.php?msg=password_short'); exit;
    }
    if ($newPassword !== $confirmPassword) {
        header('Location: profile.php?msg=password_mismatch'); exit;
    }

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    if (!password_verify($currentPassword, $user['password'])) {
        header('Location: profile.php?msg=password_mismatch'); exit;
    }

    // Update password
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $uid]);

    header('Location: profile.php?msg=password_changed'); exit;
}

header('Location: profile.php');