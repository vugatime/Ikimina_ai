<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Members';
$base_path = '../';

$isSuperAdmin = ($current_user_role === 'super_admin');
$groupId = $_GET['group_id'] ?? null;
$message = $_GET['msg'] ?? '';

// Get groups
if ($isSuperAdmin) {
    $stmt = $pdo->query("SELECT id, group_name FROM `groups` ORDER BY group_name ASC");
} else {
    if ($current_user_role === 'member') { header('Location: ../dashboard.php'); exit; }
    $stmt = $pdo->prepare("SELECT g.id, g.group_name FROM `groups` g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ? AND gm.deleted_at IS NULL AND gm.role_in_group IN ('group_admin','assistant_admin') ORDER BY g.group_name ASC");
    $stmt->execute([$current_user_id]);
}
$myGroups = $stmt->fetchAll();

if (!$groupId && !empty($myGroups)) $groupId = $myGroups[0]['id'];

// Get group info
$groupInfo = null;
$groupPrefix = '';
if ($groupId) {
    $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupInfo = $stmt->fetch();
    if ($groupInfo) {
        $groupPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $groupInfo['group_name']), 0, 3));
        if (strlen($groupPrefix) < 3) $groupPrefix = str_pad($groupPrefix, 3, 'X');
    }
}

// Get members
$members = [];
if ($groupId) {
    $stmt = $pdo->prepare("SELECT gm.id as membership_id, gm.member_id, gm.role_in_group, gm.joined_at, u.fullname, u.phone FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ? AND gm.deleted_at IS NULL ORDER BY FIELD(gm.role_in_group, 'group_admin','assistant_admin','treasurer','member'), gm.member_id ASC");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll();
}

// All members for Super Admin
$allMembers = [];
if ($isSuperAdmin && !$groupId) {
    $stmt = $pdo->query("SELECT gm.member_id, gm.role_in_group, u.fullname, u.phone, u.email, g.group_name FROM group_members gm JOIN users u ON gm.user_id = u.id JOIN `groups` g ON gm.group_id = g.id WHERE gm.deleted_at IS NULL ORDER BY g.group_name, gm.member_id ASC LIMIT 100");
    $allMembers = $stmt->fetchAll();
}

$roleColors = [
    'group_admin' => ['bg' => '#dbeafe', 'text' => '#1e40af', 'label' => 'Group Admin'],
    'assistant_admin' => ['bg' => '#ccfbf1', 'text' => '#0F766E', 'label' => 'Assistant Admin'],
    'treasurer' => ['bg' => '#fef3c7', 'text' => '#92400e', 'label' => 'Treasurer'],
    'member' => ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => 'Member'],
];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($message === 'added'): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Member added.</div>
<?php elseif ($message === 'updated'): ?><div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-5 py-3 rounded-xl text-sm font-medium">Member updated.</div>
<?php elseif ($message === 'removed'): ?><div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm font-medium">Member removed.</div><?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-extrabold text-gray-900"><?php echo $isSuperAdmin ? 'All Members' : 'Members'; ?></h3>
    <?php if (!$isSuperAdmin && $groupId): ?>
    <button onclick="openAddModal()" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">+ Add Member</button>
    <?php endif; ?>
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
    <!-- All Members Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">All Members (<?php echo count($allMembers); ?>)</h3></div>
        <?php if(empty($allMembers)): ?><div class="p-10 text-center"><p class="text-gray-500 text-sm">No members yet.</p></div>
        <?php else: ?><div class="overflow-x-auto"><table class="w-full"><thead><tr><th>Member ID</th><th>Name</th><th>Phone</th><th>Group</th><th>Role</th></tr></thead><tbody>
        <?php foreach($allMembers as $m): $rc=$roleColors[$m['role_in_group']]??$roleColors['member']; ?>
        <tr><td><span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($m['member_id']); ?></span></td><td class="font-semibold text-sm"><?php echo htmlspecialchars($m['fullname']); ?></td><td class="text-sm"><?php echo htmlspecialchars($m['phone']?:'N/A'); ?></td><td class="text-sm"><?php echo htmlspecialchars($m['group_name']); ?></td><td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" style="background:<?php echo $rc['bg']; ?>;color:<?php echo $rc['text']?>;"><?php echo $rc['label']; ?></span></td></tr>
        <?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div>
