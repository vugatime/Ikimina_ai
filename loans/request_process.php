<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: request.php');
    exit;
}

if (!isset($_POST['submit_request'])) {
    header('Location: request.php');
    exit;
}

$groupId = $_POST['group_id'];
$membershipId = $_POST['member_id'];
$loanProductId = $_POST['loan_product_id'];
$amount = $_POST['amount'];
$durationMonths = $_POST['duration_months'];
$purpose = trim($_POST['purpose']);

if (empty($groupId) || empty($membershipId) || empty($loanProductId) || empty($amount)) {
    header('Location: request.php?msg=error');
    exit;
}

if (empty($durationMonths) || $durationMonths < 1) {
    $durationMonths = 1;
}

try {
    // Get loan product rules
    $stmt = $pdo->prepare("SELECT * FROM loan_products WHERE id = ? AND group_id = ?");
    $stmt->execute([$loanProductId, $groupId]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: request.php?msg=error');
        exit;
    }

    // Check minimum savings
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE member_id = ?");
    $stmt->execute([$membershipId]);
    $mySavings = $stmt->fetchColumn();

    if ($mySavings < $product['min_savings_required']) {
        header('Location: request.php?msg=low_savings');
        exit;
    }

    // Validate amount
    if ($amount > $product['max_amount']) {
        header('Location: request.php?msg=exceeds_max');
        exit;
    }

    // Validate duration
    if ($durationMonths > $product['max_duration_months']) {
        header('Location: request.php?msg=exceeds_duration');
        exit;
    }

    // Get member info
    $stmt = $pdo->prepare("SELECT gm.member_id, u.fullname FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.id = ?");
    $stmt->execute([$membershipId]);
    $member = $stmt->fetch();

    if (!$member) {
        header('Location: request.php?msg=error');
        exit;
    }

    // Insert loan request
    $stmt = $pdo->prepare("INSERT INTO loan_requests (group_id, member_id, loan_product_id, amount, duration_months, purpose, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$groupId, $membershipId, $loanProductId, $amount, $durationMonths, $purpose]);
    $requestId = $pdo->lastInsertId();

    // Log activity
    $desc = "{$member['fullname']} ({$member['member_id']}) requested a loan of " . number_format($amount) . " RWF";
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, new_data, created_at) VALUES (?, ?, 'loan_requested', ?, ?, NOW())");
    $stmt->execute([$current_user_id, $groupId, $desc, json_encode([
        'amount' => $amount,
        'product' => $product['product_name'],
        'duration' => $durationMonths,
        'request_id' => $requestId
    ])]);

    // Notify group admin
    $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? AND role_in_group = 'group_admin' AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$groupId]);
    $admin = $stmt->fetch();
    if ($admin) {
        createNotification(
            $pdo,
            $admin['user_id'],
            'New Loan Request',
            "{$member['fullname']} ({$member['member_id']}) requested " . number_format($amount) . " RWF. Review now.",
            'info'
        );
    }

    header('Location: request.php?msg=submitted');
    exit;

} catch (PDOException $e) {
    error_log("Loan request error: " . $e->getMessage());
    header('Location: request.php?msg=error');
    exit;
}