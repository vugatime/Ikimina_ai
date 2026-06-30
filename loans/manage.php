<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Loans';
$base_path = '../';

$isSuperAdmin = ($current_user_role === 'super_admin');
$groupId = $_GET['group_id'] ?? null;
$message = $_GET['msg'] ?? '';

// Get groups
if ($isSuperAdmin) {
    $stmt = $pdo->query("SELECT id, group_name FROM `groups` ORDER BY group_name ASC");
} else {
    $stmt = $pdo->prepare("SELECT g.id, g.group_name FROM `groups` g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ? AND gm.deleted_at IS NULL AND gm.role_in_group IN ('group_admin','assistant_admin') ORDER BY g.group_name ASC");
    $stmt->execute([$current_user_id]);
}
$myGroups = $stmt->fetchAll();

if (!$groupId && !empty($myGroups)) $groupId = $myGroups[0]['id'];

// Get group info
$groupRules = null;
$loanProducts = [];
$members = [];
if ($groupId) {
    $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupRules = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM loan_products WHERE group_id = ? AND status = 'active' ORDER BY product_name ASC");
    $stmt->execute([$groupId]);
    $loanProducts = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT gm.id as membership_id, gm.member_id, u.fullname, COALESCE((SELECT SUM(amount) FROM savings WHERE member_id = gm.id), 0) as total_savings FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ? AND gm.deleted_at IS NULL ORDER BY gm.member_id ASC");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll();
}

// Get loans
$loans = [];
if ($groupId) {
    $stmt = $pdo->prepare("SELECT l.*, gm.member_id, u.fullname, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) as total_repaid FROM loans l JOIN group_members gm ON l.member_id = gm.id JOIN users u ON gm.user_id = u.id WHERE l.group_id = ? ORDER BY l.created_at DESC");
    $stmt->execute([$groupId]);
    $loans = $stmt->fetchAll();
}

// All loans for Super Admin
$allLoans = [];
if ($isSuperAdmin && !$groupId) {
    $stmt = $pdo->query("SELECT l.*, gm.member_id, u.fullname, g.group_name, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) as total_repaid FROM loans l JOIN group_members gm ON l.member_id = gm.id JOIN users u ON gm.user_id = u.id JOIN `groups` g ON l.group_id = g.id ORDER BY l.created_at DESC LIMIT 50");
    $allLoans = $stmt->fetchAll();
}

// Stats
$activeLoans = 0; $pendingLoans = 0; $totalOutstanding = 0;
foreach ($loans as $l) {
    if (in_array($l['status'], ['active', 'approved'])) $activeLoans++;
    if ($l['status'] === 'pending') $pendingLoans++;
    if (in_array($l['status'], ['active', 'approved'])) $totalOutstanding += ($l['total_repayable'] - $l['total_repaid']);
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($message === 'applied'): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Loan application submitted.</div>
<?php elseif ($message === 'approved'): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Loan approved.</div>
<?php elseif ($message === 'rejected'): ?><div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm font-medium">Loan rejected.</div>
<?php elseif ($message === 'repaid'): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Repayment recorded.</div><?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900"><?php echo $isSuperAdmin ? 'All Loans' : 'Loan Management'; ?></h3>
    <?php if ($isSuperAdmin): ?><p class="text-gray-500 text-sm mt-1">View-only — monitoring all loans across groups.</p><?php endif; ?>
</div>

<!-- Group Selector -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block">Select Group</label>
    <div class="flex flex-wrap gap-2">
        <?php foreach($myGroups as $g): ?>
            <a href="?group_id=<?php echo $g['id']; ?>" class="px-4 py-2 rounded-lg text-sm font-medium transition no-underline <?php echo ($groupId == $g['id']) ? 'text-white' : 'text-gray-600 bg-gray-50 hover:bg-gray-100'; ?>" style="<?php echo ($groupId == $g['id']) ? 'background:#0F766E;' : ''; ?>"><?php echo htmlspecialchars($g['group_name']); ?></a>
        <?php endforeach; ?>
        <?php if ($isSuperAdmin): ?>
            <a href="?group_id=" class="px-4 py-2 rounded-lg text-sm font-medium transition no-underline text-gray-600 bg-gray-50 hover:bg-gray-100">All Groups</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isSuperAdmin && !$groupId): ?>
    <!-- All Loans Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">All Loans</h3></div>
        <?php if(empty($allLoans)): ?><div class="p-10 text-center"><p class="text-gray-500 text-sm">No loans yet.</p></div>
        <?php else: ?><div class="overflow-x-auto"><table class="w-full"><thead><tr><th>Group</th><th>Member</th><th>Amount</th><th>Repaid</th><th>Status</th><th>Due</th></tr></thead><tbody>
        <?php foreach($allLoans as $l): $remaining=$l['total_repayable']-$l['total_repaid']; $sc=['active'=>['bg'=>'#d1fae5','text'=>'#065f46'],'approved'=>['bg'=>'#dbeafe','text'=>'#1e40af'],'completed'=>['bg'=>'#e0e7ff','text'=>'#3730a3'],'pending'=>['bg'=>'#fef3c7','text'=>'#92400e'],'rejected'=>['bg'=>'#fee2e2','text'=>'#991b1b'],'defaulted'=>['bg'=>'#fce7f3','text'=>'#9d174d']]; $c=$sc[$l['status']]??['bg'=>'#f1f5f9','text'=>'#475569']; ?>
        <tr><td class="text-sm"><?php echo htmlspecialchars($l['group_name']); ?></td><td><span class="text-xs font-bold text-brand-600"><?php echo htmlspecialchars($l['member_id']); ?></span><br><span class="text-sm"><?php echo htmlspecialchars($l['fullname']); ?></span></td><td class="font-semibold"><?php echo number_format($l['amount']); ?> RWF</td><td class="text-green-600"><?php echo number_format($l['total_repaid']); ?> RWF</td><td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" style="background:<?php echo $c['bg']; ?>;color:<?php echo $c['text']?>;"><?php echo ucfirst($l['status']); ?></span></td><td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($l['due_date'])); ?></td></tr>
        <?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div>
