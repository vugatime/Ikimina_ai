<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
$page_title = 'My Report';
$base_path = '';

$uid = $current_user_id;

// Get member info
$stmt = $pdo->prepare("SELECT gm.id as membership_id, gm.member_id, gm.role_in_group, gm.group_id, gm.joined_at, g.group_name, g.contribution_amount, g.contribution_frequency FROM group_members gm JOIN `groups` g ON gm.group_id = g.id WHERE gm.user_id = ? AND gm.deleted_at IS NULL LIMIT 1");
$stmt->execute([$uid]);
$memberInfo = $stmt->fetch();
if (!$memberInfo) { header('Location: dashboard.php'); exit; }

$membershipId = $memberInfo['membership_id'];
$groupId = $memberInfo['group_id'];

// Total contributed (positive savings)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE member_id = ? AND group_id = ? AND amount > 0");
$stmt->execute([$membershipId, $groupId]);
$totalContributed = $stmt->fetchColumn();

// Total received from Kuzenguruka
$stmt = $pdo->prepare("SELECT COALESCE(SUM(pr.amount_received), 0) FROM payout_recipients pr WHERE pr.member_id = ?");
$stmt->execute([$membershipId]);
$totalPayoutReceived = $stmt->fetchColumn();

// Net balance
$netBalance = $totalContributed - $totalPayoutReceived;

// Savings count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM savings WHERE member_id = ? AND group_id = ? AND amount > 0");
$stmt->execute([$membershipId, $groupId]);
$savingsCount = $stmt->fetchColumn();

// Expected savings
$joinedDate = new DateTime($memberInfo['joined_at']);
$now = new DateTime();
$interval = $joinedDate->diff($now);
$monthsSince = max(1, $interval->m + ($interval->y * 12));
$expectedSavings = $memberInfo['contribution_amount'] * $monthsSince;
$complianceRate = $expectedSavings > 0 ? min(100, round(($totalContributed / $expectedSavings) * 100)) : 0;

// Savings history
$stmt = $pdo->prepare("SELECT amount, payment_date, savings_type, notes FROM savings WHERE member_id = ? AND group_id = ? ORDER BY payment_date DESC LIMIT 30");
$stmt->execute([$membershipId, $groupId]);
$savingsHistory = $stmt->fetchAll();

// Active loans
$stmt = $pdo->prepare("SELECT l.*, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) as total_repaid FROM loans l WHERE l.member_id = ? AND l.status IN ('active','approved') ORDER BY l.created_at DESC");
$stmt->execute([$membershipId]);
$activeLoans = $stmt->fetchAll();

// Loan history
$stmt = $pdo->prepare("SELECT l.*, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) as total_repaid FROM loans l WHERE l.member_id = ? ORDER BY l.created_at DESC LIMIT 10");
$stmt->execute([$membershipId]);
$allLoans = $stmt->fetchAll();

// Payouts received
$stmt = $pdo->prepare("SELECT pc.cycle_name, pc.generation, pr.amount_received, pr.ai_score, pr.received_at FROM payout_recipients pr JOIN payout_cycles pc ON pr.cycle_id = pc.id WHERE pr.member_id = ? ORDER BY pr.received_at DESC");
$stmt->execute([$membershipId]);
$payoutsReceived = $stmt->fetchAll();

// Fines
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM fines WHERE member_id = ? AND group_id = ?");
$stmt->execute([$membershipId, $groupId]);
$totalFines = $stmt->fetchColumn();

// Attendance
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present, SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late, SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent FROM attendance WHERE member_id = ?");
$stmt->execute([$membershipId]);
$attendance = $stmt->fetch();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h3 class="text-xl font-extrabold text-gray-900">My Financial Report</h3>
        <p class="text-gray-500 text-sm mt-1"><span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold mr-2" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($memberInfo['member_id']); ?></span><?php echo htmlspecialchars($memberInfo['group_name']); ?></p>
    </div>
    <button onclick="window.print()" class="px-4 py-2 rounded-xl text-sm font-semibold text-white transition no-underline" style="background:#0F766E;">Print Report</button>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm text-center">
        <div class="text-xl font-extrabold text-gray-900"><?php echo number_format($totalContributed); ?> RWF</div>
        <div class="text-xs text-gray-500 mt-0.5">Total Contributed</div>
    </div>
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm text-center">
        <div class="text-xl font-extrabold text-green-600">+<?php echo number_format($totalPayoutReceived); ?> RWF</div>
        <div class="text-xs text-gray-500 mt-0.5">Kuzenguruka Received</div>
    </div>
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm text-center">
        <div class="text-xl font-extrabold <?php echo $netBalance>=0?'text-brand-600':'text-red-600'; ?>"><?php echo number_format($netBalance); ?> RWF</div>
        <div class="text-xs text-gray-500 mt-0.5">Net Balance</div>
    </div>
    <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm text-center">
        <div class="text-xl font-extrabold <?php echo $complianceRate>=80?'text-green-600':($complianceRate>=50?'text-amber-600':'text-red-600'); ?>"><?php echo $complianceRate; ?>%</div>
        <div class="text-xs text-gray-500 mt-0.5">Compliance</div>
    </div>
</div>

