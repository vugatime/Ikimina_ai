<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
$page_title = 'Dashboard';
$base_path = '';

$uid = $current_user_id;
$urole = $current_user_role;
$uname = $current_user_name;
$welcome = $_GET['welcome'] ?? '';

$memberInfo = null;
$memberRole = null;
$groupId = null;
if ($urole !== 'super_admin') {
    $stmt = $pdo->prepare("SELECT gm.member_id, gm.role_in_group, gm.group_id, g.group_name, g.contribution_amount, g.contribution_frequency, g.late_penalty_type, g.late_penalty_value, g.meeting_absence_fine, g.meeting_late_fine FROM group_members gm JOIN `groups` g ON gm.group_id = g.id WHERE gm.user_id = ? AND gm.deleted_at IS NULL LIMIT 1");
    $stmt->execute([$uid]);
    $memberInfo = $stmt->fetch();
    $memberRole = $memberInfo['role_in_group'] ?? null;
    $groupId = $memberInfo['group_id'] ?? null;
}

$isSuperAdmin = ($urole === 'super_admin');
$isGroupAdmin = ($memberRole === 'group_admin');
$isAssistantAdmin = ($memberRole === 'assistant_admin');
$isTreasurer = ($memberRole === 'treasurer');
$isMember = ($memberRole === 'member');

$roleLabels = ['group_admin' => 'Group Admin', 'assistant_admin' => 'Assistant Admin', 'treasurer' => 'Treasurer', 'member' => 'Member'];

// ============ SUPER ADMIN DATA ============
if ($isSuperAdmin) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users"); $totalUsers = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM `groups`"); $totalGroups = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM savings"); $totalSavings = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM loans WHERE status IN('active','approved')"); $activeLoans = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"); $newUsers = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT g.group_name, g.district, g.sector, g.created_at, u.fullname as owner FROM `groups` g JOIN users u ON g.created_by = u.id ORDER BY g.created_at DESC LIMIT 5");
    $recentGroups = $stmt->fetchAll();
}

// ============ GROUP ADMIN DATA ============
if ($isGroupAdmin) {
    $gid = $groupId;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND deleted_at IS NULL"); $stmt->execute([$gid]); $totalMembers = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings WHERE group_id = ?"); $stmt->execute([$gid]); $groupSavings = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE group_id = ? AND status IN('active','approved')"); $stmt->execute([$gid]); $activeGroupLoans = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loan_requests WHERE group_id = ? AND status = 'pending'"); $stmt->execute([$gid]); $pendingRequests = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT s.amount, s.payment_date, gm.member_id, u.fullname FROM savings s JOIN group_members gm ON s.member_id = gm.id JOIN users u ON gm.user_id = u.id WHERE s.group_id = ? ORDER BY s.created_at DESC LIMIT 5");
    $stmt->execute([$gid]); $recentSavings = $stmt->fetchAll();
}

// ============ ASSISTANT ADMIN DATA ============
if ($isAssistantAdmin) {
    $gid = $groupId;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND deleted_at IS NULL"); $stmt->execute([$gid]); $totalMembers = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings WHERE group_id = ?"); $stmt->execute([$gid]); $groupSavings = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE group_id = ? AND status IN('active','approved')"); $stmt->execute([$gid]); $activeGroupLoans = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT s.amount, s.payment_date, gm.member_id, u.fullname FROM savings s JOIN group_members gm ON s.member_id = gm.id JOIN users u ON gm.user_id = u.id WHERE s.group_id = ? ORDER BY s.created_at DESC LIMIT 5");
    $stmt->execute([$gid]); $recentSavings = $stmt->fetchAll();
}

// ============ TREASURER DATA ============
if ($isTreasurer) {
    $gid = $groupId;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings WHERE group_id = ?"); $stmt->execute([$gid]); $groupSavings = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE group_id = ? AND status IN('active','approved')"); $stmt->execute([$gid]); $activeGroupLoans = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loan_requests WHERE group_id = ? AND status = 'approved'"); $stmt->execute([$gid]); $pendingDisbursements = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT s.amount, s.payment_date, gm.member_id, u.fullname FROM savings s JOIN group_members gm ON s.member_id = gm.id JOIN users u ON gm.user_id = u.id WHERE s.group_id = ? ORDER BY s.created_at DESC LIMIT 5");
    $stmt->execute([$gid]); $recentSavings = $stmt->fetchAll();
}

