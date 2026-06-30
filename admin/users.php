<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($current_user_role !== 'super_admin') {
    header('Location: ../dashboard.php'); exit;
}

$page_title = 'All Users';
$base_path = '../';

$stmt = $pdo->query("SELECT u.*, gm.member_id, g.group_name FROM users u LEFT JOIN group_members gm ON u.id = gm.user_id AND gm.deleted_at IS NULL LEFT JOIN `groups` g ON gm.group_id = g.id ORDER BY u.created_at DESC");
$users = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">All Users</h3>
    <p class="text-gray-500 text-sm mt-1">Platform user management.</p>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Group</th><th>Member ID</th><th>Status</th><th>Joined</th></tr></thead>
            <tbody>
                <?php foreach($users as $u): 
                    $rc = $u['role'] === 'super_admin' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600';
                ?>
                <tr>
                    <td class="font-semibold text-sm"><?php echo htmlspecialchars($u['fullname']); ?></td>
                    <td class="text-sm"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td class="text-sm"><?php echo htmlspecialchars($u['phone']); ?></td>
                    <td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $rc; ?>"><?php echo ucfirst(str_replace('_',' ',$u['role'])); ?></span></td>
                    <td class="text-sm"><?php echo htmlspecialchars($u['group_name'] ?? '-'); ?></td>
                    <td><span class="text-xs font-bold text-brand-600"><?php echo htmlspecialchars($u['member_id'] ?? '-'); ?></span></td>
                    <td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $u['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                    <td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>