<!-- Group Info & Compliance -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-bold text-gray-900 mb-3">Group Information</h3>
        <table class="w-full text-sm">
            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Group</td><td class="py-2 font-semibold"><?php echo htmlspecialchars($memberInfo['group_name']); ?></td></tr>
            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Contribution</td><td class="py-2 font-semibold"><?php echo number_format($memberInfo['contribution_amount']); ?> RWF / <?php echo ucfirst($memberInfo['contribution_frequency']); ?></td></tr>
            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Member Since</td><td class="py-2 font-semibold"><?php echo date('d M Y', strtotime($memberInfo['joined_at'])); ?></td></tr>
            <tr><td class="py-2 text-gray-500">Member ID</td><td class="py-2 font-semibold" style="color:#0F766E;"><?php echo htmlspecialchars($memberInfo['member_id']); ?></td></tr>
        </table>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-bold text-gray-900 mb-3">Savings Compliance</h3>
        <div class="flex items-center gap-3 mb-2">
            <div class="flex-1 bg-gray-200 rounded-full h-3"><div class="h-3 rounded-full transition-all" style="width:<?php echo $complianceRate; ?>%;background:<?php echo $complianceRate>=80?'#10b981':($complianceRate>=50?'#f59e0b':'#ef4444'); ?>;"></div></div>
            <span class="font-bold text-sm"><?php echo $complianceRate; ?>%</span>
        </div>
        <p class="text-xs text-gray-500">Expected: <?php echo number_format($expectedSavings); ?> RWF &middot; Contributed: <?php echo number_format($totalContributed); ?> RWF &middot; Received: <?php echo number_format($totalPayoutReceived); ?> RWF</p>
    </div>
</div>

<!-- Contributions Table -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Contribution History</h3></div>
    <?php if(empty($savingsHistory)): ?><div class="p-6 text-center text-gray-500 text-sm">No contributions yet.</div>
    <?php else: ?><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>Date</th><th>Amount</th><th>Type</th><th>Notes</th></tr></thead><tbody>
        <?php foreach($savingsHistory as $s): ?>
        <tr class="border-b border-gray-50">
            <td class="py-2 px-4"><?php echo date('d M Y', strtotime($s['payment_date'])); ?></td>
            <td class="py-2 px-4 font-semibold <?php echo $s['amount']>=0?'text-green-600':'text-red-600'; ?>"><?php echo $s['amount']>=0?'+':''; ?><?php echo number_format($s['amount']); ?> RWF</td>
            <td class="py-2 px-4"><span class="inline-block px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600"><?php echo ucfirst($s['savings_type']); ?></span></td>
            <td class="py-2 px-4 text-xs text-gray-500"><?php echo htmlspecialchars($s['notes'] ?: '-'); ?></td>
        </tr>
        <?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>

<!-- Kuzenguruka Received -->
<?php if(!empty($payoutsReceived)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Kuzenguruka Payouts Received</h3></div>
    <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>Generation</th><th>Cycle</th><th>Amount</th><th>Date</th></tr></thead><tbody>
        <?php foreach($payoutsReceived as $p): ?>
        <tr class="border-b border-gray-50">
            <td class="py-2 px-4 font-semibold">Gen <?php echo $p['generation']; ?></td>
            <td class="py-2 px-4"><?php echo htmlspecialchars($p['cycle_name']); ?></td>
            <td class="py-2 px-4 font-semibold text-green-600">+<?php echo number_format($p['amount_received']); ?> RWF</td>
            <td class="py-2 px-4 text-xs text-gray-500"><?php echo date('d M Y', strtotime($p['received_at'])); ?></td>
        </tr>
        <?php endforeach; ?></tbody></table></div>
</div>
<?php endif; ?>

<!-- Active Loans -->
<?php if(!empty($activeLoans)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Active Loans</h3></div>
    <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>Amount</th><th>Total Due</th><th>Repaid</th><th>Remaining</th><th>Due Date</th><th>Progress</th></tr></thead><tbody>
        <?php foreach($activeLoans as $l): $remaining=$l['total_repayable']-$l['total_repaid']; $progress=$l['total_repayable']>0?round(($l['total_repaid']/$l['total_repayable'])*100):0; ?>
        <tr class="border-b border-gray-50">
            <td class="py-2 px-4 font-semibold"><?php echo number_format($l['amount']); ?> RWF</td>
            <td class="py-2 px-4"><?php echo number_format($l['total_repayable']); ?> RWF</td>
            <td class="py-2 px-4 text-green-600"><?php echo number_format($l['total_repaid']); ?> RWF</td>
            <td class="py-2 px-4 font-semibold text-amber-600"><?php echo number_format($remaining); ?> RWF</td>
            <td class="py-2 px-4 text-xs text-gray-500"><?php echo date('d M Y', strtotime($l['due_date'])); ?></td>
            <td class="py-2 px-4"><div class="w-20 bg-gray-200 rounded-full h-2"><div class="h-2 rounded-full bg-green-500" style="width:<?php echo $progress; ?>%;"></div></div><span class="text-xs ml-1"><?php echo $progress; ?>%</span></td>
        </tr>
        <?php endforeach; ?></tbody></table></div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>