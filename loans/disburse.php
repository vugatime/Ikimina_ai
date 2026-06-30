<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// ROLE CHECK: Only Group Admin and Treasurer can disburse loans
if ($current_user_role === 'super_admin') { header('Location: ../dashboard.php'); exit; }
$stmt = $pdo->prepare("SELECT role_in_group FROM group_members WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
$stmt->execute([$current_user_id]);
$gr = $stmt->fetchColumn();
if (!in_array($gr, ['group_admin', 'treasurer'])) { header('Location: ../dashboard.php'); exit; }

$page_title = 'Disburse Loans';
$base_path = '../';

$stmt = $pdo->prepare("SELECT gm.group_id, gm.role_in_group FROM group_members gm WHERE gm.user_id = ? AND gm.deleted_at IS NULL LIMIT 1");
$stmt->execute([$current_user_id]);
$treasurerInfo = $stmt->fetch();

$groupId = $_GET['group_id'] ?? $treasurerInfo['group_id'] ?? null;
$msg = $_GET['msg'] ?? '';

// Get approved loans waiting for disbursement
$stmt = $pdo->prepare("SELECT lr.*, gm.member_id, u.fullname, lp.product_name, lp.interest_rate, lp.interest_type
                       FROM loan_requests lr 
                       JOIN group_members gm ON lr.member_id = gm.id 
                       JOIN users u ON gm.user_id = u.id 
                       LEFT JOIN loan_products lp ON lr.loan_product_id = lp.id 
                       WHERE lr.group_id = ? AND lr.status = 'approved'
                       ORDER BY lr.approved_at ASC");
$stmt->execute([$groupId]);
$approvedLoans = $stmt->fetchAll();

// Get disbursed loans
$stmt = $pdo->prepare("SELECT lr.*, gm.member_id, u.fullname, lp.product_name, du.fullname as disbursed_by_name
                       FROM loan_requests lr 
                       JOIN group_members gm ON lr.member_id = gm.id 
                       JOIN users u ON gm.user_id = u.id 
                       LEFT JOIN loan_products lp ON lr.loan_product_id = lp.id 
                       LEFT JOIN users du ON lr.disbursed_by = du.id
                       WHERE lr.group_id = ? AND lr.status IN ('disbursed', 'active', 'completed')
                       ORDER BY lr.disbursed_at DESC LIMIT 20");
$stmt->execute([$groupId]);
$disbursedLoans = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($msg === 'disbursed'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Loan disbursed successfully. Member can now see their active loan.
</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">Disburse Loans</h3>
    <p class="text-gray-500 text-sm mt-1">Record when approved loan money is given to members.</p>
</div>

<!-- Approved - Ready for Disbursement -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Ready for Disbursement (<?php echo count($approvedLoans); ?>)</h3></div>
    <?php if (empty($approvedLoans)): ?>
        <div class="p-10 text-center"><p class="text-gray-500 text-sm">No approved loans waiting for disbursement.</p></div>
    <?php else: ?>
        <div class="space-y-4 p-5">
            <?php foreach($approvedLoans as $al): 
                if ($al['interest_type'] === 'flat') {
                    $totalRepayable = $al['amount'] + ($al['amount'] * $al['interest_rate'] / 100);
                } else {
                    $totalRepayable = $al['amount'] * pow(1 + ($al['interest_rate'] / 100), $al['duration_months']);
                }
                $monthlyPayment = $totalRepayable / $al['duration_months'];
                $dueDate = date('Y-m-d', strtotime('+' . $al['duration_months'] . ' months'));
            ?>
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($al['member_id']); ?></span>
                            <span class="font-bold text-gray-900"><?php echo htmlspecialchars($al['fullname']); ?></span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-gray-500 mb-2">
                            <div>Amount: <strong class="text-gray-900"><?php echo number_format($al['amount']); ?> RWF</strong></div>
                            <div>Total Repayable: <strong><?php echo number_format(round($totalRepayable)); ?> RWF</strong></div>
                            <div>Monthly: <strong><?php echo number_format(round($monthlyPayment)); ?> RWF</strong></div>
                            <div>Duration: <strong><?php echo $al['duration_months']; ?> months</strong></div>
                        </div>
                        <div class="text-xs text-gray-500">Product: <strong><?php echo htmlspecialchars($al['product_name'] ?? 'Standard'); ?></strong> | Interest: <strong><?php echo $al['interest_rate']; ?>% <?php echo $al['interest_type']; ?></strong></div>
                        <?php if ($al['purpose']): ?><div class="text-xs text-gray-500 mt-1">Purpose: <?php echo htmlspecialchars($al['purpose']); ?></div><?php endif; ?>
                    </div>
                    <form action="disburse_process.php" method="POST" class="flex-shrink-0">
                        <input type="hidden" name="request_id" value="<?php echo $al['id']; ?>">
                        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                        <input type="hidden" name="total_repayable" value="<?php echo round($totalRepayable); ?>">
                        <input type="hidden" name="due_date" value="<?php echo $dueDate; ?>">
                        <input type="hidden" name="interest_rate" value="<?php echo $al['interest_rate']; ?>">
                        <button type="submit" name="disburse" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">Confirm Disbursement</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Disbursed History -->
<?php if (!empty($disbursedLoans)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Disbursement History</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr><th>Member</th><th>Amount</th><th>Status</th><th>Disbursed By</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach($disbursedLoans as $dl): ?>
                <tr>
                    <td><span class="text-xs text-brand-600"><?php echo htmlspecialchars($dl['member_id']); ?></span><br><span class="font-medium text-sm"><?php echo htmlspecialchars($dl['fullname']); ?></span></td>
                    <td class="font-semibold"><?php echo number_format($dl['amount']); ?> RWF</td>
                    <td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700"><?php echo ucfirst($dl['status']); ?></span></td>
                    <td class="text-sm"><?php echo htmlspecialchars($dl['disbursed_by_name'] ?? '-'); ?></td>
                    <td class="text-xs text-gray-500"><?php echo $dl['disbursed_at'] ? date('d M Y', strtotime($dl['disbursed_at'])) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>