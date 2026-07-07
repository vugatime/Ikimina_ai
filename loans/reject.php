<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

$loanId = $_GET['id'] ?? null;
$groupId = $_GET['group_id'] ?? null;

if (!$loanId || !$groupId) { header('Location: manage.php'); exit; }

try {
    $stmt = $pdo->prepare("UPDATE loans SET status = 'rejected' WHERE id = ? AND group_id = ?");
    $stmt->execute([$loanId, $groupId]);

    $stmt = $pdo->prepare("SELECT l.*, gm.member_id, u.fullname FROM loans l JOIN group_members gm ON l.member_id = gm.id JOIN users u ON gm.user_id = u.id WHERE l.id = ?");
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch();

    $desc = "Loan rejected for {$loan['fullname']} ({$loan['member_id']}): " . number_format($loan['amount']) . " RWF";
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, created_at) VALUES (?, ?, 'loan_rejected', ?, NOW())");
    $stmt->execute([$current_user_id, $groupId, $desc]);

    createNotification($pdo, $loan['user_id'] ?? 0, 'Loan Rejected', "Your loan of " . number_format($loan['amount']) . " RWF was not approved.", 'warning');

    header('Location: manage.php?group_id=' . $groupId . '&msg=rejected'); exit;
} catch (PDOException $e) {
    error_log("Loan reject error: " . $e->getMessage());
    header('Location: manage.php?group_id=' . $groupId); exit;
}