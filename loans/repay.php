<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

$loanId = $_GET['id'] ?? null;
$groupId = $_GET['group_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_repayment'])) {
    $amount = $_POST['amount'];
    $paymentDate = $_POST['payment_date'];
    $notes = trim($_POST['notes']);

    try {
        $stmt = $pdo->prepare("INSERT INTO loan_payments (loan_id, amount, payment_date, recorded_by, notes, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$loanId, $amount, $paymentDate, $current_user_id, $notes]);

        // Check if fully repaid
        $stmt = $pdo->prepare("SELECT l.total_repayable, COALESCE(SUM(lp.amount), 0) as total_paid FROM loans l LEFT JOIN loan_payments lp ON l.id = lp.loan_id WHERE l.id = ? GROUP BY l.id");
        $stmt->execute([$loanId]);
        $loanStatus = $stmt->fetch();

        if ($loanStatus && $loanStatus['total_paid'] >= $loanStatus['total_repayable']) {
            $stmt = $pdo->prepare("UPDATE loans SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$loanId]);
        }

        $desc = "Repayment recorded: " . number_format($amount) . " RWF";
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, created_at) VALUES (?, ?, 'loan_repayment', ?, NOW())");
        $stmt->execute([$current_user_id, $groupId, $desc]);

        header('Location: manage.php?group_id=' . $groupId . '&msg=repaid'); exit;
    } catch (PDOException $e) {
        error_log("Repay error: " . $e->getMessage());
        header('Location: manage.php?group_id=' . $groupId); exit;
    }
}

// Get loan details
$stmt = $pdo->prepare("SELECT l.*, gm.member_id, u.fullname, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) as total_repaid FROM loans l JOIN group_members gm ON l.member_id = gm.id JOIN users u ON gm.user_id = u.id WHERE l.id = ?");
$stmt->execute([$loanId]);
$loan = $stmt->fetch();

if (!$loan) { header('Location: manage.php'); exit; }

$remaining = $loan['total_repayable'] - $loan['total_repaid'];
$page_title = 'Record Repayment';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-lg text-gray-900 mb-1">Record Repayment</h3>
        <p class="text-sm text-gray-500 mb-5"><?php echo htmlspecialchars($loan['member_id'] . ' - ' . $loan['fullname']); ?></p>
        
        <div class="bg-gray-50 rounded-xl p-4 mb-5 text-sm space-y-2">
            <div class="flex justify-between"><span class="text-gray-500">Loan Amount:</span> <strong><?php echo number_format($loan['amount']); ?> RWF</strong></div>
            <div class="flex justify-between"><span class="text-gray-500">Total to Repay:</span> <strong><?php echo number_format($loan['total_repayable']); ?> RWF</strong></div>
            <div class="flex justify-between"><span class="text-gray-500">Already Repaid:</span> <strong class="text-green-600"><?php echo number_format($loan['total_repaid']); ?> RWF</strong></div>
            <div class="flex justify-between border-t pt-2"><span class="text-gray-500">Remaining:</span> <strong class="text-amber-600"><?php echo number_format($remaining); ?> RWF</strong></div>
        </div>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Amount (RWF)</label>
                <input type="number" name="amount" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo $remaining; ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Payment Date</label>
                <input type="date" name="payment_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Notes</label>
                <textarea name="notes" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Optional..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" name="record_repayment" class="flex-1 py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Record Repayment</button>
                <a href="manage.php?group_id=<?php echo $groupId; ?>" class="flex-1 py-3 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition no-underline text-center">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>