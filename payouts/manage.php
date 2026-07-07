<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payout_engine.php';

if (!isset($_SESSION['user_id'])) { header('Location: /auth.php'); exit; }
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'] ?? 'User';
$current_user_role = $_SESSION['user_role'] ?? 'member';

$page_title = 'Kuzenguruka - Payouts';
$base_path = '../';

// Get user's group
$groupRole = null;
$groupId = null;
if ($current_user_role !== 'super_admin') {
    $stmt = $pdo->prepare("SELECT gm.role_in_group, gm.group_id FROM group_members gm WHERE gm.user_id = ? AND gm.deleted_at IS NULL LIMIT 1");
    $stmt->execute([$current_user_id]);
    $memberData = $stmt->fetch();
    if ($memberData) { $groupRole = $memberData['role_in_group']; $groupId = $memberData['group_id']; }
}

// Only Group Admin can manage payouts
if ($groupRole !== 'group_admin' && $current_user_role !== 'super_admin') {
    header('Location: /dashboard.php'); exit;
}

// Get groups
if ($current_user_role === 'super_admin') {
    $stmt = $pdo->query("SELECT id, group_name FROM `groups` ORDER BY group_name ASC");
    $myGroups = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT id, group_name FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $myGroups = $stmt->fetchAll();
}

$groupId = $_GET['group_id'] ?? $groupId ?? ($myGroups[0]['id'] ?? null);
$msg = $_GET['msg'] ?? '';

// Handle create cycle
$cycleResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_cycle'])) {
    $cycleName = trim($_POST['cycle_name']);
    $totalAmount = $_POST['total_amount'];
    $recipientsCount = $_POST['recipients_count'];
    $cycleResult = createPayoutCycle($pdo, $groupId, $cycleName, $totalAmount, $recipientsCount, $current_user_id);
}

// Get group info
$groupInfo = null;
if ($groupId) {
    $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupInfo = $stmt->fetch();
}

// Get total available savings
$totalSavings = 0;
if ($groupId) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE group_id = ?");
    $stmt->execute([$groupId]);
    $totalSavings = $stmt->fetchColumn();
}

// Get payout history
$payoutHistory = [];
if ($groupId) {
    $payoutHistory = getPayoutHistory($pdo, $groupId);
}

