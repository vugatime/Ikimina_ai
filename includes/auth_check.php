<?php
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /Ikimina_ai/auth.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'] ?? 'User';
$current_user_role = $_SESSION['user_role'] ?? 'member';

// Get actual group role
$groupRole = null;
$groupId = null;
if ($current_user_role !== 'super_admin') {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare("SELECT role_in_group, group_id FROM group_members WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$current_user_id]);
    $member = $stmt->fetch();
    if ($member) {
        $groupRole = $member['role_in_group'];
        $groupId = $member['group_id'];
    }
}

// Use group role if available
$effective_role = $groupRole ?: $current_user_role;