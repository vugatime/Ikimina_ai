<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/ai_engine.php';

// ROLE CHECK: Only Group Admin and Assistant Admin can review loans
if ($current_user_role === 'super_admin') { header('Location: ../dashboard.php'); exit; }
$stmt = $pdo->prepare("SELECT role_in_group FROM group_members WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
$stmt->execute([$current_user_id]);
$gr = $stmt->fetchColumn();
if (!in_array($gr, ['group_admin', 'assistant_admin'])) { header('Location: ../dashboard.php'); exit; }

$page_title = 'Review Loan Requests';
$base_path = '../';

// Get member's group
$stmt = $pdo->prepare("SELECT gm.group_id, gm.role_in_group FROM group_members gm WHERE gm.user_id = ? AND gm.deleted_at IS NULL LIMIT 1");
$stmt->execute([$current_user_id]);
$adminInfo = $stmt->fetch();

$groupId = $_GET['group_id'] ?? $adminInfo['group_id'] ?? null;
$msg = $_GET['msg'] ?? '';

// Get pending requests - ADDED gm.id as membership_id
$stmt = $pdo->prepare("SELECT lr.*, gm.id as membership_id, gm.member_id, u.fullname, lp.product_name,
                       COALESCE((SELECT SUM(amount) FROM savings WHERE member_id = lr.member_id), 0) as total_savings,
                       (SELECT COUNT(*) FROM loans WHERE member_id = lr.member_id AND status IN ('active','approved')) as active_loans,
                       (SELECT COUNT(*) FROM loan_requests WHERE member_id = lr.member_id AND status = 'rejected') as rejected_count
                       FROM loan_requests lr 
                       JOIN group_members gm ON lr.member_id = gm.id 
                       JOIN users u ON gm.user_id = u.id 
                       LEFT JOIN loan_products lp ON lr.loan_product_id = lp.id 
                       WHERE lr.group_id = ? AND lr.status = 'pending'
                       ORDER BY lr.created_at ASC");
$stmt->execute([$groupId]);
$pendingRequests = $stmt->fetchAll();

// Get reviewed requests
$stmt = $pdo->prepare("SELECT lr.*, gm.member_id, u.fullname, lp.product_name, rv.fullname as reviewer_name
                       FROM loan_requests lr 
                       JOIN group_members gm ON lr.member_id = gm.id 
                       JOIN users u ON gm.user_id = u.id 
                       LEFT JOIN loan_products lp ON lr.loan_product_id = lp.id 
                       LEFT JOIN users rv ON lr.reviewed_by = rv.id
                       WHERE lr.group_id = ? AND lr.status != 'pending'
                       ORDER BY lr.created_at DESC LIMIT 20");
$stmt->execute([$groupId]);
$reviewedRequests = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($msg === 'reviewed'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Loan request reviewed successfully.</div>
<?php elseif ($msg === 'approved'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Loan approved. Treasurer can now disburse.</div>
<?php elseif ($msg === 'rejected'): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm font-medium">Loan request rejected.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">Review Loan Requests</h3>
    <p class="text-gray-500 text-sm mt-1">Review member loan applications before approval.</p>
</div>

<!-- Pending Requests -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Pending Requests (<?php echo count($pendingRequests); ?>)</h3></div>
    <?php if (empty($pendingRequests)): ?>
        <div class="p-10 text-center"><p class="text-gray-500 text-sm">No pending loan requests.</p></div>
    <?php else: ?>
        <div class="space-y-4 p-5">
            <?php foreach($pendingRequests as $pr): 
                $riskScore = 50;
                if ($pr['total_savings'] > $pr['amount']) $riskScore += 20;
                if ($pr['active_loans'] == 0) $riskScore += 15;
                if ($pr['rejected_count'] == 0) $riskScore += 10;
                if ($pr['total_savings'] > $pr['amount'] * 2) $riskScore += 5;
                $riskLabel = $riskScore >= 70 ? 'Low Risk' : ($riskScore >= 40 ? 'Medium Risk' : 'High Risk');
                $riskColor = $riskScore >= 70 ? 'text-green-600 bg-green-50' : ($riskScore >= 40 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50');
                
                // Get AI scores using numeric membership_id
                $memberScores = getMemberAIScores($pdo, $pr['membership_id'], $groupId);
                if (!$memberScores) {
                    $scores = calculateMemberScores($pdo, $pr['membership_id'], $groupId);
                    saveAIScores($pdo, $pr['membership_id'], $groupId, $scores);
                    $memberScores = $scores;
                }
                $riskBg = $memberScores['risk_label'] === 'LOW RISK' ? 'bg-green-50 text-green-700' : ($memberScores['risk_label'] === 'MEDIUM RISK' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700');
            ?>
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($pr['member_id']); ?></span>
                            <span class="font-bold text-gray-900"><?php echo htmlspecialchars($pr['fullname']); ?></span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-gray-500 mb-2">
                            <div>Product: <strong><?php echo htmlspecialchars($pr['product_name'] ?? 'Standard'); ?></strong></div>
                            <div>Amount: <strong class="text-gray-900"><?php echo number_format($pr['amount']); ?> RWF</strong></div>
                            <div>Duration: <strong><?php echo $pr['duration_months']; ?> months</strong></div>
                            <div>Purpose: <strong><?php echo htmlspecialchars($pr['purpose'] ?: 'N/A'); ?></strong></div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-xs text-gray-500 mb-2">
                            <div>Total Savings: <strong class="text-green-600"><?php echo number_format($pr['total_savings']); ?> RWF</strong></div>
                            <div>Active Loans: <strong><?php echo $pr['active_loans']; ?></strong></div>
                            <div>Rejected Before: <strong><?php echo $pr['rejected_count']; ?></strong></div>
                        </div>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold <?php echo $riskColor; ?>">AI Risk: <?php echo $riskLabel; ?> (<?php echo $riskScore; ?>%)</span>
                        
                        <!-- AI Detailed Scores -->
                        <div class="mt-3 bg-white rounded-xl p-3 border">
                            <div class="flex flex-wrap gap-3 text-xs">
                                <div><span class="text-gray-500">Trust Score:</span> <strong><?php echo $memberScores['trust_score'] ?? 0; ?>%</strong></div>
                                <div><span class="text-gray-500">Savings:</span> <strong><?php echo $memberScores['savings_score'] ?? 0; ?>%</strong></div>
                                <div><span class="text-gray-500">Repayment:</span> <strong><?php echo $memberScores['repayment_score'] ?? 0; ?>%</strong></div>
                                <div><span class="text-gray-500">Eligibility:</span> <strong><?php echo $memberScores['loan_eligibility'] ?? 0; ?>%</strong></div>
                                <div><span class="text-gray-500">Default Risk:</span> <strong><?php echo $memberScores['default_risk'] ?? 0; ?>%</strong></div>
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold <?php echo $riskBg; ?>"><?php echo $memberScores['risk_label'] ?? 'MEDIUM RISK'; ?></span>
                            </div>
                            <?php if (!empty($memberScores['recommendation'])): ?>
                                <p class="text-xs text-gray-500 mt-2"><?php echo htmlspecialchars($memberScores['recommendation']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <form action="review_process.php" method="POST">
                            <input type="hidden" name="request_id" value="<?php echo $pr['id']; ?>">
                            <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                            <input type="hidden" name="action" value="review">
                            <textarea name="review_notes" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs mb-2" placeholder="Review notes..."></textarea>
                            <div class="flex gap-2">
                                <button type="submit" name="decision" value="approve" class="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-green-500 hover:bg-green-600 transition">Approve</button>
                                <button type="submit" name="decision" value="reject" class="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-red-500 hover:bg-red-600 transition">Reject</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Reviewed Requests -->
<?php if (!empty($reviewedRequests)): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">Reviewed Requests</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr><th>Member</th><th>Amount</th><th>Status</th><th>Reviewed By</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach($reviewedRequests as $rr): 
                    $sc = ['reviewed_by_admin'=>['bg'=>'#dbeafe','text'=>'#1e40af'],'approved'=>['bg'=>'#d1fae5','text'=>'#065f46'],'rejected'=>['bg'=>'#fee2e2','text'=>'#991b1b'],'disbursed'=>['bg'=>'#e0e7ff','text'=>'#3730a3'],'active'=>['bg'=>'#d1fae5','text'=>'#065f46'],'completed'=>['bg'=>'#ccfbf1','text'=>'#0F766E']];
                    $c = $sc[$rr['status']] ?? ['bg'=>'#f1f5f9','text'=>'#475569'];
                ?>
                <tr>
                    <td><span class="text-xs text-brand-600"><?php echo htmlspecialchars($rr['member_id']); ?></span><br><span class="font-medium text-sm"><?php echo htmlspecialchars($rr['fullname']); ?></span></td>
                    <td class="font-semibold"><?php echo number_format($rr['amount']); ?> RWF</td>
                    <td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" style="background:<?php echo $c['bg']; ?>;color:<?php echo $c['text']?>;"><?php echo ucfirst(str_replace('_',' ',$rr['status'])); ?></span></td>
                    <td class="text-sm"><?php echo htmlspecialchars($rr['reviewer_name'] ?? '-'); ?></td>
                    <td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($rr['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
