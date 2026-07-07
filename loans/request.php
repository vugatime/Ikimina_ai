<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// ROLE CHECK: All group members can request loans (Member, Assistant Admin, Treasurer)
$stmt = $pdo->prepare("SELECT role_in_group FROM group_members WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
$stmt->execute([$current_user_id]);
$gr = $stmt->fetchColumn();
if (!in_array($gr, ['member', 'assistant_admin', 'treasurer'])) { header('Location: ../dashboard.php'); exit; }

$page_title = 'Request Loan';
$base_path = '../';

// Get member info
$stmt = $pdo->prepare("SELECT gm.id as membership_id, gm.member_id, gm.role_in_group, gm.group_id, g.group_name, g.contribution_amount, g.contribution_frequency, g.min_savings_for_loan FROM group_members gm JOIN `groups` g ON gm.group_id = g.id WHERE gm.user_id = ? AND gm.deleted_at IS NULL LIMIT 1");
$stmt->execute([$current_user_id]);
$memberInfo = $stmt->fetch();

if (!$memberInfo) { header('Location: ../dashboard.php'); exit; }

$groupId = $memberInfo['group_id'];
$membershipId = $memberInfo['membership_id'];

// Get member's total savings
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE member_id = ?");
$stmt->execute([$membershipId]);
$myTotalSavings = $stmt->fetchColumn();

// Get active loan products
$stmt = $pdo->prepare("SELECT * FROM loan_products WHERE group_id = ? AND status = 'active' ORDER BY product_name ASC");
$stmt->execute([$groupId]);
$loanProducts = $stmt->fetchAll();

// Get member's previous loan requests
$stmt = $pdo->prepare("SELECT lr.*, lp.product_name FROM loan_requests lr LEFT JOIN loan_products lp ON lr.loan_product_id = lp.id WHERE lr.group_id = ? AND lr.member_id = ? ORDER BY lr.created_at DESC LIMIT 5");
$stmt->execute([$groupId, $membershipId]);
$myRequests = $stmt->fetchAll();

// Get active loans
$stmt = $pdo->prepare("SELECT l.*, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) as total_repaid FROM loans l WHERE l.member_id = ? AND l.status IN ('active','approved')");
$stmt->execute([$membershipId]);
$activeLoans = $stmt->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($msg === 'submitted'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Loan request submitted successfully. Waiting for admin review.
</div>
<?php elseif ($msg === 'low_savings'): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm font-medium">You don't have enough savings to qualify for this loan.</div>
<?php elseif ($msg === 'exceeds_max'): ?>
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-5 py-3 rounded-xl text-sm font-medium">Loan amount exceeds the maximum allowed.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">Request a Loan</h3>
    <p class="text-gray-500 text-sm mt-1">
        <span class="inline-block px-2 py-0.5 rounded-lg text-xs font-bold mr-2" style="background:#ccfbf1;color:#0F766E;"><?php echo htmlspecialchars($memberInfo['member_id']); ?></span>
        <?php echo htmlspecialchars($memberInfo['group_name']); ?>
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Loan Application Form -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-bold text-gray-900 mb-4">New Loan Request</h3>
            
            <!-- Member Stats -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4 text-sm space-y-2">
                <div class="flex justify-between"><span class="text-gray-500">My Savings:</span> <strong><?php echo number_format($myTotalSavings); ?> RWF</strong></div>
                <div class="flex justify-between"><span class="text-gray-500">Active Loans:</span> <strong><?php echo count($activeLoans); ?></strong></div>
            </div>

            <?php if (empty($loanProducts)): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl text-sm">No loan products available yet. Contact your group admin to add loan products.</div>
            <?php else: ?>
            <form action="request_process.php" method="POST" class="space-y-4">
                <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                <input type="hidden" name="member_id" value="<?php echo $membershipId; ?>">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Loan Product</label>
                    <select name="loan_product_id" id="loanProduct" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required onchange="updateProductInfo()">
                        <option value="">Select a loan product</option>
                        <?php foreach($loanProducts as $lp): ?>
                            <option value="<?php echo $lp['id']; ?>" 
                                data-max="<?php echo $lp['max_amount']; ?>"
                                data-rate="<?php echo $lp['interest_rate']; ?>"
                                data-type="<?php echo $lp['interest_type']; ?>"
                                data-duration="<?php echo $lp['max_duration_months']; ?>"
                                data-min-savings="<?php echo $lp['min_savings_required']; ?>">
                                <?php echo htmlspecialchars($lp['product_name']); ?> (Max: <?php echo number_format($lp['max_amount']); ?> RWF, <?php echo $lp['interest_rate']; ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Product Info Display -->
                <div id="productInfo" class="hidden bg-brand-50 rounded-xl p-3 text-xs space-y-1">
                    <p><span class="text-gray-500">Max Amount:</span> <strong id="infoMax">-</strong></p>
                    <p><span class="text-gray-500">Interest Rate:</span> <strong id="infoRate">-</strong></p>
                    <p><span class="text-gray-500">Interest Type:</span> <strong id="infoType">-</strong></p>
                    <p><span class="text-gray-500">Max Duration:</span> <strong id="infoDuration">-</strong> months</p>
                    <p><span class="text-gray-500">Min Savings Required:</span> <strong id="infoMinSavings">-</strong></p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Amount (RWF)</label>
                    <input type="number" name="amount" id="loanAmount" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Enter loan amount" required>
                    <p id="amountWarning" class="hidden text-xs text-red-500 mt-1">Amount exceeds maximum allowed.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Duration (Months)</label>
                    <input type="number" name="duration_months" id="loanDuration" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 3" value="3" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Purpose</label>
                    <textarea name="purpose" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Why do you need this loan?"></textarea>
                </div>
                <button type="submit" name="submit_request" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">Submit Request</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Loan Requests History -->
    <div class="lg:col-span-2">
        <!-- Active Loans -->
        <?php if (!empty($activeLoans)): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
            <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">My Active Loans</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead><tr><th>Amount</th><th>Interest</th><th>Total Due</th><th>Repaid</th><th>Remaining</th><th>Due Date</th></tr></thead>
                    <tbody>
                        <?php foreach($activeLoans as $al): 
                            $remaining = $al['total_repayable'] - $al['total_repaid'];
                        ?>
                        <tr>
                            <td class="font-semibold"><?php echo number_format($al['amount']); ?> RWF</td>
                            <td><?php echo $al['interest_rate']; ?>%</td>
                            <td><?php echo number_format($al['total_repayable']); ?> RWF</td>
                            <td class="text-green-600"><?php echo number_format($al['total_repaid']); ?> RWF</td>
                            <td class="font-semibold text-amber-600"><?php echo number_format($remaining); ?> RWF</td>
                            <td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($al['due_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Request History -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-50"><h3 class="font-bold text-gray-900">My Request History</h3></div>
            <?php if (empty($myRequests)): ?>
                <div class="p-10 text-center"><p class="text-gray-500 text-sm">No loan requests yet.</p></div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr><th>Product</th><th>Amount</th><th>Duration</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach($myRequests as $rq): 
                                $sc = [
                                    'pending' => ['bg'=>'#fef3c7','text'=>'#92400e'],
                                    'reviewed_by_admin' => ['bg'=>'#dbeafe','text'=>'#1e40af'],
                                    'approved' => ['bg'=>'#d1fae5','text'=>'#065f46'],
                                    'rejected' => ['bg'=>'#fee2e2','text'=>'#991b1b'],
                                    'disbursed' => ['bg'=>'#e0e7ff','text'=>'#3730a3'],
                                    'active' => ['bg'=>'#d1fae5','text'=>'#065f46'],
                                    'completed' => ['bg'=>'#ccfbf1','text'=>'#0F766E'],
                                ];
                                $c = $sc[$rq['status']] ?? ['bg'=>'#f1f5f9','text'=>'#475569'];
                            ?>
                            <tr>
                                <td class="text-sm"><?php echo htmlspecialchars($rq['product_name'] ?? 'Standard'); ?></td>
                                <td class="font-semibold"><?php echo number_format($rq['amount']); ?> RWF</td>
                                <td><?php echo $rq['duration_months']; ?> months</td>
                                <td><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" style="background:<?php echo $c['bg']; ?>;color:<?php echo $c['text']?>;"><?php echo ucfirst(str_replace('_',' ',$rq['status'])); ?></span></td>
                                <td class="text-xs text-gray-500"><?php echo date('d M Y', strtotime($rq['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateProductInfo() {
    const select = document.getElementById('loanProduct');
    const option = select.options[select.selectedIndex];
    const info = document.getElementById('productInfo');
    
    if (option.value) {
        info.classList.remove('hidden');
        document.getElementById('infoMax').textContent = Number(option.dataset.max).toLocaleString() + ' RWF';
        document.getElementById('infoRate').textContent = option.dataset.rate + '%';
        document.getElementById('infoType').textContent = option.dataset.type === 'flat' ? 'Flat Interest' : 'Monthly Interest';
        document.getElementById('infoDuration').textContent = option.dataset.duration;
        document.getElementById('infoMinSavings').textContent = Number(option.dataset.minSavings).toLocaleString() + ' RWF';
    } else {
        info.classList.add('hidden');
    }
}

document.getElementById('loanAmount').addEventListener('input', function() {
    const select = document.getElementById('loanProduct');
    const option = select.options[select.selectedIndex];
    const max = option.value ? Number(option.dataset.max) : Infinity;
    const warning = document.getElementById('amountWarning');
    
    if (Number(this.value) > max) {
        warning.classList.remove('hidden');
    } else {
        warning.classList.add('hidden');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>