// ============ MEMBER DATA ============
if ($isMember) {
    $gid = $groupId;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings WHERE member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL)"); $stmt->execute([$uid, $gid]); $mySavings = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL) AND status IN('active','approved')"); $stmt->execute([$uid, $gid]); $myActiveLoans = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fines WHERE member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL) AND status = 'pending'"); $stmt->execute([$uid, $gid]); $myPendingFines = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT amount, payment_date, savings_type FROM savings WHERE member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL) ORDER BY created_at DESC LIMIT 5"); $stmt->execute([$uid, $gid]); $myRecentSavings = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT l.amount, l.total_repayable, l.status, l.due_date, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) as repaid FROM loans l WHERE l.member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL) ORDER BY l.created_at DESC LIMIT 3"); $stmt->execute([$uid, $gid]); $myRecentLoans = $stmt->fetchAll();
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php if ($welcome == '1'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Account created successfully. Welcome to IkiminaAI.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl md:text-2xl font-extrabold text-gray-900">Welcome, <?php echo htmlspecialchars(explode(' ', $uname)[0]); ?></h3>
    <?php if ($memberInfo): ?>
        <p class="text-gray-500 text-sm mt-1 flex items-center gap-2 flex-wrap">
            <span class="inline-block px-2.5 py-0.5 rounded-lg text-xs font-bold" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($memberInfo['member_id']); ?></span>
            <span><?php echo htmlspecialchars($memberInfo['group_name']); ?></span>
            <span class="hidden sm:inline text-gray-300">|</span>
            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600"><?php echo $roleLabels[$memberRole] ?? 'Member'; ?></span>
        </p>
    <?php elseif ($isSuperAdmin): ?>
        <p class="text-gray-500 text-sm mt-1">Platform Administrator — Monitoring all groups and users.</p>
    <?php endif; ?>
</div>

<!-- ============ SUPER ADMIN DASHBOARD ============ -->
<?php if ($isSuperAdmin): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($totalUsers); ?></div><div class="text-xs text-gray-500">Total Users</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($totalGroups); ?></div><div class="text-xs text-gray-500">Active Groups</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($totalSavings); ?> RWF</div><div class="text-xs text-gray-500">Total Savings</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo $activeLoans; ?></div><div class="text-xs text-gray-500">Active Loans</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#fef3c7;"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo $newUsers; ?></div><div class="text-xs text-gray-500">New Users (7d)</div></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Recent Groups</h3><?php foreach($recentGroups as $g): ?><div class="flex justify-between py-2 border-b border-gray-50 last:border-0"><div><strong><?php echo htmlspecialchars($g['group_name']); ?></strong><br><span class="text-xs text-gray-500"><?php echo htmlspecialchars($g['district'].', '.$g['sector']); ?> — <?php echo htmlspecialchars($g['owner']); ?></span></div><span class="text-xs text-gray-400"><?php echo date('d M', strtotime($g['created_at'])); ?></span></div><?php endforeach; ?></div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Quick Access</h3><div class="space-y-2"><a href="admin/users.php" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Manage Users</a><a href="groups/manage.php" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition">View All Groups</a><a href="loans/manage.php" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition">View All Loans</a></div></div>
</div>

<!-- ============ GROUP ADMIN DASHBOARD ============ -->
<?php elseif ($isGroupAdmin): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($totalMembers); ?></div><div class="text-xs text-gray-500">Members</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($groupSavings); ?> RWF</div><div class="text-xs text-gray-500">Total Savings</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo $activeGroupLoans; ?></div><div class="text-xs text-gray-500">Active Loans</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#fef3c7;"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo $pendingRequests; ?></div><div class="text-xs text-gray-500">Pending Requests</div></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2"><div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Recent Savings</h3><?php if(empty($recentSavings)): ?><p class="text-gray-500 text-sm">No savings yet.</p><?php else: ?><?php foreach($recentSavings as $s): ?><div class="flex justify-between py-2 border-b border-gray-50 last:border-0"><span class="text-sm"><?php echo htmlspecialchars($s['member_id']); ?> — <?php echo htmlspecialchars($s['fullname']); ?></span><span class="font-semibold"><?php echo number_format($s['amount']); ?> RWF</span><span class="text-xs text-gray-400"><?php echo date('d M', strtotime($s['payment_date'])); ?></span></div><?php endforeach; ?><?php endif; ?></div></div>
    <div class="space-y-4"><div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Actions</h3><div class="space-y-2"><a href="members/manage.php?group_id=<?php echo $groupId; ?>" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Manage Members</a><a href="loans/review.php?group_id=<?php echo $groupId; ?>" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition">Review Loans</a><a href="groups/edit.php?id=<?php echo $groupId; ?>" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition">Edit Rules</a></div></div></div>
</div>
<?php if ($isGroupAdmin && $groupId): 
    require_once __DIR__ . '/config/ai_engine.php';
    $aiInsights = getGroupAIInsights($pdo, $groupId);
?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        AI Insights
    </h3>
    
    <!-- Group Health -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-3xl font-extrabold <?php echo $aiInsights['health']['score'] >= 70 ? 'text-green-600' : ($aiInsights['health']['score'] >= 40 ? 'text-amber-600' : 'text-red-600'); ?>"><?php echo $aiInsights['health']['score']; ?>%</div>
            <div class="text-xs text-gray-500">Group Health — <?php echo $aiInsights['health']['label']; ?></div>
        </div>
        <div class="bg-gray-50 rounded-xl p-4 text-center">
            <div class="text-3xl font-extrabold text-brand-600"><?php echo count($aiInsights['top_performers']); ?></div>
            <div class="text-xs text-gray-500">Active Members Analyzed</div>
        </div>
    </div>
    
    <!-- At Risk Members -->
    <?php if (!empty($aiInsights['at_risk'])): ?>
    <div class="mb-3">
        <p class="text-xs font-semibold text-red-600 uppercase tracking-wider mb-2">Members Needing Attention</p>
        <?php foreach($aiInsights['at_risk'] as $risk): ?>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-50 last:border-0 text-sm">
                <span><?php echo htmlspecialchars($risk['member_id']); ?> — <?php echo htmlspecialchars($risk['fullname']); ?></span>
                <span class="font-semibold <?php echo $risk['score_value'] >= 50 ? 'text-amber-600' : 'text-red-600'; ?>"><?php echo $risk['score_value']; ?>%</span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Top Performers -->
    <?php if (!empty($aiInsights['top_performers'])): ?>
    <div>
        <p class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-2">Top Performers</p>
        <?php foreach(array_slice($aiInsights['top_performers'], 0, 3) as $top): ?>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-50 last:border-0 text-sm">
                <span><?php echo htmlspecialchars($top['member_id']); ?> — <?php echo htmlspecialchars($top['fullname']); ?></span>
                <span class="font-semibold text-green-600"><?php echo $top['score_value']; ?>%</span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<!-- ============ ASSISTANT ADMIN DASHBOARD ============ -->
<?php elseif ($isAssistantAdmin): ?>
<?php
// Get personal stats for Assistant Admin
$gid = $groupId;
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings WHERE member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL)");
$stmt->execute([$uid, $gid]); $mySavings = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL) AND status IN ('active','approved')");
$stmt->execute([$uid, $gid]); $myLoans = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM fines WHERE member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL) AND status = 'pending'");
$stmt->execute([$uid, $gid]); $myFines = $stmt->fetchColumn();
?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($mySavings); ?> RWF</div><div class="text-xs text-gray-500">My Savings</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo $myLoans; ?></div><div class="text-xs text-gray-500">My Loans</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($totalMembers); ?></div><div class="text-xs text-gray-500">Group Members</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#fef3c7;"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($groupSavings); ?> RWF</div><div class="text-xs text-gray-500">Group Savings</div></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2"><div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Recent Savings</h3><?php if(empty($recentSavings)): ?><p class="text-gray-500 text-sm">No savings yet.</p><?php else: ?><?php foreach($recentSavings as $s): ?><div class="flex justify-between py-2 border-b border-gray-50 last:border-0"><span class="text-sm"><?php echo htmlspecialchars($s['fullname']); ?></span><span class="font-semibold"><?php echo number_format($s['amount']); ?> RWF</span><span class="text-xs text-gray-400"><?php echo date('d M', strtotime($s['payment_date'])); ?></span></div><?php endforeach; ?><?php endif; ?></div></div>
    <div class="space-y-4"><div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Actions</h3><div class="space-y-2"><a href="members/manage.php?group_id=<?php echo $groupId; ?>" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Manage Members</a><a href="loans/review.php?group_id=<?php echo $groupId; ?>" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition">Review Loans</a></div></div></div>
