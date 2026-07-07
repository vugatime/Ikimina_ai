<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

$loanId = $_GET['id'] ?? null;
$groupId = $_GET['group_id'] ?? null;

if (!$loanId || !$groupId) { header('Location: manage.php'); exit; }

try {
    $stmt = $pdo->prepare("UPDATE loans SET status = 'active', approved_by = ?, approved_at = NOW() WHERE id = ? AND group_id = ?");
    $stmt->execute([$current_user_id, $loanId, $groupId]);

    // Get loan details
    $stmt = $pdo->prepare("SELECT l.*, gm.member_id, u.fullname, u.email FROM loans l JOIN group_members gm ON l.member_id = gm.id JOIN users u ON gm.user_id = u.id WHERE l.id = ?");
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch();

    // Log
    $desc = "Loan approved for {$loan['fullname']} ({$loan['member_id']}): " . number_format($loan['amount']) . " RWF";
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, created_at) VALUES (?, ?, 'loan_approved', ?, NOW())");
    $stmt->execute([$current_user_id, $groupId, $desc]);

    // Notify member
    createNotification($pdo, $loan['user_id'] ?? 0, 'Loan Approved', "Your loan of " . number_format($loan['amount']) . " RWF has been approved.", 'success');

    if (!empty($loan['email'])) {
        $subject = 'Loan Approved: ' . number_format($loan['amount']) . ' RWF';
        $content = "<p>Hello <strong>{$loan['fullname']}</strong>,</p><p>Your loan of <strong>" . number_format($loan['amount']) . " RWF</strong> has been approved.</p><p>Total to repay: <strong>" . number_format($loan['total_repayable']) . " RWF</strong><br>Due date: {$loan['due_date']}</p>";
        $body = emailTemplate($subject, '', $content, '', '');
        queueAndSendEmail($pdo, $loan['user_id'], $loan['email'], $subject, $body);
    }

    header('Location: manage.php?group_id=' . $groupId . '&msg=approved'); exit;
} catch (PDOException $e) {
    error_log("Loan approve error: " . $e->getMessage());
    header('Location: manage.php?group_id=' . $groupId); exit;
}