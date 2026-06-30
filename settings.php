<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
$page_title = 'Settings';
$base_path = '';

$uid = $current_user_id;
$urole = $current_user_role;
$msg = $_GET['msg'] ?? '';

// Get group settings if admin
$groupSettings = null;
if ($urole !== 'super_admin') {
    $stmt = $pdo->prepare("SELECT g.* FROM `groups` g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ? AND gm.role_in_group = 'group_admin' AND gm.deleted_at IS NULL LIMIT 1");
    $stmt->execute([$uid]);
    $groupSettings = $stmt->fetch();
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php if ($msg === 'saved'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Settings saved successfully.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">Settings</h3>
</div>

<div class="space-y-6">
    <!-- System Information -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">System Information</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div><span class="text-gray-500">Platform:</span> <strong>IkiminaAI v1.0</strong></div>
            <div><span class="text-gray-500">PHP Version:</span> <strong><?php echo phpversion(); ?></strong></div>
            <div><span class="text-gray-500">Database:</span> <strong>MySQL/MariaDB</strong></div>
            <div><span class="text-gray-500">Environment:</span> <strong>Development</strong></div>
        </div>
    </div>

    <?php if ($groupSettings): ?>
    <!-- Group Rules Summary -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">Group Rules: <?php echo htmlspecialchars($groupSettings['group_name']); ?></h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Savings</p>
                <p><strong><?php echo number_format($groupSettings['contribution_amount']); ?> RWF</strong> / <?php echo ucfirst($groupSettings['contribution_frequency']); ?></p>
                <p class="text-xs text-gray-400 mt-1">Expected: <?php echo htmlspecialchars($groupSettings['expected_day']); ?></p>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Penalties</p>
                <p><strong><?php echo $groupSettings['late_penalty_value']; ?><?php echo $groupSettings['late_penalty_type'] == 'percent' ? '%' : ' RWF'; ?></strong> / <?php echo ucfirst($groupSettings['late_penalty_frequency']); ?></p>
                <p class="text-xs text-gray-400 mt-1">Grace: <?php echo $groupSettings['grace_period_days']; ?> days</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Fines</p>
                <p>Absence: <strong><?php echo number_format($groupSettings['meeting_absence_fine']); ?> RWF</strong></p>
                <p>Late: <strong><?php echo number_format($groupSettings['meeting_late_fine']); ?> RWF</strong></p>
            </div>
        </div>
        <a href="groups/create.php?edit=<?php echo $groupSettings['id']; ?>" class="inline-block mt-4 px-4 py-2 rounded-xl text-sm font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 transition no-underline">Edit Group Rules</a>
    </div>
    <?php endif; ?>

    <!-- Notification Preferences -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">Notification Preferences</h3>
        <p class="text-sm text-gray-500 mb-4">Email notifications are sent to: <strong><?php echo htmlspecialchars($current_user_email ?? 'Not set'); ?></strong></p>
        <div class="space-y-3">
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" checked class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span>Savings recorded notifications</span>
            </label>
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" checked class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span>Loan application updates</span>
            </label>
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" checked class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span>Meeting reminders</span>
            </label>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>