<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['add_member'])) {
    header('Location: manage.php'); exit;
}

$groupId = $_POST['group_id'];
$fullname = trim($_POST['fullname']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$roleInGroup = $_POST['role_in_group'];

if (empty($fullname) || empty($phone)) {
    header('Location: add.php?group_id=' . $groupId . '&error=empty'); exit;
}
if (!in_array($roleInGroup, ['member', 'treasurer', 'group_admin'])) {
    $roleInGroup = 'member';
}
if (empty($email)) {
    $email = $phone . '@ikimina.rw';
}

try {
    // Check if user exists by phone or email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR email = ? LIMIT 1");
    $stmt->execute([$phone, $email]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        $userId = $existingUser['id'];
    } else {
        // Create new user account
        $password = password_hash('ikimina123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, 'member', 'active', NOW())");
        $stmt->execute([$fullname, $email, $password, $phone]);
        $userId = $pdo->lastInsertId();
    }

    // Check if already a member
    $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ? AND deleted_at IS NULL");
    $stmt->execute([$groupId, $userId]);
    if ($stmt->fetch()) {
        header('Location: manage.php?group_id=' . $groupId . '&error=exists'); exit;
    }

    // Check if previously removed (restore)
    $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ? AND deleted_at IS NOT NULL");
    $stmt->execute([$groupId, $userId]);
    $restored = $stmt->fetch();

    if ($restored) {
        $stmt = $pdo->prepare("UPDATE group_members SET role_in_group = ?, deleted_at = NULL WHERE id = ?");
        $stmt->execute([$roleInGroup, $restored['id']]);
        $membershipId = $restored['id'];
        $action = 'member_restored';
        $desc = "{$fullname} was restored to the group as " . ucfirst(str_replace('_', ' ', $roleInGroup));
    } else {
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role_in_group, joined_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$groupId, $userId, $roleInGroup]);
        $membershipId = $pdo->lastInsertId();
        $action = 'member_added';
        $desc = "{$fullname} was added as " . ucfirst(str_replace('_', ' ', $roleInGroup));
    }

    // Log activity
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, new_data, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$current_user_id, $groupId, $action, $desc, json_encode(['name' => $fullname, 'phone' => $phone, 'role' => $roleInGroup])]);

    // Notify
    createNotification($pdo, $current_user_id, 'Member Added', $desc, 'success');

    // Send email to member
    $stmt = $pdo->prepare("SELECT group_name FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupName = $stmt->fetchColumn();

    $subject = 'You Have Been Added to ' . $groupName;
    $subtitle = 'Welcome to the savings group.';
    $content = "<p>Hello <strong>{$fullname}</strong>,</p><p>You have been added to <strong>{$groupName}</strong> as a " . ucfirst(str_replace('_', ' ', $roleInGroup)) . ".</p><p>Login with your phone number ({$phone}) and default password: <strong>ikimina123</strong></p>";
    $body = emailTemplate($subject, $subtitle, $content, APP_URL . '/login.php', 'Login Now');
    queueAndSendEmail($pdo, $userId, $email, $subject, $body);

    header('Location: manage.php?group_id=' . $groupId . '&added=1'); exit;
} catch (PDOException $e) {
    error_log("Add member error: " . $e->getMessage());
    header('Location: add.php?group_id=' . $groupId . '&error=server'); exit;
}