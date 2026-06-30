<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Savings';
$base_path = '../';

$isSuperAdmin = ($current_user_role === 'super_admin');
$groupId = $_GET['group_id'] ?? null;
$message = $_GET['msg'] ?? '';

// Get groups
if ($isSuperAdmin) {
    $stmt = $pdo->query("SELECT id, group_name FROM `groups` ORDER BY group_name ASC");
} else {
    // ROLE CHECK: Only Group Admin, Assistant Admin, Treasurer can record
    if ($current_user_role === 'member') { header('Location: ../dashboard.php'); exit; }
    $stmt = $pdo->prepare("SELECT g.id, g.group_name FROM `groups` g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ? AND gm.deleted_at IS NULL AND gm.role_in_group IN ('group_admin','assistant_admin','treasurer') ORDER BY g.group_name ASC");
    $stmt->execute([$current_user_id]);
}
$myGroups = $stmt->fetchAll();

if (!$groupId && !empty($myGroups)) $groupId = $myGroups[0]['id'];

// Get group rules
$groupRules = null;
$members = [];
if ($groupId) {
    $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupRules = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT gm.id as membership_id, gm.member_id, u.fullname FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ? AND gm.deleted_at IS NULL ORDER BY gm.member_id ASC");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll();
}

// Get recent savings
$recentSavings = [];
if ($groupId) {
    $stmt = $pdo->prepare("SELECT s.*, gm.member_id, u.fullname, us.fullname as recorded_by_name FROM savings s JOIN group_members gm ON s.member_id = gm.id JOIN users u ON gm.user_id = u.id JOIN users us ON s.recorded_by = us.id WHERE s.group_id = ? ORDER BY s.created_at DESC LIMIT 20");
    $stmt->execute([$groupId]);
    $recentSavings = $stmt->fetchAll();
}

// Get member summaries
$memberSummaries = [];
if ($groupId) {
    $stmt = $pdo->prepare("SELECT gm.id as membership_id, gm.member_id, u.fullname, COALESCE(SUM(s.amount), 0) as total_saved, COUNT(s.id) as savings_count, MAX(s.payment_date) as last_payment FROM group_members gm JOIN users u ON gm.user_id = u.id LEFT JOIN savings s ON gm.id = s.member_id WHERE gm.group_id = ? AND gm.deleted_at IS NULL GROUP BY gm.id, gm.member_id, u.fullname ORDER BY gm.member_id ASC");
    $stmt->execute([$groupId]);
    $memberSummaries = $stmt->fetchAll();
}