</div>

<!-- ============ TREASURER DASHBOARD ============ -->
<?php elseif ($isTreasurer): ?>
<?php
// Get personal stats for Treasurer
$gid = $groupId;
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings WHERE member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL)");
$stmt->execute([$uid, $gid]); $mySavings = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = (SELECT id FROM group_members WHERE user_id = ? AND group_id = ? AND deleted_at IS NULL) AND status IN ('active','approved')");
$stmt->execute([$uid, $gid]); $myLoans = $stmt->fetchColumn();
?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($mySavings); ?> RWF</div><div class="text-xs text-gray-500">My Savings</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo $myLoans; ?></div><div class="text-xs text-gray-500">My Loans</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($groupSavings); ?> RWF</div><div class="text-xs text-gray-500">Group Savings</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#fef3c7;"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo $pendingDisbursements; ?></div><div class="text-xs text-gray-500">To Disburse</div></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2"><div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Recent Savings</h3><?php if(empty($recentSavings)): ?><p class="text-gray-500 text-sm">No savings yet.</p><?php else: ?><?php foreach($recentSavings as $s): ?><div class="flex justify-between py-2 border-b border-gray-50 last:border-0"><span class="text-sm"><?php echo htmlspecialchars($s['fullname']); ?></span><span class="font-semibold"><?php echo number_format($s['amount']); ?> RWF</span><span class="text-xs text-gray-400"><?php echo date('d M', strtotime($s['payment_date'])); ?></span></div><?php endforeach; ?><?php endif; ?></div></div>
    <div class="space-y-4"><div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Actions</h3><div class="space-y-2"><a href="savings/record.php?group_id=<?php echo $groupId; ?>" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Record Savings</a><a href="loans/disburse.php?group_id=<?php echo $groupId; ?>" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition">Disburse Loans</a></div></div></div>
