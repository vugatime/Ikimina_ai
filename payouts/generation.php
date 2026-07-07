<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payout_engine.php';

if (!isset($_SESSION['user_id'])) { header('Location: /auth.php'); exit; }
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'] ?? 'User';
$current_user_role = $_SESSION['user_role'] ?? 'member';

$page_title = 'Generation Details';
$base_path = '../';

// Get user's group
$groupRole = null;
$groupId = null;
if ($current_user_role !== 'super_admin') {
    $stmt = $pdo->prepare("SELECT role_in_group, group_id FROM group_members WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$current_user_id]);
    $memberData = $stmt->fetch();
    if ($memberData) { $groupRole = $memberData['role_in_group']; $groupId = $memberData['group_id']; }
}

$gen = $_GET['gen'] ?? null;
$msg = $_GET['msg'] ?? '';

// Get all generations for this group
$generations = [];
if ($groupId) {
    $stmt = $pdo->prepare("SELECT generation, COUNT(*) as cycles, SUM(total_amount) as total_distributed, MIN(created_at) as started, MAX(created_at) as last_activity FROM payout_cycles WHERE group_id = ? GROUP BY generation ORDER BY generation DESC");
    $stmt->execute([$groupId]);
    $generations = $stmt->fetchAll();
}

// Get selected generation details
$genData = null;
if ($gen && $groupId) {
    $stmt = $pdo->prepare("
        SELECT pc.*, pr.amount_received, pr.ai_score, pr.selection_reason, pr.received_at,
               gm.member_id, u.fullname
        FROM payout_cycles pc
        JOIN payout_recipients pr ON pc.id = pr.cycle_id
        JOIN group_members gm ON pr.member_id = gm.id
        JOIN users u ON gm.user_id = u.id
        WHERE pc.group_id = ? AND pc.generation = ?
        ORDER BY pr.received_at DESC
    ");
    $stmt->execute([$groupId, $gen]);
    $genData = $stmt->fetchAll();
    
    // Generation summary
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cycles FROM payout_cycles WHERE group_id = ? AND generation = ?");
    $stmt->execute([$groupId, $gen]);
    $genSummary = $stmt->fetch();
}

// Handle notes update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    $notes = trim($_POST['notes']);
    $genNum = $_POST['generation'];
    $stmt = $pdo->prepare("UPDATE payout_cycles SET notes = ? WHERE group_id = ? AND generation = ?");
    $stmt->execute([$notes, $groupId, $genNum]);
    header('Location: generation.php?group_id=' . $groupId . '&gen=' . $genNum . '&msg=saved');
    exit;
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($msg === 'saved'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Notes saved successfully.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">Generation Sheets</h3>
    <p class="text-gray-500 text-sm mt-1">Each generation shows who received payouts and when.</p>
</div>

<?php if (empty($generations)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
    <p class="text-gray-500 text-sm">No generations yet. Start a payout cycle to create Generation 1.</p>
    <a href="manage.php?group_id=<?php echo $groupId; ?>" class="inline-block mt-3 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Start Payout</a>
</div>
<?php else: ?>
<!-- Generation Tabs -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block">Select Generation</label>
    <div class="flex flex-wrap gap-2">
        <?php foreach($generations as $g): ?>
            <a href="?group_id=<?php echo $groupId; ?>&gen=<?php echo $g['generation']; ?>" class="px-4 py-2 rounded-lg text-sm font-medium transition no-underline <?php echo ($gen == $g['generation']) ? 'text-white' : 'text-gray-600 bg-gray-50 hover:bg-gray-100'; ?>" style="<?php echo ($gen == $g['generation']) ? 'background:#0F766E;' : ''; ?>">
                Gen <?php echo $g['generation']; ?> — <?php echo number_format($g['total_distributed']); ?> RWF
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($gen && $genData): ?>
<!-- Generation Summary -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold text-brand-600"><?php echo number_format($genSummary['total']); ?> RWF</div>
        <div class="text-xs text-gray-500 mt-0.5">Total Distributed</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold text-gray-900"><?php echo $genSummary['cycles']; ?></div>
        <div class="text-xs text-gray-500 mt-0.5">Cycles This Generation</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <div class="text-2xl font-extrabold text-gray-900"><?php echo count($genData); ?></div>
        <div class="text-xs text-gray-500 mt-0.5">Total Recipients</div>
    </div>
</div>

<!-- Recipients List -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Generation <?php echo $gen; ?> — Recipients</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr><th>Member</th><th>Amount</th><th>AI Score</th><th>Reason</th><th>Date</th></tr></thead>
            <tbody>
                <?php 
                $membersSeen = [];
                foreach($genData as $r): 
                    $key = $r['member_id'];
                    if(isset($membersSeen[$key])) continue;
                    $membersSeen[$key] = true;
                ?>
                <tr>
                    <td><span class="text-xs font-bold text-brand-600"><?php echo htmlspecialchars($r['member_id']); ?></span><br><span class="text-sm"><?php echo htmlspecialchars($r['fullname']); ?></span></td>
                    <td class="font-semibold text-green-600">+<?php echo number_format($r['amount_received']); ?> RWF</td>
                    <td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?php echo $r['ai_score'] >= 70 ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'; ?>"><?php echo $r['ai_score']; ?>%</span></td>
                    <td class="text-xs text-gray-500"><?php echo htmlspecialchars($r['selection_reason'] ?? ''); ?></td>
                    <td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($r['received_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Generation Notes -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-bold text-gray-900 mb-4">Generation Notes</h3>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="generation" value="<?php echo $gen; ?>">
        <textarea name="notes" rows="4" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Notes about this generation... e.g., 'Q3 2026 — Distributed to 3 members after harvest season'"><?php echo htmlspecialchars($genData[0]['notes'] ?? ''); ?></textarea>
        <button type="submit" name="save_notes" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;">Save Notes</button>
    </form>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>