<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
$page_title = 'Take Attendance';
$base_path = '../';

$meetingId = $_GET['meeting_id'] ?? null;
$groupId = $_GET['group_id'] ?? null;

if (!$meetingId || !$groupId) { header('Location: manage.php'); exit; }

// Get meeting info
$stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = ? AND group_id = ?");
$stmt->execute([$meetingId, $groupId]);
$meeting = $stmt->fetch();

if (!$meeting) { header('Location: manage.php'); exit; }

// Get group info
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

// Get members
$stmt = $pdo->prepare("SELECT gm.id as membership_id, gm.member_id, u.fullname FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ? AND gm.deleted_at IS NULL ORDER BY gm.member_id ASC");
$stmt->execute([$groupId]);
$members = $stmt->fetchAll();

// Get existing attendance
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE meeting_id = ?");
$stmt->execute([$meetingId]);
$existingAttendance = $stmt->fetchAll();
$attendanceMap = [];
foreach ($existingAttendance as $a) {
    $attendanceMap[$a['member_id']] = $a;
}

$msg = $_GET['msg'] ?? '';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($msg === 'saved'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Attendance saved. Fines auto-applied for absent/late members.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">Take Attendance</h3>
    <p class="text-gray-500 text-sm mt-1">Meeting: <?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?> | <?php echo htmlspecialchars($group['group_name']); ?></p>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-50 flex justify-between items-center">
        <h3 class="font-bold text-gray-900">Members (<?php echo count($members); ?>)</h3>
        <div class="flex gap-2">
            <span class="text-xs text-red-500">Absent Fine: <?php echo number_format($group['meeting_absence_fine']); ?> RWF</span>
            <span class="text-xs text-amber-500">Late Fine: <?php echo number_format($group['meeting_late_fine']); ?> RWF</span>
        </div>
    </div>
    <form action="attendance_process.php" method="POST">
        <input type="hidden" name="meeting_id" value="<?php echo $meetingId; ?>">
        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead><tr><th>Member ID</th><th>Name</th><th>Status</th><th>Fine</th></tr></thead>
                <tbody>
                    <?php foreach($members as $m): 
                        $currentStatus = $attendanceMap[$m['membership_id']]['status'] ?? 'present';
                        $currentFine = $attendanceMap[$m['membership_id']]['fine_amount'] ?? 0;
                    ?>
                    <tr>
                        <td><span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($m['member_id']); ?></span></td>
                        <td class="font-semibold text-sm"><?php echo htmlspecialchars($m['fullname']); ?></td>
                        <td>
                            <select name="attendance[<?php echo $m['membership_id']; ?>]" class="px-3 py-2 border border-gray-200 rounded-lg text-sm" onchange="updateFine(this, <?php echo $m['membership_id']; ?>, <?php echo $group['meeting_absence_fine']; ?>, <?php echo $group['meeting_late_fine']; ?>)">
                                <option value="present" <?php echo $currentStatus == 'present' ? 'selected' : ''; ?>>Present</option>
                                <option value="late" <?php echo $currentStatus == 'late' ? 'selected' : ''; ?>>Late</option>
                                <option value="absent" <?php echo $currentStatus == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                <option value="excused" <?php echo $currentStatus == 'excused' ? 'selected' : ''; ?>>Excused</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="fines[<?php echo $m['membership_id']; ?>]" id="fine_<?php echo $m['membership_id']; ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm w-28" value="<?php echo $currentFine; ?>" readonly>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-gray-50">
            <button type="submit" name="save_attendance" class="px-6 py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Save Attendance</button>
        </div>
    </form>
</div>

<script>
function updateFine(select, memberId, absenceFine, lateFine) {
    const fineInput = document.getElementById('fine_' + memberId);
    if (select.value === 'absent') fineInput.value = absenceFine;
    else if (select.value === 'late') fineInput.value = lateFine;
    else fineInput.value = 0;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>