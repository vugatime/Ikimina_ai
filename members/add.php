<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
$page_title = 'Add Member';
$base_path = '../';
$groupId = $_GET['group_id'] ?? null;

if (!$groupId) { header('Location: manage.php'); exit; }

// Verify user has access to this group
if ($current_user_role !== 'super_admin') {
    $stmt = $pdo->prepare("SELECT id FROM `groups` WHERE id = ? AND created_by = ?");
    $stmt->execute([$groupId, $current_user_id]);
    if (!$stmt->fetch()) { header('Location: manage.php'); exit; }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-lg text-gray-900 mb-1">Add New Member</h3>
        <p class="text-sm text-gray-500 mb-5">Invite someone to join your savings group.</p>
        
        <form action="add_process.php" method="POST" class="space-y-4">
            <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name</label>
                <input type="text" name="fullname" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., ISHIMWE Samuel" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Phone Number</label>
                <input type="text" name="phone" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 0788504503" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address (Optional)</label>
                <input type="email" name="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Leave empty to auto-generate">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Role in Group</label>
                <select name="role_in_group" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required>
                    <option value="member">Member</option>
                    <option value="treasurer">Treasurer</option>
                    <option value="group_admin">Group Admin</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" name="add_member" class="px-6 py-3 rounded-xl text-sm font-semibold text-white transition-all" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">Add Member</button>
                <a href="manage.php?group_id=<?php echo $groupId; ?>" class="px-6 py-3 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition no-underline inline-flex items-center">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>