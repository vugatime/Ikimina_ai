<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

$action = $_REQUEST['action'] ?? '';
$groupId = $_REQUEST['group_id'] ?? null;

if (!$groupId) { header('Location: manage.php'); exit; }

$stmt = $pdo->prepare("SELECT group_name FROM `groups` WHERE id = ?");
$stmt->execute([$groupId]);
$groupName = $stmt->fetchColumn();
$prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $groupName), 0, 3));
if (strlen($prefix) < 3) $prefix = str_pad($prefix, 3, 'X');

try {
    if ($action === 'add') {
        $fullname = trim($_POST['fullname']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role_in_group'];

        if (empty($fullname)) { header('Location: manage.php?group_id=' . $groupId); exit; }
        if (!in_array($role, ['member', 'assistant_admin', 'treasurer', 'group_admin'])) $role = 'member';

        // Password = phone number (or 'ikimina123' if no phone)
        $memberPassword = !empty($phone) ? $phone : 'ikimina123';
        $hash = password_hash($memberPassword, PASSWORD_BCRYPT);

        // Insert user first (without member_id)
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, 'member', 'active', NOW())");
        $stmt->execute([$fullname, $phone . '@ikimina.local', $hash, $phone]);
        $userId = $pdo->lastInsertId();

        // Insert group member (member_id will be NULL initially)
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role_in_group, joined_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$groupId, $userId, $role]);
        $membershipId = $pdo->lastInsertId();

        // Generate unique member_id using the auto-increment membership ID
        $uniqueMemberId = $prefix . '-' . str_pad($membershipId, 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("UPDATE group_members SET member_id = ? WHERE id = ?");
        $stmt->execute([$uniqueMemberId, $membershipId]);
        $memberId = $uniqueMemberId;

        $roleLabel = ['group_admin' => 'Group Admin', 'assistant_admin' => 'Assistant Admin', 'treasurer' => 'Treasurer', 'member' => 'Member'];
        $desc = "{$fullname} ({$memberId}) added as " . ($roleLabel[$role] ?? 'Member') . ". Login: {$memberId} / {$memberPassword}";
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, new_data, created_at) VALUES (?, ?, 'member_added', ?, ?, NOW())");
        $stmt->execute([$current_user_id, $groupId, $desc, json_encode(['name' => $fullname, 'member_id' => $memberId, 'role' => $role, 'login' => $memberId, 'password' => $memberPassword])]);

        createNotification($pdo, $current_user_id, 'Member Added', $desc, 'success');

        header('Location: manage.php?group_id=' . $groupId . '&msg=added'); exit;
    }

    elseif ($action === 'edit') {
        $membershipId = $_POST['membership_id'];
        $fullname = trim($_POST['fullname']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role_in_group'];

        if (!in_array($role, ['member', 'assistant_admin', 'treasurer', 'group_admin'])) $role = 'member';

        $stmt = $pdo->prepare("SELECT gm.*, u.fullname as old_name FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.id = ?");
        $stmt->execute([$membershipId]);
        $old = $stmt->fetch();

        // Update user info AND password (phone = password)
        if (!empty($phone)) {
            $hash = password_hash($phone, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users u JOIN group_members gm ON u.id = gm.user_id SET u.fullname = ?, u.phone = ?, u.password = ? WHERE gm.id = ?");
            $stmt->execute([$fullname, $phone, $hash, $membershipId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users u JOIN group_members gm ON u.id = gm.user_id SET u.fullname = ?, u.phone = ? WHERE gm.id = ?");
            $stmt->execute([$fullname, $phone, $membershipId]);
        }

        $stmt = $pdo->prepare("UPDATE group_members SET role_in_group = ? WHERE id = ?");
        $stmt->execute([$role, $membershipId]);

        $roleLabel = ['group_admin' => 'Group Admin', 'assistant_admin' => 'Assistant Admin', 'treasurer' => 'Treasurer', 'member' => 'Member'];
        $desc = "{$old['old_name']} updated — Role: " . ($roleLabel[$role] ?? 'Member');
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, old_data, new_data, created_at) VALUES (?, ?, 'member_updated', ?, ?, ?, NOW())");
        $stmt->execute([$current_user_id, $groupId, $desc, json_encode(['role' => $old['role_in_group']]), json_encode(['role' => $role])]);

        createNotification($pdo, $current_user_id, 'Member Updated', $desc, 'info');

        header('Location: manage.php?group_id=' . $groupId . '&msg=updated'); exit;
    }

    elseif ($action === 'remove') {
        $membershipId = $_GET['id'];

        $stmt = $pdo->prepare("SELECT gm.*, u.fullname FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.id = ?");
        $stmt->execute([$membershipId]);
        $member = $stmt->fetch();

        if ($member && $member['role_in_group'] !== 'group_admin') {
            $stmt = $pdo->prepare("UPDATE group_members SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$membershipId]);

            $desc = "{$member['fullname']} ({$member['member_id']}) removed from group";
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, old_data, created_at) VALUES (?, ?, 'member_removed', ?, ?, NOW())");
            $stmt->execute([$current_user_id, $groupId, $desc, json_encode(['name' => $member['fullname'], 'member_id' => $member['member_id']])]);

            createNotification($pdo, $current_user_id, 'Member Removed', $desc, 'warning');
        }

        header('Location: manage.php?group_id=' . $groupId . '&msg=removed'); exit;
    }

} catch (PDOException $e) {
    error_log("Member process error: " . $e->getMessage());
    header('Location: manage.php?group_id=' . $groupId); exit;
}