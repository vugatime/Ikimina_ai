<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

if (!isset($_POST['register'])) {
    header('Location: ../register.php');
    exit;
}

$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$role = $_POST['role'];
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

// Validate
if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
    header('Location: ../register.php?error=empty');
    exit;
}
if ($password !== $confirm) {
    header('Location: ../register.php?error=mismatch');
    exit;
}
if (!in_array($role, ['group_admin', 'treasurer', 'member'])) {
    header('Location: ../register.php?error=role');
    exit;
}

try {
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../register.php?error=exists');
        exit;
    }

    // Insert user
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
    $stmt->execute([$fullname, $email, $hash, $phone, $role]);
    
    $newUserId = $pdo->lastInsertId();

    // Set session
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['user_name'] = $fullname;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['last_activity'] = time();

    // Send notifications and emails
    createNotification($pdo, $newUserId, 'Account Created', 'Welcome to IkiminaAI! Create your first savings group.', 'success');

    $subject = 'Welcome to IkiminaAI, ' . explode(' ', $fullname)[0] . '!';
    $subtitle = 'Your account has been created successfully.';
    $content = "<p>Hello <strong>{$fullname}</strong>,</p>
                <p>Welcome to IkiminaAI — the smart platform for managing community savings groups in Rwanda.</p>
                <p><strong>Your details:</strong><br>Email: {$email}<br>Phone: {$phone}<br>Role: " . ucfirst(str_replace('_', ' ', $role)) . "</p>
                <p>Start by creating a savings group and inviting members.</p>";
    $body = emailTemplate($subject, $subtitle, $content, APP_URL . '/login.php', 'Login to Your Account');
    queueAndSendEmail($pdo, $newUserId, $email, $subject, $body);

    // Notify super admin
    $stmt = $pdo->query("SELECT id, email FROM users WHERE role = 'super_admin' LIMIT 1");
    $superAdmin = $stmt->fetch();
    if ($superAdmin) {
        createNotification($pdo, $superAdmin['id'], 'New Registration', "{$fullname} registered as " . ucfirst(str_replace('_', ' ', $role)), 'info');
        $adminSubject = 'New User: ' . $fullname;
        $adminSubtitle = 'A new user joined IkiminaAI.';
        $adminContent = "<p><strong>{$fullname}</strong><br>Email: {$email}<br>Phone: {$phone}<br>Role: " . ucfirst(str_replace('_', ' ', $role)) . "</p>";
        $adminBody = emailTemplate($adminSubject, $adminSubtitle, $adminContent, APP_URL . '/dashboard.php', 'View Dashboard');
        queueAndSendEmail($pdo, $superAdmin['id'], $superAdmin['email'], $adminSubject, $adminBody);
    }

    header('Location: ../dashboard.php?welcome=1');
    exit;

} catch (PDOException $e) {
    error_log("Register error: " . $e->getMessage());
    header('Location: ../register.php?error=server');
    exit;
}