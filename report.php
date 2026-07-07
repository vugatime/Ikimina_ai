<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
$page_title = 'My Report';
$base_path = '';

$uid = $current_user_id;

// Get member info
$stmt = $pdo->prepare("SELECT gm.id as membership_id, gm.member_id, gm.role_in_group, gm.group_id, gm.joined_at, g.group_name, g.contribution_amount, g.contribution_frequency 
                       FROM group_members gm 
                       JOIN `groups` g ON gm.group_id = g.id 
                       WHERE gm.user_id = ? AND gm.deleted_at IS NULL LIMIT 1");
$stmt->execute([$uid]);
$memberInfo = $stmt->fetch();

if (!$memberInfo) {
    header('Location: dashboard.php'); exit;
}

$membershipId = $memberInfo['membership_id'];
$groupId = $memberInfo['group_id'];

// Total savings
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE member_id = ?");
$stmt->execute([$membershipId]);
$totalSavings = $stmt->fetchColumn();

// Savings count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM savings WHERE member_id = ?");
$stmt->execute([$membershipId]);
$savingsCount = $stmt->fetchColumn();

// Expected savings (since joining)
$joinedDate = new DateTime($memberInfo['joined_at']);
$now = new DateTime();
$interval = $joinedDate->diff($now);
$monthsSinceJoining = max(1, $interval->m + ($interval->y * 12));

if ($memberInfo['contribution_frequency'] === 'weekly') {
    $weeksSinceJoining = max(1, ceil($interval->days / 7));
    $expectedSavings = $memberInfo['contribution_amount'] * $weeksSinceJoining;
} elseif ($memberInfo['contribution_frequency'] === 'biweekly') {
    $biweeksSinceJoining = max(1, ceil($interval->days / 14));
    $expectedSavings = $memberInfo['contribution_amount'] * $biweeksSinceJoining;
} else {
    $expectedSavings = $memberInfo['contribution_amount'] * $monthsSinceJoining;
}

$complianceRate = $expectedSavings > 0 ? min(100, round(($totalSavings / $expectedSavings) * 100)) : 0;

// All savings records
$stmt = $pdo->prepare("SELECT amount, payment_date, savings_type, notes FROM savings WHERE member_id = ? ORDER BY payment_date DESC LIMIT 50");
$stmt->execute([$membershipId]);
$allSavings = $stmt->fetchAll();

// Active loans
$stmt = $pdo->prepare("SELECT l.*, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) as total_repaid FROM loans l WHERE l.member_id = ? AND l.status IN ('active','approved') ORDER BY l.created_at DESC");
$stmt->execute([$membershipId]);
$activeLoans = $stmt->fetchAll();

// Completed loans
$stmt = $pdo->prepare("SELECT l.*, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) as total_repaid FROM loans l WHERE l.member_id = ? AND l.status = 'completed' ORDER BY l.created_at DESC LIMIT 10");
$stmt->execute([$membershipId]);
$completedLoans = $stmt->fetchAll();

// Loan requests
$stmt = $pdo->prepare("SELECT lr.*, lp.product_name FROM loan_requests lr LEFT JOIN loan_products lp ON lr.loan_product_id = lp.id WHERE lr.member_id = ? ORDER BY lr.created_at DESC LIMIT 10");
$stmt->execute([$membershipId]);
$loanRequests = $stmt->fetchAll();

// Penalties (fines)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM fines WHERE member_id = ? AND group_id = ? AND status = 'paid'");
$stmt->execute([$membershipId, $groupId]);
$totalFines = $stmt->fetchColumn();

// Meeting attendance
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late, SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent FROM attendance WHERE member_id = ?");
$stmt->execute([$membershipId]);
$attendance = $stmt->fetch();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h3 class="text-xl font-extrabold text-gray-900">My Financial Report</h3>
        <p class="text-gray-500 text-sm mt-1">
            <span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold mr-2" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($memberInfo['member_id']); ?></span>
            <?php echo htmlspecialchars($memberInfo['group_name']); ?>
        </p>
    </div>
    <button onclick="window.print()" class="px-4 py-2 rounded-xl text-sm font-semibold text-white transition no-underline" style="background:#0F766E;">Print Report</button>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($totalSavings); ?> RWF</div>
        <div class="text-xs text-gray-500 mt-0.5">Total Saved (<?php echo $savingsCount; ?> times)</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($expectedSavings); ?> RWF</div>
        <div class="text-xs text-gray-500 mt-0.5">Expected Savings</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold <?php echo $complianceRate >= 80 ? 'text-green-600' : ($complianceRate >= 50 ? 'text-amber-600' : 'text-red-600'); ?>"><?php echo $complianceRate; ?>%</div>
        <div class="text-xs text-gray-500 mt-0.5">Compliance Rate</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold text-gray-900"><?php echo count($activeLoans); ?></div>
        <div class="text-xs text-gray-500 mt-0.5">Active Loans</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Compliance Meter -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-bold text-gray-900 mb-4">Savings Compliance</h3>
        <div class="flex items-center gap-4 mb-2">
            <div class="flex-1 bg-gray-200 rounded-full h-4">
                <div class="h-4 rounded-full transition-all" style="width:<?php echo $complianceRate; ?>%; background:<?php echo $complianceRate >= 80 ? '#10b981' : ($complianceRate >= 50 ? '#f59e0b' : '#ef4444'); ?>;"></div>
            </div>
            <span class="font-bold text-sm"><?php echo $complianceRate; ?>%</span>
        </div>
        <p class="text-xs text-gray-500">
            You have saved <strong><?php echo number_format($totalSavings); ?> RWF</strong> out of expected <strong><?php echo number_format($expectedSavings); ?> RWF</strong>.
            <?php if ($complianceRate < 80): ?>
                <br><span class="text-amber-600">You are behind by <?php echo number_format($expectedSavings - $totalSavings); ?> RWF.</span>
            <?php else: ?>
                <br><span class="text-green-600">Great job! Keep up the consistent savings.</span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Group Info -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-bold text-gray-900 mb-3">Group Information</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Group:</span> <strong><?php echo htmlspecialchars($memberInfo['group_name']); ?></strong></div>
            <div class="flex justify-between"><span class="text-gray-500">Contribution:</span> <strong><?php echo number_format($memberInfo['contribution_amount']); ?> RWF / <?php echo ucfirst($memberInfo['contribution_frequency']); ?></strong></div>
            <div class="flex justify-between"><span class="text-gray-500">Member Since:</span> <strong><?php echo date('d M Y', strtotime($memberInfo['joined_at'])); ?></strong></div>
            <div class="flex justify-between"><span class="text-gray-500">Member ID:</span> <strong style="color:#0F766E;"><?php echo htmlspecialchars($memberInfo['member_id']); ?></strong></div>
            <?php if ($totalFines > 0): ?>
            <div class="flex justify-between"><span class="text-gray-500">Total Fines Paid:</span> <strong class="text-red-500"><?php echo number_format($totalFines); ?> RWF</strong></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Active Loans -->
<?php if (!empty($activeLoans)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Active Loans</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr><th>Amount</th><th>Total Due</th><th>Repaid</th><th>Remaining</th><th>Due Date</th><th>Progress</th></tr></thead>
            <tbody>
                <?php foreach($activeLoans as $al): 
                    $remaining = $al['total_repayable'] - $al['total_repaid'];
                    $progress = $al['total_repayable'] > 0 ? round(($al['total_repaid'] / $al['total_repayable']) * 100) : 0;
                ?>
                <tr>
                    <td class="font-semibold"><?php echo number_format($al['amount']); ?> RWF</td>
                    <td><?php echo number_format($al['total_repayable']); ?> RWF</td>
                    <td class="text-green-600"><?php echo number_format($al['total_repaid']); ?> RWF</td>
                    <td class="font-semibold text-amber-600"><?php echo number_format($remaining); ?> RWF</td>
                    <td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($al['due_date'])); ?></td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-20 bg-gray-200 rounded-full h-2"><div class="h-2 rounded-full bg-green-500" style="width:<?php echo $progress; ?>%;"></div></div>
                            <span class="text-xs"><?php echo $progress; ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Savings History -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Savings History</h3></div>
    <?php if (empty($allSavings)): ?>
        <div class="p-10 text-center"><p class="text-gray-500 text-sm">No savings recorded yet.</p></div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead><tr><th>Date</th><th>Amount</th><th>Type</th><th>Notes</th></tr></thead>
                <tbody>
                    <?php foreach($allSavings as $s): ?>
                    <tr>
                        <td class="text-sm"><?php echo date('d M Y', strtotime($s['payment_date'])); ?></td>
                        <td class="font-semibold"><?php echo number_format($s['amount']); ?> RWF</td>
                        <td><span class="inline-block px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600"><?php echo ucfirst($s['savings_type']); ?></span></td>
                        <td class="text-xs text-gray-500"><?php echo htmlspecialchars($s['notes'] ?: '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Meeting Attendance -->
<?php if ($attendance && $attendance['total'] > 0): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Meeting Attendance</h3></div>
    <div class="p-5">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div class="bg-green-50 rounded-xl p-4"><div class="text-2xl font-extrabold text-green-600"><?php echo $attendance['present']; ?></div><div class="text-xs text-gray-500 mt-1">Present</div></div>
            <div class="bg-amber-50 rounded-xl p-4"><div class="text-2xl font-extrabold text-amber-600"><?php echo $attendance['late']; ?></div><div class="text-xs text-gray-500 mt-1">Late</div></div>
            <div class="bg-red-50 rounded-xl p-4"><div class="text-2xl font-extrabold text-red-600"><?php echo $attendance['absent']; ?></div><div class="text-xs text-gray-500 mt-1">Absent</div></div>
        </div>
        <p class="text-xs text-gray-500 text-center mt-3">Out of <?php echo $attendance['total']; ?> total meetings</p>
    </div>
</div>
<?php endif; ?>

<!-- Loan Request History -->
<?php if (!empty($loanRequests)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Loan Request History</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr><th>Date</th><th>Product</th><th>Amount</th><th>Duration</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach($loanRequests as $lr): 
                    $sc = ['pending'=>['bg'=>'#fef3c7','text'=>'#92400e'],'reviewed_by_admin'=>['bg'=>'#dbeafe','text'=>'#1e40af'],'approved'=>['bg'=>'#d1fae5','text'=>'#065f46'],'rejected'=>['bg'=>'#fee2e2','text'=>'#991b1b'],'disbursed'=>['bg'=>'#e0e7ff','text'=>'#3730a3'],'active'=>['bg'=>'#d1fae5','text'=>'#065f46'],'completed'=>['bg'=>'#ccfbf1','text'=>'#0F766E']];
                    $c = $sc[$lr['status']] ?? ['bg'=>'#f1f5f9','text'=>'#475569'];
                ?>
                <tr>
                    <td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($lr['created_at'])); ?></td>
                    <td class="text-sm"><?php echo htmlspecialchars($lr['product_name'] ?? 'Standard'); ?></td>
                    <td class="font-semibold"><?php echo number_format($lr['amount']); ?> RWF</td>
                    <td><?php echo $lr['duration_months']; ?> months</td>
                    <td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" style="background:<?php echo $c['bg']; ?>;color:<?php echo $c['text']?>;"><?php echo ucfirst(str_replace('_',' ',$lr['status'])); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>