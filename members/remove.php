<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$membershipId = $_GET['id'] ?? null;
$groupId = $_GET['group_id'] ?? null;

if (!$membershipId || !$groupId) { header('Location: manage.php'); exit; }

try {
    // Get member info before removing
    $stmt = $pdo->prepare("SELECT gm.*, u.fullname FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.id = ?");
    $stmt->execute([$membershipId]);
    $member = $stmt->fetch();

    if ($member) {
        // Soft delete
        $stmt = $pdo->prepare("UPDATE group_members SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$membershipId]);

        // Log
        $desc = "{$member['fullname']} was removed from the group";
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, old_data, created_at) VALUES (?, ?, 'member_removed', ?, ?, NOW())");
        $stmt->execute([$current_user_id, $groupId, $desc, json_encode(['name' => $member['fullname'], 'role' => $member['role_in_group']])]);

        createNotification($pdo, $current_user_id, 'Member Removed', $desc, 'warning');
    }

    header('Location: manage.php?group_id=' . $groupId . '&removed=1'); exit;
} catch (PDOException $e) {
    error_log("Remove member error: " . $e->getMessage());
    header('Location: manage.php?group_id=' . $groupId); exit;
}