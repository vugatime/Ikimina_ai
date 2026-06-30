<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
$page_title = 'Edit Group Rules';
$base_path = '../';

$groupId = $_GET['id'] ?? null;
if (!$groupId) { header('Location: manage.php'); exit; }

// Verify user is group admin
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ? AND created_by = ?");
$stmt->execute([$groupId, $current_user_id]);
$group = $stmt->fetch();

if (!$group && $current_user_role !== 'super_admin') {
    header('Location: manage.php'); exit;
}

$msg = $_GET['msg'] ?? '';

// Get loan products for this group
$stmt = $pdo->prepare("SELECT * FROM loan_products WHERE group_id = ? AND status = 'active' ORDER BY product_name ASC");
$stmt->execute([$groupId]);
$loanProducts = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($msg === 'saved'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Group rules updated successfully.
</div>
<?php elseif ($msg === 'product_added'): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-xl text-sm font-medium">Loan product added.</div>
<?php elseif ($msg === 'product_deleted'): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3 rounded-xl text-sm font-medium">Loan product removed.</div>
<?php endif; ?>

<div class="mb-6">
    <h3 class="text-xl font-extrabold text-gray-900">Edit Group Rules</h3>
    <p class="text-gray-500 text-sm mt-1"><?php echo htmlspecialchars($group['group_name']); ?></p>
</div>

<form action="edit_process.php" method="POST" class="space-y-8">
    <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">

    <!-- SECTION 1: Basic Information -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider mb-4">Basic Information</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Group Name</label>
                <input type="text" name="group_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo htmlspecialchars($group['group_name']); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">District</label>
                <input type="text" name="district" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo htmlspecialchars($group['district']); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Sector</label>
                <input type="text" name="sector" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo htmlspecialchars($group['sector']); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Cell</label>
                <input type="text" name="cell_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo htmlspecialchars($group['cell_name']); ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Village</label>
                <input type="text" name="village" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo htmlspecialchars($group['village']); ?>">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
                <textarea name="description" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition"><?php echo htmlspecialchars($group['description']); ?></textarea>
            </div>
        </div>
    </div>

    <!-- SECTION 2: Savings Rules -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider mb-4">Savings Rules</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Amount Per Member (RWF)</label>
                <input type="number" name="contribution_amount" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo $group['contribution_amount']; ?>" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Frequency</label>
                <select name="contribution_frequency" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required>
                    <option value="daily" <?php echo $group['contribution_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                    <option value="weekly" <?php echo $group['contribution_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="biweekly" <?php echo $group['contribution_frequency'] == 'biweekly' ? 'selected' : ''; ?>>Every 2 Weeks</option>
                    <option value="monthly" <?php echo $group['contribution_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Expected Day</label>
                <input type="text" name="expected_day" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo htmlspecialchars($group['expected_day']); ?>" placeholder="e.g., Friday or 5th">
            </div>
        </div>
    </div>

    <!-- SECTION 3: Penalty Rules -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider mb-4">Penalty Rules</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Late Payment Penalty</label>
                <select name="late_penalty_type" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition">
                    <option value="fixed" <?php echo $group['late_penalty_type'] == 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                    <option value="percent" <?php echo $group['late_penalty_type'] == 'percent' ? 'selected' : ''; ?>>Percentage (%)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Penalty Value</label>
                <input type="number" name="late_penalty_value" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo $group['late_penalty_value']; ?>" step="0.01">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Applied Every</label>
                <select name="late_penalty_frequency" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition">
                    <option value="once" <?php echo $group['late_penalty_frequency'] == 'once' ? 'selected' : ''; ?>>Once</option>
                    <option value="daily" <?php echo $group['late_penalty_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                    <option value="weekly" <?php echo $group['late_penalty_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="monthly" <?php echo $group['late_penalty_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Grace Period (Days)</label>
                <input type="number" name="grace_period_days" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo $group['grace_period_days']; ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Meeting Absence Fine (RWF)</label>
                <input type="number" name="meeting_absence_fine" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo $group['meeting_absence_fine']; ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Meeting Late Fine (RWF)</label>
                <input type="number" name="meeting_late_fine" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo $group['meeting_late_fine']; ?>">
            </div>
        </div>
    </div>

    <!-- SECTION 4: Loan Rules -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider mb-4">Loan Rules</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Min Savings to Qualify (RWF)</label>
                <input type="number" name="min_savings_for_loan" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" value="<?php echo $group['min_savings_for_loan']; ?>">
            </div>
        </div>
    </div>

    <!-- SECTION 5: Loan Products -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Loan Products</h4>
            <button type="button" onclick="document.getElementById('addProductForm').classList.toggle('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-white transition" style="background:#0F766E;">+ Add Product</button>
        </div>
        
        <!-- Add Product Form -->
        <div id="addProductForm" class="hidden bg-gray-50 rounded-xl p-4 mb-4 border border-gray-100">
            <p class="text-sm font-semibold text-gray-700 mb-3">New Loan Product</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="text" name="new_product_name" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm" placeholder="Product Name" required>
                <input type="number" name="new_product_max" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm" placeholder="Max Amount (RWF)" required>
                <input type="number" name="new_product_rate" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm" placeholder="Interest Rate (%)" value="10" step="0.01" required>
                <input type="number" name="new_product_duration" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm" placeholder="Max Duration (Months)" value="3" required>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                <select name="new_product_interest_type" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm">
                    <option value="monthly">Monthly Interest</option>
                    <option value="flat">Flat Interest</option>
                </select>
                <input type="number" name="new_product_min_savings" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm" placeholder="Min Savings Required" value="0">
                <button type="submit" name="add_product" class="px-4 py-2.5 rounded-lg text-sm font-semibold text-white transition" style="background:#0F766E;">Add</button>
            </div>
        </div>

        <!-- Existing Products -->
        <?php if (empty($loanProducts)): ?>
            <p class="text-gray-500 text-sm text-center py-4">No loan products defined yet.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach($loanProducts as $lp): ?>
                    <div class="flex justify-between items-center bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 flex-1 text-sm">
                            <div><span class="text-gray-500">Name:</span> <strong><?php echo htmlspecialchars($lp['product_name']); ?></strong></div>
                            <div><span class="text-gray-500">Max:</span> <strong><?php echo number_format($lp['max_amount']); ?> RWF</strong></div>
                            <div><span class="text-gray-500">Interest:</span> <strong><?php echo $lp['interest_rate']; ?>% <?php echo $lp['interest_type']; ?></strong></div>
                            <div><span class="text-gray-500">Duration:</span> <strong><?php echo $lp['max_duration_months']; ?> months</strong></div>
                        </div>
                        <a href="edit_process.php?action=delete_product&product_id=<?php echo $lp['id']; ?>&group_id=<?php echo $groupId; ?>" class="text-xs text-red-500 hover:underline ml-4 flex-shrink-0" onclick="return confirm('Delete this loan product?')">Delete</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Save Button -->
    <div class="flex gap-3">
        <button type="submit" name="save_rules" class="px-6 py-3 rounded-xl text-sm font-semibold text-white transition shadow-md" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">Save All Changes</button>
        <a href="manage.php" class="px-6 py-3 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition no-underline inline-flex items-center">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>