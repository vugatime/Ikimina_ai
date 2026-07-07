<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// ROLE CHECK: Get group role
$stmt = $pdo->prepare("SELECT role_in_group FROM group_members WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
$stmt->execute([$current_user_id]);
$gr = $stmt->fetchColumn();
if ($current_user_role === 'super_admin') { header('Location: /Ikimina_ai/dashboard.php'); exit; }
if ($gr === 'member') { header('Location: /Ikimina_ai/dashboard.php'); exit; }

$page_title = 'Meetings';
$base_path = '../';

$groupId = $_GET['group_id'] ?? null;
$msg = $_GET['msg'] ?? '';

// Get user's groups where they can manage meetings
$stmt = $pdo->prepare("SELECT g.id, g.group_name FROM `groups` g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ? AND gm.deleted_at IS NULL AND gm.role_in_group IN ('group_admin','assistant_admin','treasurer') ORDER BY g.group_name ASC");
$stmt->execute([$current_user_id]);
$myGroups = $stmt->fetchAll();

if (!$groupId && !empty($myGroups)) $groupId = $myGroups[0]['id'];

// Get group info
$groupInfo = null;
if ($groupId) {
    $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupInfo = $stmt->fetch();
}

// Get meetings
$meetings = [];
if ($groupId) {
    $stmt = $pdo->prepare("SELECT m.*, u.fullname as created_by_name FROM meetings m JOIN users u ON m.created_by = u.id WHERE m.group_id = ? ORDER BY m.meeting_date DESC LIMIT 20");
    $stmt->execute([$groupId]);
    $meetings = $stmt->fetchAll();
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($msg === 'created'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Meeting created successfully.</div>
<?php elseif ($msg === 'attendance_saved'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Attendance recorded.</div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">Meetings</h3>
    <?php if ($groupId): ?>
    <button onclick="openCreateModal()" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">+ Schedule Meeting</button>
    <?php endif; ?>
</div>

<?php if (empty($myGroups)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center"><p class="text-gray-500 text-sm">No groups available.</p></div>
<?php else: ?>
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
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-4 text-sm">
        <div><span class="text-gray-500">Group:</span> <strong><?php echo htmlspecialchars($groupInfo['group_name']); ?></strong></div>
        <div><span class="text-gray-500">Absence Fine:</span> <strong class="text-red-500"><?php echo number_format($groupInfo['meeting_absence_fine']); ?> RWF</strong></div>
        <div><span class="text-gray-500">Late Fine:</span> <strong class="text-amber-500"><?php echo number_format($groupInfo['meeting_late_fine']); ?> RWF</strong></div>
    </div>
    <?php endif; ?>

    <!-- Meetings List -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Meeting History</h3></div>
        <?php if (empty($meetings)): ?>
            <div class="p-10 text-center"><p class="text-gray-500 text-sm">No meetings scheduled yet.</p></div>
        <?php else: ?>
            <div class="space-y-4 p-5">
                <?php foreach($meetings as $m): ?>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900"><?php echo date('d M Y', strtotime($m['meeting_date'])); ?></span>
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $m['status'] === 'completed' ? 'bg-green-100 text-green-700' : ($m['status'] === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'); ?>"><?php echo ucfirst($m['status']); ?></span>
                            </div>
                            <?php if ($m['agenda']): ?><p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($m['agenda']); ?></p><?php endif; ?>
                            <p class="text-xs text-gray-400 mt-1">By <?php echo htmlspecialchars($m['created_by_name']); ?> &middot; <?php echo date('H:i', strtotime($m['created_at'])); ?></p>
                        </div>
                        <div class="flex gap-2">
                            <?php if ($m['status'] === 'scheduled'): ?>
                                <a href="attendance.php?meeting_id=<?php echo $m['id']; ?>&group_id=<?php echo $groupId; ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition" style="background:#0F766E;">Take Attendance</a>
                                <a href="meeting_process.php?action=cancel&meeting_id=<?php echo $m['id']; ?>&group_id=<?php echo $groupId; ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium text-red-500 hover:bg-red-50 transition" onclick="return confirm('Cancel this meeting?')">Cancel</a>
                            <?php elseif ($m['status'] === 'completed'): ?>
                                <a href="attendance.php?meeting_id=<?php echo $m['id']; ?>&group_id=<?php echo $groupId; ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">View Attendance</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- CREATE MEETING MODAL -->
<div id="createModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl">
        <div class="flex justify-between items-center p-5 border-b"><h3 class="font-bold text-lg">Schedule Meeting</h3><button onclick="closeCreateModal()" class="text-2xl text-gray-400 hover:text-gray-800">&times;</button></div>
        <form action="meeting_process.php" method="POST" class="p-5 space-y-4">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
            <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Meeting Date</label><input type="date" name="meeting_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo date('Y-m-d'); ?>" required></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Agenda</label><textarea name="agenda" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="What will be discussed?"></textarea></div>
            <button type="submit" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Schedule Meeting</button>
        </form>
    </div>
</div>

<script>
function openCreateModal() { document.getElementById('createModal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeCreateModal() { document.getElementById('createModal').classList.add('hidden'); document.body.style.overflow = ''; }
document.getElementById('createModal').addEventListener('click', function(e) { if(e.target === this) closeCreateModal(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>