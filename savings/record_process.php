<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['record_savings'])) {
    header('Location: record.php'); exit;
}

$groupId = $_POST['group_id'];
$membershipId = $_POST['member_id'];
$amount = $_POST['amount'];
$paymentDate = $_POST['payment_date'];
$savingsType = $_POST['savings_type'];
$notes = trim($_POST['notes']);

if (empty($groupId) || empty($membershipId) || empty($amount)) {
    header('Location: record.php?group_id=' . $groupId . '&msg=error'); exit;
}

try {
    // Get group rules
    $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();

    // Get member info
    $stmt = $pdo->prepare("SELECT gm.*, u.fullname, u.email FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.id = ?");
    $stmt->execute([$membershipId]);
    $member = $stmt->fetch();

    if (!$group || !$member) {
        header('Location: record.php?group_id=' . $groupId . '&msg=error'); exit;
    }

    // Check if payment is late
    $isLate = false;
    $penaltyAmount = 0;
    $expectedDay = strtolower($group['expected_day']);
    $currentDay = date('l', strtotime($paymentDate));
    $currentDate = date('d', strtotime($paymentDate));
    
    // Simple late check: if expected day is a number (like "5" for 5th of month)
    if (is_numeric($expectedDay)) {
        if (intval($currentDate) > intval($expectedDay) + intval($group['grace_period_days'])) {
            $isLate = true;
        }
    }

    if ($isLate && $group['late_penalty_value'] > 0) {
        if ($group['late_penalty_type'] == 'fixed') {
            $penaltyAmount = $group['late_penalty_value'];
        } else {
            $penaltyAmount = ($amount * $group['late_penalty_value']) / 100;
        }
    }

    // Record savings
    $stmt = $pdo->prepare("INSERT INTO savings (member_id, group_id, amount, savings_type, payment_date, recorded_by, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$membershipId, $groupId, $amount, $savingsType, $paymentDate, $current_user_id, $notes]);

    // Log activity
    $desc = "{$member['fullname']} ({$member['member_id']}) saved " . number_format($amount) . " RWF";
    if ($isLate) {
        $desc .= " (Late - Penalty: " . number_format($penaltyAmount) . " RWF)";
    }
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, new_data, created_at) VALUES (?, ?, 'savings_recorded', ?, ?, NOW())");
    $stmt->execute([$current_user_id, $groupId, $desc, json_encode(['amount' => $amount, 'member' => $member['member_id'], 'late' => $isLate])]);

    // Create notification for member
    createNotification($pdo, $member['user_id'], 'Savings Recorded', "Your savings of " . number_format($amount) . " RWF has been recorded.", 'success');

    // Send email if member has email
    if (!empty($member['email']) && filter_var($member['email'], FILTER_VALIDATE_EMAIL)) {
        $subject = 'Savings Recorded: ' . number_format($amount) . ' RWF';
        $subtitle = 'Your contribution has been recorded.';
        $content = "<p>Hello <strong>{$member['fullname']}</strong>,</p>
                    <p>Amount: <strong>" . number_format($amount) . " RWF</strong><br>Date: {$paymentDate}<br>Type: " . ucfirst($savingsType) . "</p>
                    " . ($isLate ? "<p style='color:#f59e0b;'>Late payment penalty: " . number_format($penaltyAmount) . " RWF</p>" : "") . "
                    <p>Group: {$group['group_name']}</p>";
        $body = emailTemplate($subject, $subtitle, $content, '', '');
        queueAndSendEmail($pdo, $member['user_id'], $member['email'], $subject, $body);
    }

    $msg = $isLate ? 'late' : 'recorded';
    header('Location: record.php?group_id=' . $groupId . '&msg=' . $msg); exit;

} catch (PDOException $e) {
    error_log("Record savings error: " . $e->getMessage());
    header('Location: record.php?group_id=' . $groupId . '&msg=error'); exit;
}