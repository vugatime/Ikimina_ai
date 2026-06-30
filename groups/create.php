<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// ROLE CHECK: Super Admin cannot create groups
if ($current_user_role === 'super_admin') { header('Location: ../dashboard.php'); exit; }

$page_title = 'Create Group';
$base_path = '../';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-bold text-lg text-gray-900 mb-1">Create New Savings Group</h3>
        <p class="text-sm text-gray-500 mb-6">Set up your ikimina with custom rules. You can change these later.</p>
        
        <form action="create_process.php" method="POST" class="space-y-8">
            
            <!-- SECTION 1: Basic Information -->
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-brand-600 text-white text-xs flex items-center justify-center font-bold">1</span>
                    Basic Information
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Group Name</label>
                        <input type="text" name="group_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., Abishyizehamwe SACCO" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">District</label>
                        <input type="text" name="district" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., Gasabo" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Sector</label>
                        <input type="text" name="sector" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., Kimironko" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Cell</label>
                        <input type="text" name="cell_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Cell name">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Village</label>
                        <input type="text" name="village" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Village name">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Description</label>
                        <textarea name="description" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Purpose of this group..."></textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Savings Rules -->
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-brand-600 text-white text-xs flex items-center justify-center font-bold">2</span>
                    Savings Rules
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Amount Per Member (RWF)</label>
                        <input type="number" name="contribution_amount" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 10000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Frequency</label>
                        <select name="contribution_frequency" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Every 2 Weeks</option>
                            <option value="monthly" selected>Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Expected Day</label>
                        <input type="text" name="expected_day" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., Friday or 5th">
                        <p class="text-xs text-gray-400 mt-1">e.g., "Friday" for weekly, "5" for monthly</p>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Penalty Rules -->
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-brand-600 text-white text-xs flex items-center justify-center font-bold">3</span>
                    Penalty Rules
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Late Payment Penalty</label>
                        <select name="late_penalty_type" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition">
                            <option value="fixed">Fixed Amount</option>
                            <option value="percent">Percentage (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Penalty Value</label>
                        <input type="number" name="late_penalty_value" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 500 or 10" value="0">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Applied Every</label>
                        <select name="late_penalty_frequency" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition">
                            <option value="once">Once</option>
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Grace Period (Days)</label>
                        <input type="number" name="grace_period_days" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 3" value="3">
                        <p class="text-xs text-gray-400 mt-1">Days before penalty starts</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Meeting Absence Fine (RWF)</label>
                        <input type="number" name="meeting_absence_fine" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 1000" value="0">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Meeting Late Fine (RWF)</label>
                        <input type="number" name="meeting_late_fine" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 500" value="0">
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Loan Rules -->
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-brand-600 text-white text-xs flex items-center justify-center font-bold">4</span>
                    Loan Rules
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Min Savings to Qualify (RWF)</label>
                        <input type="number" name="min_savings_for_loan" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 50000" value="0">
                    </div>
                </div>
                
                <div class="mt-5 pt-5 border-t border-gray-200">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Default Loan Product</p>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Max Amount (RWF)</label><input type="number" name="loan_max_amount" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 500000" value="0"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Interest Type</label><select name="loan_interest_type" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition"><option value="monthly">Monthly Interest</option><option value="flat">Flat Interest</option></select></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Interest Rate (%)</label><input type="number" name="loan_interest_rate" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 10" value="10" step="0.01"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1.5">Max Duration (Months)</label><input type="number" name="loan_max_duration" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., 6" value="3"></div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-2">
                <button type="submit" name="create_group" class="px-6 py-3 rounded-xl text-sm font-semibold text-white transition-all shadow-md hover:shadow-lg" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">Create Group with Rules</button>
                <a href="../dashboard.php" class="px-6 py-3 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition no-underline inline-flex items-center">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>