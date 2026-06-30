<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
$page_title = 'My Profile';
$base_path = '';

$uid = $current_user_id;
$msg = $_GET['msg'] ?? '';

// Get user info
$stmt = $pdo->prepare("SELECT u.*, gm.member_id, gm.role_in_group, g.group_name 
                       FROM users u 
                       LEFT JOIN group_members gm ON u.id = gm.user_id AND gm.deleted_at IS NULL 
                       LEFT JOIN `groups` g ON gm.group_id = g.id 
                       WHERE u.id = ? LIMIT 1");
$stmt->execute([$uid]);
$user = $stmt->fetch();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php if ($msg === 'updated'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Profile updated successfully.</div>
<?php elseif ($msg === 'password_changed'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Password changed successfully.</div>
<?php elseif ($msg === 'password_mismatch'): ?>
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-5 py-3 rounded-xl text-sm font-medium">Current password is incorrect.</div>
<?php elseif ($msg === 'password_short'): ?>
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-5 py-3 rounded-xl text-sm font-medium">New password must be at least 6 characters.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">My Profile</h3>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Profile Info -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">Personal Information</h3>
        <form action="profile_process.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_profile">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name</label>
                <input type="text" name="fullname" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                <input type="email" name="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Phone</label>
                <input type="text" name="phone" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            <button type="submit" class="px-5 py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Save Changes</button>
        </form>
    </div>

    <!-- Password Change -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">Change Password</h3>
        <form action="profile_process.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="change_password">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Current Password</label>
                <input type="password" name="current_password" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Enter current password" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">New Password</label>
                <input type="password" name="new_password" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Min 6 characters" required minlength="6">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm New Password</label>
                <input type="password" name="confirm_password" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Repeat new password" required minlength="6">
            </div>
            <button type="submit" class="px-5 py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Change Password</button>
        </form>
    </div>
</div>

<!-- Account Info Card -->
<?php if ($user['member_id']): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mt-6">
    <h3 class="font-bold text-gray-900 mb-4">Account Information</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        <div><span class="text-gray-500">Member ID:</span> <strong style="color:#0F766E;"><?php echo htmlspecialchars($user['member_id']); ?></strong></div>
        <div><span class="text-gray-500">Group:</span> <strong><?php echo htmlspecialchars($user['group_name'] ?? 'N/A'); ?></strong></div>
        <div><span class="text-gray-500">Role:</span> <strong><?php echo ucfirst(str_replace('_', ' ', $user['role_in_group'] ?? 'N/A')); ?></strong></div>
        <div><span class="text-gray-500">Account Status:</span> <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span></div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>