<?php elseif ($groupId): ?>
    <!-- Group Members Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-50 flex justify-between items-center"><h3 class="font-bold text-gray-900">Member List (<?php echo count($members); ?>)</h3></div>
        <?php if(empty($members)): ?><div class="p-10 text-center"><p class="text-gray-500 text-sm">No members yet.</p><?php if(!$isSuperAdmin): ?><button onclick="openAddModal()" class="mt-3 px-4 py-2 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Add First Member</button><?php endif; ?></div>
        <?php else: ?><div class="overflow-x-auto"><table class="w-full"><thead><tr><th>Member ID</th><th>Name</th><th>Phone</th><th>Role</th><?php if(!$isSuperAdmin): ?><th>Actions</th><?php endif; ?></tr></thead><tbody>
        <?php foreach($members as $m): $rc=$roleColors[$m['role_in_group']]??$roleColors['member']; ?>
        <tr><td><span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($m['member_id']); ?></span></td><td class="font-semibold text-sm"><?php echo htmlspecialchars($m['fullname']); ?></td><td class="text-sm"><?php echo htmlspecialchars($m['phone']?:'N/A'); ?></td><td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" style="background:<?php echo $rc['bg']; ?>;color:<?php echo $rc['text']?>;"><?php echo $rc['label']; ?></span></td>
        <?php if(!$isSuperAdmin): ?><td><div class="flex gap-1"><button onclick="openEditModal(<?php echo $m['membership_id']; ?>,'<?php echo htmlspecialchars(addslashes($m['fullname'])); ?>','<?php echo htmlspecialchars($m['phone']); ?>','<?php echo $m['role_in_group']; ?>')" class="px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50 rounded">Edit</button><?php if($m['role_in_group']!=='group_admin'): ?><button onclick="removeMember(<?php echo $m['membership_id']; ?>)" class="px-2 py-1 text-xs font-medium text-red-500 hover:bg-red-50 rounded">Remove</button><?php endif; ?></div></td><?php endif; ?></tr>
        <?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div>
<?php endif; ?>

<!-- ADD/EDIT MODALS - Only for non-Super Admin -->
<?php if (!$isSuperAdmin && $groupId): ?>
<div id="addModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl"><div class="flex justify-between items-center p-5 border-b"><h3 class="font-bold text-lg">Add Member</h3><button onclick="closeAddModal()" class="text-2xl text-gray-400 hover:text-gray-800">&times;</button></div>
        <form action="manage_process.php" method="POST" class="p-5 space-y-4"><input type="hidden" name="action" value="add"><input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
            <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name</label><input type="text" name="fullname" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Phone</label><input type="text" name="phone" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label><select name="role_in_group" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition"><option value="member">Member</option><option value="assistant_admin">Assistant Admin</option><option value="treasurer">Treasurer</option></select></div>
            <button type="submit" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Add Member</button>
        </form>
    </div>
</div>
<div id="editModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl"><div class="flex justify-between items-center p-5 border-b"><h3 class="font-bold text-lg">Edit Member</h3><button onclick="closeEditModal()" class="text-2xl text-gray-400 hover:text-gray-800">&times;</button></div>
        <form action="manage_process.php" method="POST" class="p-5 space-y-4"><input type="hidden" name="action" value="edit"><input type="hidden" name="group_id" value="<?php echo $groupId; ?>"><input type="hidden" name="membership_id" id="editMembershipId">
            <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name</label><input type="text" name="fullname" id="editName" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Phone</label><input type="text" name="phone" id="editPhone" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition"></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label><select name="role_in_group" id="editRole" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition"><option value="member">Member</option><option value="assistant_admin">Assistant Admin</option><option value="treasurer">Treasurer</option></select></div>
            <button type="submit" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Save Changes</button>
        </form>
    </div>
</div>
<script>
function openAddModal(){document.getElementById('addModal').classList.remove('hidden');document.body.style.overflow='hidden';}
function closeAddModal(){document.getElementById('addModal').classList.add('hidden');document.body.style.overflow='';}
function openEditModal(id,name,phone,role){document.getElementById('editMembershipId').value=id;document.getElementById('editName').value=name;document.getElementById('editPhone').value=phone;document.getElementById('editRole').value=role;document.getElementById('editModal').classList.remove('hidden');document.body.style.overflow='hidden';}
function closeEditModal(){document.getElementById('editModal').classList.add('hidden');document.body.style.overflow='';}
function removeMember(id){Swal.fire({title:'Remove Member?',text:'This action is logged.',icon:'warning',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#64748b',confirmButtonText:'Yes, remove'}).then((result)=>{if(result.isConfirmed){window.location.href='manage_process.php?action=remove&id='+id+'&group_id=<?php echo $groupId; ?>';}});}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>