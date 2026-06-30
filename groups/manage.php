<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
$page_title = 'My Groups';
$base_path = '../';

if ($current_user_role === 'super_admin') {
    $stmt = $pdo->query("SELECT g.*, u.fullname as owner_name, (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND deleted_at IS NULL) as mc FROM `groups` g JOIN users u ON g.created_by = u.id ORDER BY g.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT g.*, (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND deleted_at IS NULL) as mc FROM `groups` g WHERE g.created_by = ? ORDER BY g.created_at DESC");
    $stmt->execute([$current_user_id]);
}
$groups = $stmt->fetchAll();
$created = $_GET['created'] ?? '';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($created == '1'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Group created successfully!
</div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">
        <?php echo $current_user_role === 'super_admin' ? 'All Groups' : 'My Groups'; ?>
    </h3>
    <?php if ($current_user_role !== 'super_admin'): ?>
    <a href="create.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition no-underline" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">+ Create Group</a>
    <?php endif; ?>
</div>

<?php if(empty($groups)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
        <p class="text-gray-500 text-sm">No groups yet.</p>
        <a href="create.php" class="inline-block mt-3 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Create Your First Group</a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach($groups as $g): 
            $freq = $g['contribution_frequency'] ?? 'monthly';
            $freqLabel = ['daily' => 'Day', 'weekly' => 'Week', 'biweekly' => '2 Weeks', 'monthly' => 'Month'];
            $freqText = $freqLabel[$freq] ?? 'Month';
        ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($g['group_name']); ?></h4>
                    <?php if ($current_user_role !== 'super_admin'): ?>
                    <a href="edit.php?id=<?php echo $g['id']; ?>" class="text-xs text-brand-600 hover:underline font-medium flex-shrink-0 ml-2">Edit Rules</a>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    <?php echo htmlspecialchars($g['district'] . ', ' . $g['sector']); ?>
                    <?php if ($current_user_role === 'super_admin' && isset($g['owner_name'])): ?>
                        <br>Owner: <?php echo htmlspecialchars($g['owner_name']); ?>
                    <?php endif; ?>
                </p>
                <div class="flex gap-4 mt-3 text-xs text-gray-600">
                    <span><?php echo $g['mc']; ?> members</span>
                    <span><?php echo number_format($g['contribution_amount']); ?> RWF/<?php echo $freqText; ?></span>
                </div>
                <div class="flex gap-2 mt-3 pt-3 border-t border-gray-50">
                    <a href="../dashboard.php?group_id=<?php echo $g['id']; ?>" class="flex-1 text-center px-3 py-2 rounded-lg text-xs font-semibold text-white transition" style="background:#0F766E;">Open Dashboard</a>
                    <?php if ($current_user_role !== 'super_admin'): ?>
                    <a href="edit.php?id=<?php echo $g['id']; ?>" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition no-underline">Settings</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>