// If Super Admin and no group selected, show all savings
$allSavings = [];
if ($isSuperAdmin && !$groupId) {
    $stmt = $pdo->query("SELECT s.*, gm.member_id, u.fullname, g.group_name, us.fullname as recorded_by_name FROM savings s JOIN group_members gm ON s.member_id = gm.id JOIN users u ON gm.user_id = u.id JOIN `groups` g ON s.group_id = g.id JOIN users us ON s.recorded_by = us.id ORDER BY s.created_at DESC LIMIT 50");
    $allSavings = $stmt->fetchAll();
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($message === 'recorded'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Savings recorded successfully.</div>
<?php elseif ($message === 'late'): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm font-medium">Savings recorded. Penalty applied.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900"><?php echo $isSuperAdmin ? 'All Savings' : 'Record Savings'; ?></h3>
    <?php if ($isSuperAdmin): ?><p class="text-gray-500 text-sm mt-1">View-only — monitoring all savings across groups.</p><?php endif; ?>
</div>

<?php if (empty($myGroups) && !$isSuperAdmin): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center"><p class="text-gray-500 text-sm">No groups available.</p></div>
<?php else: ?>
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

    <?php if ($groupRules): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap gap-4 text-sm">
            <div><span class="text-gray-500">Contribution:</span> <strong><?php echo number_format($groupRules['contribution_amount']); ?> RWF / <?php echo ucfirst($groupRules['contribution_frequency']); ?></strong></div>
            <?php if ($groupRules['late_penalty_value'] > 0): ?><div><span class="text-gray-500">Penalty:</span> <strong class="text-amber-600"><?php echo $groupRules['late_penalty_value']; ?><?php echo $groupRules['late_penalty_type']=='percent'?'%':' RWF'; ?></strong></div><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 <?php echo $isSuperAdmin ? '' : 'lg:grid-cols-3'; ?> gap-6">
        <!-- Record Form - HIDDEN for Super Admin -->
        <?php if (!$isSuperAdmin && $groupId): ?>
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-bold text-gray-900 mb-4">New Savings Entry</h3>
                <form action="record_process.php" method="POST" class="space-y-4">
                    <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Member</label><select name="member_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required><option value="">Select member</option><?php foreach($members as $m): ?><option value="<?php echo $m['membership_id']; ?>"><?php echo htmlspecialchars($m['member_id'].' - '.$m['fullname']); ?></option><?php endforeach; ?></select></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Amount (RWF)</label><input type="number" name="amount" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo $groupRules['contribution_amount']; ?>" required></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Payment Date</label><input type="date" name="payment_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Type</label><select name="savings_type" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition"><option value="weekly">Weekly</option><option value="monthly" selected>Monthly</option><option value="special">Special</option></select></div>
                    <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Notes</label><textarea name="notes" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Any notes..."></textarea></div>
                    <button type="submit" name="record_savings" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Record Savings</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tables -->
        <div class="<?php echo $isSuperAdmin ? '' : 'lg:col-span-2'; ?>">
            <?php if ($isSuperAdmin && !$groupId): ?>
                <!-- All Savings Table -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">All Recent Transactions</h3></div>
                    <?php if(empty($allSavings)): ?><div class="p-10 text-center"><p class="text-gray-500 text-sm">No savings recorded yet.</p></div>
                    <?php else: ?><div class="overflow-x-auto"><table class="w-full"><thead><tr><th>Group</th><th>Member</th><th>Amount</th><th>Date</th><th>By</th></tr></thead><tbody>
                    <?php foreach($allSavings as $s): ?><tr><td class="text-sm"><?php echo htmlspecialchars($s['group_name']); ?></td><td><span class="text-xs font-bold text-brand-600"><?php echo htmlspecialchars($s['member_id']); ?></span><br><span class="text-sm"><?php echo htmlspecialchars($s['fullname']); ?></span></td><td class="font-semibold"><?php echo number_format($s['amount']); ?> RWF</td><td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($s['payment_date'])); ?></td><td class="text-xs text-gray-400"><?php echo htmlspecialchars($s['recorded_by_name']); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
                </div>
            <?php elseif ($groupId): ?>
                <!-- Member Summary -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
                    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Member Savings Summary</h3></div>
                    <?php if(empty($memberSummaries)): ?><div class="p-10 text-center"><p class="text-gray-500 text-sm">No members yet.</p></div>
                    <?php else: ?><div class="overflow-x-auto"><table class="w-full"><thead><tr><th>Member ID</th><th>Name</th><th>Total Saved</th><th>Times</th><th>Last Payment</th></tr></thead><tbody>
                    <?php foreach($memberSummaries as $ms): ?><tr><td><span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($ms['member_id']); ?></span></td><td class="font-semibold text-sm"><?php echo htmlspecialchars($ms['fullname']); ?></td><td class="font-semibold"><?php echo number_format($ms['total_saved']); ?> RWF</td><td><?php echo $ms['savings_count']; ?></td><td class="text-xs text-gray-500"><?php echo $ms['last_payment'] ? date('d M Y', strtotime($ms['last_payment'])) : 'Never'; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
                </div>
                <!-- Recent Transactions -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Recent Transactions</h3></div>
                    <?php if(empty($recentSavings)): ?><div class="p-10 text-center"><p class="text-gray-500 text-sm">No savings recorded yet.</p></div>
                    <?php else: ?><div class="overflow-x-auto"><table class="w-full"><thead><tr><th>Member</th><th>Amount</th><th>Type</th><th>Date</th><th>By</th></tr></thead><tbody>
                    <?php foreach($recentSavings as $rs): ?><tr><td><span class="text-xs font-bold text-brand-600"><?php echo htmlspecialchars($rs['member_id']); ?></span><br><span class="text-sm"><?php echo htmlspecialchars($rs['fullname']); ?></span></td><td class="font-semibold"><?php echo number_format($rs['amount']); ?> RWF</td><td><span class="inline-block px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600"><?php echo ucfirst($rs['savings_type']); ?></span></td><td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($rs['payment_date'])); ?></td><td class="text-xs text-gray-400"><?php echo htmlspecialchars($rs['recorded_by_name']); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>