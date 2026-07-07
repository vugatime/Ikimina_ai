<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['apply_loan'])) {
    header('Location: manage.php'); exit;
}

$groupId = $_POST['group_id'];
$membershipId = $_POST['member_id'];
$loanProductId = $_POST['loan_product_id'];
$amount = $_POST['amount'];
$durationMonths = $_POST['duration_months'];
$purpose = trim($_POST['purpose']);

if (empty($groupId) || empty($membershipId) || empty($loanProductId) || empty($amount)) {
    header('Location: manage.php?group_id=' . $groupId . '&msg=error'); exit;
}

try {
    // Get loan product rules
    $stmt = $pdo->prepare("SELECT * FROM loan_products WHERE id = ? AND group_id = ?");
    $stmt->execute([$loanProductId, $groupId]);
    $product = $stmt->fetch();

    if (!$product) { header('Location: manage.php?group_id=' . $groupId . '&msg=error'); exit; }

    // Validate amount
    if ($amount > $product['max_amount']) {
        header('Location: manage.php?group_id=' . $groupId . '&msg=exceeds_max'); exit;
    }
    if ($durationMonths > $product['max_duration_months']) {
        header('Location: manage.php?group_id=' . $groupId . '&msg=exceeds_duration'); exit;
    }

    // Calculate total repayable
    if ($product['interest_type'] === 'flat') {
        $totalRepayable = $amount + ($amount * $product['interest_rate'] / 100);
    } else {
        // Monthly interest
        $totalRepayable = $amount;
        for ($i = 0; $i < $durationMonths; $i++) {
            $totalRepayable += ($totalRepayable * $product['interest_rate'] / 100);
        }
    }

    // Get member info
    $stmt = $pdo->prepare("SELECT gm.*, u.fullname, u.email FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.id = ?");
    $stmt->execute([$membershipId]);
    $member = $stmt->fetch();

    // Insert loan
    $dueDate = date('Y-m-d', strtotime('+' . $durationMonths . ' months'));
    $stmt = $pdo->prepare("INSERT INTO loans (member_id, group_id, amount, interest_rate, total_repayable, due_date, purpose, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$membershipId, $groupId, $amount, $product['interest_rate'], round($totalRepayable), $dueDate, $purpose]);

    // Log
    $desc = "{$member['fullname']} ({$member['member_id']}) applied for loan: " . number_format($amount) . " RWF";
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, new_data, created_at) VALUES (?, ?, 'loan_applied', ?, ?, NOW())");
    $stmt->execute([$current_user_id, $groupId, $desc, json_encode(['amount' => $amount, 'product' => $product['product_name']])]);

    // Notify group admin
    $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? AND role_in_group = 'group_admin' AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$groupId]);
    $admin = $stmt->fetch();
    if ($admin) {
        createNotification($pdo, $admin['user_id'], 'Loan Application', "{$member['fullname']} applied for " . number_format($amount) . " RWF loan", 'info');
    }

    header('Location: manage.php?group_id=' . $groupId . '&msg=applied'); exit;

} catch (PDOException $e) {
    error_log("Loan apply error: " . $e->getMessage());
    header('Location: manage.php?group_id=' . $groupId . '&msg=error'); exit;
}