</div>

<!-- ============ MEMBER DASHBOARD ============ -->
<?php elseif ($isMember): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo number_format($mySavings); ?> RWF</div><div class="text-xs text-gray-500">My Savings</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo $myActiveLoans; ?></div><div class="text-xs text-gray-500">Active Loans</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#fef3c7;"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo $myPendingFines; ?></div><div class="text-xs text-gray-500">Pending Fines</div></div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm"><div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:#ccfbf1;"><svg class="w-5 h-5" style="color:#0F766E;" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div class="text-2xl font-extrabold text-gray-900"><?php echo htmlspecialchars($memberInfo['member_id']); ?></div><div class="text-xs text-gray-500">My ID</div></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Group Info</h3><div class="space-y-2 text-sm"><div class="flex justify-between"><span class="text-gray-500">Group:</span> <strong><?php echo htmlspecialchars($memberInfo['group_name']); ?></strong></div><div class="flex justify-between"><span class="text-gray-500">Contribution:</span> <strong><?php echo number_format($memberInfo['contribution_amount']); ?> RWF / <?php echo ucfirst($memberInfo['contribution_frequency']); ?></strong></div></div></div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"><h3 class="font-bold text-gray-900 mb-3">Actions</h3><div class="space-y-2"><a href="loans/request.php" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Request a Loan</a><a href="report.php" class="block w-full text-center px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition">View My Report</a></div></div>
</div>

<?php else: ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center"><p class="text-gray-500 text-sm">You are not a member of any group yet.</p></div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>