<?php elseif ($groupId): ?>
    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="text-2xl font-extrabold text-gray-900"><?php echo $activeLoans; ?></div><div class="text-xs text-gray-500">Active Loans</div></div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="text-2xl font-extrabold text-amber-600"><?php echo $pendingLoans; ?></div><div class="text-xs text-gray-500">Pending</div></div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($totalOutstanding); ?> RWF</div><div class="text-xs text-gray-500">Outstanding</div></div>
    </div>

    <div class="grid grid-cols-1 <?php echo $isSuperAdmin ? '' : 'lg:grid-cols-3'; ?> gap-6">
        <!-- Form - HIDDEN for Super Admin -->
        <?php if (!$isSuperAdmin): ?>
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-bold text-gray-900 mb-4">New Loan Application</h3>
                <form action="apply_process.php" method="POST" class="space-y-4">
                    <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Member</label><select name="member_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required><option value="">Select member</option><?php foreach($members as $m): ?><option value="<?php echo $m['membership_id']; ?>"><?php echo htmlspecialchars($m['member_id'].' - '.$m['fullname']); ?> (<?php echo number_format($m['total_savings']); ?> RWF)</option><?php endforeach; ?></select></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Loan Product</label><select name="loan_product_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required><option value="">Select product</option><?php foreach($loanProducts as $lp): ?><option value="<?php echo $lp['id']; ?>"><?php echo htmlspecialchars($lp['product_name']); ?> (Max: <?php echo number_format($lp['max_amount']); ?> RWF)</option><?php endforeach; ?></select></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Amount (RWF)</label><input type="number" name="amount" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Loan amount" required></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Duration (Months)</label><input type="number" name="duration_months" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="3" required></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Purpose</label><textarea name="purpose" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Reason for loan..."></textarea></div>
                    <button type="submit" name="apply_loan" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Submit Application</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Loans Table -->
        <div class="<?php echo $isSuperAdmin ? '' : 'lg:col-span-2'; ?>">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">All Loans</h3></div>
                <?php if(empty($loans)): ?><div class="p-10 text-center"><p class="text-gray-500 text-sm">No loans yet.</p></div>
                <?php else: ?><div class="overflow-x-auto"><table class="w-full"><thead><tr><th>Member</th><th>Amount</th><th>Total Due</th><th>Repaid</th><th>Status</th><?php if(!$isSuperAdmin): ?><th>Actions</th><?php endif; ?></tr></thead><tbody>
                <?php foreach($loans as $l): $remaining=$l['total_repayable']-$l['total_repaid']; $sc=['active'=>['bg'=>'#d1fae5','text'=>'#065f46'],'approved'=>['bg'=>'#dbeafe','text'=>'#1e40af'],'completed'=>['bg'=>'#e0e7ff','text'=>'#3730a3'],'pending'=>['bg'=>'#fef3c7','text'=>'#92400e'],'rejected'=>['bg'=>'#fee2e2','text'=>'#991b1b']]; $c=$sc[$l['status']]??['bg'=>'#f1f5f9','text'=>'#475569']; ?>
                <tr><td><span class="text-xs font-bold text-brand-600"><?php echo htmlspecialchars($l['member_id']); ?></span><br><span class="text-sm"><?php echo htmlspecialchars($l['fullname']); ?></span></td><td class="font-semibold"><?php echo number_format($l['amount']); ?> RWF</td><td><?php echo number_format($l['total_repayable']); ?> RWF</td><td class="text-green-600"><?php echo number_format($l['total_repaid']); ?> RWF</td><td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" style="background:<?php echo $c['bg']; ?>;color:<?php echo $c['text']?>;"><?php echo ucfirst($l['status']); ?></span></td>
                <?php if(!$isSuperAdmin): ?><td><div class="flex gap-1"><?php if($l['status']==='pending'): ?><a href="approve.php?id=<?php echo $l['id']; ?>&group_id=<?php echo $groupId; ?>" class="px-2 py-1 text-xs font-medium text-green-600 hover:bg-green-50 rounded">Approve</a><a href="reject.php?id=<?php echo $l['id']; ?>&group_id=<?php echo $groupId; ?>" class="px-2 py-1 text-xs font-medium text-red-500 hover:bg-red-50 rounded" onclick="return confirm('Reject?')">Reject</a><?php elseif(in_array($l['status'],['active','approved'])): ?><a href="repay.php?id=<?php echo $l['id']; ?>&group_id=<?php echo $groupId; ?>" class="px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50 rounded">Repay</a><?php endif; ?></div></td><?php endif; ?></tr>
                <?php endforeach; ?></tbody></table></div><?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>