<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: /Ikimina_ai/auth.php'); exit; }
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'] ?? 'User';
$current_user_role = $_SESSION['user_role'] ?? 'member';

$page_title = 'Savings';
$base_path = '../';

// Get group role
$stmt = $pdo->prepare("SELECT role_in_group, group_id FROM group_members WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
$stmt->execute([$current_user_id]);
$memberData = $stmt->fetch();
$groupRole = $memberData['role_in_group'] ?? null;
$groupId = $memberData['group_id'] ?? null;

// Check permission
if (!in_array($groupRole, ['group_admin', 'assistant_admin', 'treasurer'])) {
    header('Location: /Ikimina_ai/dashboard.php'); exit;
}

$message = $_GET['msg'] ?? '';
$search = $_GET['search'] ?? '';

// Get group rules
$groupRules = null;
if ($groupId) {
    $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupRules = $stmt->fetch();
}

// Get member savings sheets with search
$memberSheets = [];
if ($groupId) {
    $searchQuery = '';
    $params = [$groupId];
    if ($search) {
        $searchQuery = " AND (u.fullname LIKE ? OR gm.member_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $stmt = $pdo->prepare("
        SELECT gm.id as membership_id, gm.member_id, u.fullname, u.phone,
            COALESCE(SUM(CASE WHEN s.amount > 0 THEN s.amount ELSE 0 END), 0) as total_saved,
            COALESCE(SUM(CASE WHEN s.amount < 0 THEN -s.amount ELSE 0 END), 0) as total_withdrawn,
            COALESCE(SUM(s.amount), 0) as net_balance,
            COUNT(CASE WHEN s.amount > 0 THEN 1 END) as savings_count,
            MAX(CASE WHEN s.amount > 0 THEN s.payment_date END) as last_payment,
            COALESCE(SUM(CASE WHEN s.savings_type = 'payout' AND s.payment_date > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN -s.amount ELSE 0 END), 0) as recent_payout
        FROM group_members gm
        JOIN users u ON gm.user_id = u.id
        LEFT JOIN savings s ON gm.id = s.member_id AND s.group_id = ?
        WHERE gm.group_id = ? AND gm.deleted_at IS NULL
        $searchQuery
        GROUP BY gm.id, gm.member_id, u.fullname, u.phone
        ORDER BY gm.member_id ASC
    ");
    $stmt->execute(array_merge([$groupId], $params));
    $memberSheets = $stmt->fetchAll();
}

// Get recent transactions for selected member
$selectedMember = $_GET['member'] ?? null;
$recentTransactions = [];
if ($selectedMember) {
    $stmt = $pdo->prepare("
        SELECT s.*, u.fullname as recorded_by_name
        FROM savings s 
        JOIN users u ON s.recorded_by = u.id
        WHERE s.member_id = ? AND s.group_id = ?
        ORDER BY s.created_at DESC LIMIT 20
    ");
    $stmt->execute([$selectedMember, $groupId]);
    $recentTransactions = $stmt->fetchAll();
}

// Handle quick record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_record'])) {
    $memberId = $_POST['member_id'];
    $amount = $_POST['amount'];
    $savingsType = $_POST['savings_type'] ?? 'monthly';
    $notes = trim($_POST['notes'] ?? '');
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    
    if ($memberId && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO savings (member_id, group_id, amount, savings_type, payment_date, recorded_by, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$memberId, $groupId, $amount, $savingsType, $paymentDate, $current_user_id, $notes]);
        header('Location: record.php?group_id=' . $groupId . '&msg=recorded'); exit;
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($message === 'recorded'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Savings recorded successfully.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">Member Savings Sheets</h3>
    <p class="text-gray-500 text-sm mt-1">One row per member. Click to record or view history.</p>
</div>

<?php if ($groupRules): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
    <div class="flex flex-wrap gap-4 text-sm">
        <div><span class="text-gray-500">Expected:</span> <strong><?php echo number_format($groupRules['contribution_amount']); ?> RWF / <?php echo ucfirst($groupRules['contribution_frequency']); ?></strong></div>
        <div><span class="text-gray-500">Group:</span> <strong><?php echo htmlspecialchars($groupRules['group_name']); ?></strong></div>
    </div>
</div>
<?php endif; ?>

<!-- Search -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
    <form method="GET" class="flex gap-3">
        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or ID..." class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 transition">
        <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Search</button>
        <?php if($search): ?><a href="?group_id=<?php echo $groupId; ?>" class="px-4 py-2.5 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition no-underline">Clear</a><?php endif; ?>
    </form>
</div>

<!-- Member Sheets Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <?php foreach($memberSheets as $ms): 
        $expected = $groupRules['contribution_amount'] ?? 0;
        $compliance = $expected > 0 ? min(100, round(($ms['net_balance'] / max(1, $expected)) * 100)) : 100;
        $statusColor = $compliance >= 80 ? 'border-green-200 bg-green-50' : ($compliance >= 50 ? 'border-amber-200 bg-amber-50' : 'border-red-200 bg-red-50');
    ?>
    <div class="bg-white rounded-2xl border shadow-sm hover:shadow-md transition cursor-pointer <?php echo $statusColor; ?>" onclick="openRecordModal(<?php echo $ms['membership_id']; ?>, '<?php echo htmlspecialchars(addslashes($ms['member_id'])); ?>', '<?php echo htmlspecialchars(addslashes($ms['fullname'])); ?>')">
        <div class="p-4">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($ms['member_id']); ?></span>
                    <h4 class="font-bold text-gray-900 mt-1"><?php echo htmlspecialchars($ms['fullname']); ?></h4>
                </div>
                <span class="text-xs font-semibold <?php echo $compliance >= 80 ? 'text-green-600' : ($compliance >= 50 ? 'text-amber-600' : 'text-red-600'); ?>"><?php echo $compliance; ?>%</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div><span class="text-gray-500">Saved:</span> <strong><?php echo number_format($ms['total_saved']); ?> RWF</strong></div>
                <div><span class="text-gray-500">Times:</span> <strong><?php echo $ms['savings_count']; ?></strong></div>
                <div><span class="text-gray-500">Net:</span> <strong class="<?php echo $ms['net_balance'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo number_format($ms['net_balance']); ?> RWF</strong></div>
                <div><span class="text-gray-500">Last:</span> <strong><?php echo $ms['last_payment'] ? date('d M', strtotime($ms['last_payment'])) : 'Never'; ?></strong></div>
            </div>
            <?php if($ms['recent_payout'] > 0): ?>
            <div class="mt-2 pt-2 border-t border-gray-100">
                <span class="text-xs text-brand-600 font-semibold">Recent payout: +<?php echo number_format($ms['recent_payout']); ?> RWF</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick Record Modal -->
<div id="recordModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl">
        <div class="flex justify-between items-center p-5 border-b">
            <h3 class="font-bold text-lg">Record Savings</h3>
            <button onclick="closeRecordModal()" class="text-2xl text-gray-400 hover:text-gray-800">&times;</button>
        </div>
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
            <input type="hidden" name="member_id" id="modalMemberId">
            <div class="bg-brand-50 rounded-xl p-3 text-center">
                <p class="text-sm font-bold text-brand-600" id="modalMemberName"></p>
                <p class="text-xs text-gray-500" id="modalMemberIdDisplay"></p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Amount (RWF)</label>
                <input type="number" name="amount" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo $groupRules['contribution_amount'] ?? ''; ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Payment Date</label>
                <input type="date" name="payment_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Type</label>
                <select name="savings_type" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition">
                    <option value="weekly">Weekly</option>
                    <option value="monthly" selected>Monthly</option>
                    <option value="special">Special</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Notes</label>
                <input type="text" name="notes" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Optional note...">
            </div>
            <div class="flex gap-3">
                <button type="submit" name="quick_record" class="flex-1 py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Record Savings</button>
                <a href="?group_id=<?php echo $groupId; ?>&member=" id="viewHistoryLink" class="px-4 py-3 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition no-underline">History</a>
            </div>
        </form>
    </div>
</div>

<!-- Recent Transactions for Selected Member -->
<?php if ($selectedMember && !empty($recentTransactions)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-50 flex justify-between items-center">
        <h3 class="font-bold text-gray-900">Transaction History</h3>
        <a href="?group_id=<?php echo $groupId; ?>" class="text-xs text-brand-600 font-semibold hover:underline">Close</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr><th>Amount</th><th>Type</th><th>Date</th><th>By</th></tr></thead>
            <tbody>
                <?php foreach($recentTransactions as $t): ?>
                <tr>
                    <td class="font-semibold <?php echo $t['amount'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo $t['amount'] >= 0 ? '+' : ''; ?><?php echo number_format($t['amount']); ?> RWF</td>
                    <td><span class="inline-block px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600"><?php echo ucfirst($t['savings_type']); ?></span></td>
                    <td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($t['payment_date'])); ?></td>
                    <td class="text-xs text-gray-400"><?php echo htmlspecialchars($t['recorded_by_name']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function openRecordModal(memberId, memberCode, memberName) {
    document.getElementById('modalMemberId').value = memberId;
    document.getElementById('modalMemberName').textContent = memberName;
    document.getElementById('modalMemberIdDisplay').textContent = memberCode;
    document.getElementById('viewHistoryLink').href = '?group_id=<?php echo $groupId; ?>&member=' + memberId;
    document.getElementById('recordModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeRecordModal() {
    document.getElementById('recordModal').classList.add('hidden');
    document.body.style.overflow = '';
}
document.getElementById('recordModal').addEventListener('click', function(e) {
    if(e.target === this) closeRecordModal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>