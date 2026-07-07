<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    header('Location: ../login.php?error=invalid');
    exit;
}

try {
    // Find user by email, phone, or member_id
    $stmt = $pdo->prepare("
        SELECT u.id, u.fullname, u.email, u.password, u.role, u.status 
        FROM users u 
        LEFT JOIN group_members gm ON u.id = gm.user_id AND gm.deleted_at IS NULL
        WHERE u.email = ? OR u.phone = ? OR gm.member_id = ?
        LIMIT 1
    ");
    $stmt->execute([$login, $login, $login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password']) && $user['status'] === 'active') {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        
        header('Location: ../dashboard.php');
        exit;
    } else {
        header('Location: ../login.php?error=invalid');
        exit;
    }
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    header('Location: ../login.php?error=invalid');
    exit;
}