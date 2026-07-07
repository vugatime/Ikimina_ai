<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../register.php'); exit; }

$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
    header('Location: ../register.php?error=empty'); exit;
}
if ($password !== $confirm) {
    header('Location: ../register.php?error=mismatch'); exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) { header('Location: ../register.php?error=exists'); exit; }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, 'member', 'active', NOW())");
    $stmt->execute([$fullname, $email, $hash, $phone]);
    $newUserId = $pdo->lastInsertId();

    $_SESSION['user_id'] = $newUserId;
    $_SESSION['user_name'] = $fullname;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = 'member';
    $_SESSION['last_activity'] = time();

    createNotification($pdo, $newUserId, 'Account Created', 'Welcome to IkiminaAI! Create your first group to get started.', 'success');

    header('Location: ../dashboard.php?welcome=1'); exit;
} catch (PDOException $e) {
    header('Location: ../register.php?error=server'); exit;
}