// Preview recipients
$preview = $_GET['preview'] ?? null;
$previewResult = null;
if ($preview && $groupId) {
    $amount = $_GET['amount'] ?? ($totalSavings > 0 ? $totalSavings : 100000);
    $count = $_GET['count'] ?? 1;
    $previewResult = selectPayoutRecipients($pdo, $groupId, $amount, $count);
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($cycleResult && isset($cycleResult['success'])): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Payout cycle created successfully! <?php echo count($cycleResult['recipients']); ?> recipient(s) selected by AI.
</div>
<?php endif; ?>

<?php if ($cycleResult && isset($cycleResult['error'])): ?>
<div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-5 py-3 rounded-xl text-sm font-medium">
    <?php echo htmlspecialchars($cycleResult['error']); ?>
</div>
<?php endif; ?>

<?php if ($cycleResult && !empty($cycleResult['blocked'])): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm">
    <p class="font-semibold mb-2">Members Not Eligible</p>
    <?php foreach($cycleResult['blocked'] as $b): ?>
    <div class="flex justify-between py-1 text-xs">
        <span><?php echo htmlspecialchars($b['member_id']); ?> — <?php echo htmlspecialchars($b['fullname']); ?></span>
        <span class="text-amber-700"><?php echo htmlspecialchars($b['blocked_reason'] ?? 'Not eligible'); ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($previewResult && !empty($previewResult['blocked'])): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm">
    <p class="font-semibold mb-2">Members Not Eligible</p>
    <?php foreach($previewResult['blocked'] as $b): ?>
    <div class="flex justify-between py-1 text-xs">
        <span><?php echo htmlspecialchars($b['member_id']); ?> — <?php echo htmlspecialchars($b['fullname']); ?></span>
        <span class="text-amber-700"><?php echo htmlspecialchars($b['blocked_reason'] ?? 'Not eligible'); ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
    <div>
        <h3 class="text-xl font-extrabold text-gray-900">Kuzenguruka — Rotating Payouts</h3>
        <p class="text-gray-500 text-sm mt-1">AI-powered fair distribution of group savings to members.</p>
    </div>
    <a href="generation.php?group_id=<?php echo $groupId; ?>" class="px-4 py-2 rounded-xl text-sm font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 transition no-underline">View All Generations</a>
</div>

<!-- Group Selector -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block">Select Group</label>
    <div class="flex flex-wrap gap-2">
        <?php foreach($myGroups as $g): ?>
            <a href="?group_id=<?php echo $g['id']; ?>" class="px-4 py-2 rounded-lg text-sm font-medium transition no-underline <?php echo ($groupId == $g['id']) ? 'text-white' : 'text-gray-600 bg-gray-50 hover:bg-gray-100'; ?>" style="<?php echo ($groupId == $g['id']) ? 'background:#0F766E;' : ''; ?>"><?php echo htmlspecialchars($g['group_name']); ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($groupInfo): ?>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold text-brand-600"><?php echo number_format($totalSavings); ?> RWF</div>
        <div class="text-xs text-gray-500 mt-0.5">Available Group Savings</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold text-gray-900"><?php echo count($payoutHistory); ?></div>
        <div class="text-xs text-gray-500 mt-0.5">Completed Cycles</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold text-gray-900"><?php echo $groupInfo['contribution_amount']; ?> RWF</div>
        <div class="text-xs text-gray-500 mt-0.5">Per Member / <?php echo ucfirst($groupInfo['contribution_frequency']); ?></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Create New Cycle -->
    <div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-bold text-gray-900 mb-4">Start New Payout Cycle</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Cycle Name</label>
                    <input type="text" name="cycle_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., Cycle 1 - July 2026" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Total Amount to Distribute (RWF)</label>
                    <input type="number" name="total_amount" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 150000" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Number of Recipients</label>
                    <select name="recipients_count" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition">
                        <option value="1">1 Person</option>
                        <option value="2">2 People</option>
                        <option value="3">3 People</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" name="create_cycle" class="flex-1 py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Run AI Selection & Create Cycle</button>
                    <a href="?group_id=<?php echo $groupId; ?>&preview=1&amount=<?php echo $totalSavings > 0 ? $totalSavings : 100000; ?>&count=1" class="px-4 py-3 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition no-underline">Preview</a>
                </div>
            </form>
        </div>
    </div>

    <!-- AI Preview -->
    <?php if ($preview && $previewResult && !empty($previewResult['selected'])): ?>
    <div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                AI Selection Preview
            </h3>
            <div class="space-y-3">
                <?php foreach($previewResult['selected'] as $r): ?>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($r['member_id']); ?> — <?php echo htmlspecialchars($r['fullname']); ?></span>
                        <span class="text-lg font-extrabold text-brand-600"><?php echo number_format($r['amount']); ?> RWF</span>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs text-gray-500">AI Score:</span>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?php echo ($r['ai_score'] ?? 0) >= 70 ? 'bg-green-100 text-green-700' : (($r['ai_score'] ?? 0) >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'); ?>"><?php echo $r['ai_score'] ?? 0; ?>%</span>
                    </div>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($r['reason'] ?? ''); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Payout History -->
<?php if (!empty($payoutHistory)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mt-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Payout History</h3></div>
    <div class="p-5 space-y-4">
        <?php foreach($payoutHistory as $cycle): ?>
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3">
                <div>
                    <span class="font-bold text-gray-900"><?php echo htmlspecialchars($cycle['cycle_name']); ?></span>
                    <a href="generation.php?group_id=<?php echo $groupId; ?>&gen=<?php echo $cycle['generation']; ?>" class="text-xs text-brand-600 hover:underline ml-2">View Gen <?php echo $cycle['generation']; ?> Sheet</a>
                    <span class="inline-block ml-2 px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $cycle['status'] === 'completed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>"><?php echo ucfirst($cycle['status']); ?></span>
                </div>
                <span class="text-sm font-semibold text-brand-600">Total: <?php echo number_format($cycle['total_amount']); ?> RWF</span>
            </div>
            <?php if (!empty($cycle['recipients'])): ?>
            <div class="space-y-2">
                <?php foreach($cycle['recipients'] as $rec): ?>
                <div class="flex justify-between items-center text-sm bg-white rounded-lg p-2">
                    <span><?php echo htmlspecialchars($rec['member_id'] ?? ''); ?> — <?php echo htmlspecialchars($rec['fullname'] ?? ''); ?></span>
                    <span class="font-semibold text-green-600">+<?php echo number_format($rec['amount_received'] ?? 0); ?> RWF</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>