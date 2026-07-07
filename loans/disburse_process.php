<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['disburse'])) {
    header('Location: disburse.php'); exit;
}

$requestId = $_POST['request_id'];
$groupId = $_POST['group_id'];
$totalRepayable = $_POST['total_repayable'];
$dueDate = $_POST['due_date'];
$interestRate = $_POST['interest_rate'];

try {
    // Get request details
    $stmt = $pdo->prepare("SELECT lr.*, gm.member_id as member_code, gm.id as membership_id, u.fullname, u.id as user_id 
                           FROM loan_requests lr 
                           JOIN group_members gm ON lr.member_id = gm.id 
                           JOIN users u ON gm.user_id = u.id 
                           WHERE lr.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request || $request['status'] !== 'approved') {
        header('Location: disburse.php?group_id=' . $groupId); exit;
    }

    // Create the actual loan record
    $stmt = $pdo->prepare("INSERT INTO loans (member_id, group_id, amount, interest_rate, total_repayable, due_date, purpose, status, approved_by, approved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())");
    $stmt->execute([
        $request['membership_id'], 
        $groupId, 
        $request['amount'], 
        $interestRate, 
        $totalRepayable, 
        $dueDate, 
        $request['purpose'],
        $current_user_id
    ]);
    $loanId = $pdo->lastInsertId();

    // Update loan request
    $stmt = $pdo->prepare("UPDATE loan_requests SET status = 'disbursed', disbursed_by = ?, disbursed_at = NOW(), loan_id = ? WHERE id = ?");
    $stmt->execute([$current_user_id, $loanId, $requestId]);

    // Log
    $desc = "Loan disbursed to {$request['fullname']} ({$request['member_code']}): " . number_format($request['amount']) . " RWF";
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, created_at) VALUES (?, ?, 'loan_disbursed', ?, NOW())");
    $stmt->execute([$current_user_id, $groupId, $desc]);

    // Notify member
    createNotification($pdo, $request['user_id'], 'Loan Disbursed', "Your loan of " . number_format($request['amount']) . " RWF has been disbursed. Total to repay: " . number_format($totalRepayable) . " RWF by " . date('d M Y', strtotime($dueDate)), 'success');

    header('Location: disburse.php?group_id=' . $groupId . '&msg=disbursed'); exit;

} catch (PDOException $e) {
    error_log("Disburse error: " . $e->getMessage());
    header('Location: disburse.php?group_id=' . $groupId); exit;
}