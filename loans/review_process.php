<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: review.php'); exit;
}

$requestId = $_POST['request_id'];
$groupId = $_POST['group_id'];
$action = $_POST['action'];
$decision = $_POST['decision'];
$reviewNotes = trim($_POST['review_notes']);

if (empty($requestId) || !in_array($decision, ['approve', 'reject'])) {
    header('Location: review.php?group_id=' . $groupId); exit;
}

try {
    // Get request details
    $stmt = $pdo->prepare("SELECT lr.*, gm.member_id as member_code, u.fullname, u.id as user_id, lp.product_name, lp.interest_rate, lp.interest_type 
                           FROM loan_requests lr 
                           JOIN group_members gm ON lr.member_id = gm.id 
                           JOIN users u ON gm.user_id = u.id 
                           LEFT JOIN loan_products lp ON lr.loan_product_id = lp.id 
                           WHERE lr.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) { header('Location: review.php?group_id=' . $groupId); exit; }

    if ($decision === 'approve') {
        // Update request status
        $stmt = $pdo->prepare("UPDATE loan_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$current_user_id, $reviewNotes, $current_user_id, $requestId]);

        // Log
        $desc = "Loan approved for {$request['fullname']} ({$request['member_code']}): " . number_format($request['amount']) . " RWF";
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, created_at) VALUES (?, ?, 'loan_approved', ?, NOW())");
        $stmt->execute([$current_user_id, $groupId, $desc]);

        // Notify member
        createNotification($pdo, $request['user_id'], 'Loan Approved', "Your loan request for " . number_format($request['amount']) . " RWF has been approved. Treasurer will disburse soon.", 'success');

        // Notify treasurer
        $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? AND role_in_group = 'treasurer' AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$groupId]);
        $treasurer = $stmt->fetch();
        if ($treasurer) {
            createNotification($pdo, $treasurer['user_id'], 'Disburse Loan', "Loan approved for {$request['fullname']}. Please disburse " . number_format($request['amount']) . " RWF.", 'info');
        }

        header('Location: review.php?group_id=' . $groupId . '&msg=approved'); exit;

    } else {
        // Reject
        $stmt = $pdo->prepare("UPDATE loan_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
        $stmt->execute([$current_user_id, $reviewNotes, $requestId]);

        // Log
        $desc = "Loan rejected for {$request['fullname']} ({$request['member_code']}): " . number_format($request['amount']) . " RWF";
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, created_at) VALUES (?, ?, 'loan_rejected', ?, NOW())");
        $stmt->execute([$current_user_id, $groupId, $desc]);

        // Notify member
        createNotification($pdo, $request['user_id'], 'Loan Rejected', "Your loan request for " . number_format($request['amount']) . " RWF was not approved. Reason: " . ($reviewNotes ?: 'Not specified'), 'warning');

        header('Location: review.php?group_id=' . $groupId . '&msg=rejected'); exit;
    }

} catch (PDOException $e) {
    error_log("Review process error: " . $e->getMessage());
    header('Location: review.php?group_id=' . $groupId); exit;
}