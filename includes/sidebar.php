<?php
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '';
if (strpos($current_path, '/groups/') !== false || strpos($current_path, '/members/') !== false || strpos($current_path, '/savings/') !== false || strpos($current_path, '/loans/') !== false || strpos($current_path, '/meetings/') !== false || strpos($current_path, '/report') !== false || strpos($current_path, '/admin/') !== false) {
    $base = '../';
}

$user_role = $_SESSION['user_role'] ?? 'member';

$groupRole = null;
$groupId = null;
if ($user_role !== 'super_admin' && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare("SELECT gm.role_in_group, gm.group_id FROM group_members gm WHERE gm.user_id = ? AND gm.deleted_at IS NULL LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $memberData = $stmt->fetch();
    if ($memberData) { 
        $groupRole = $memberData['role_in_group']; 
        $groupId = $memberData['group_id']; 
    }
}

$isSuperAdmin = ($user_role === 'super_admin');
$isGroupAdmin = ($groupRole === 'group_admin');
$isAssistantAdmin = ($groupRole === 'assistant_admin');
$isTreasurer = ($groupRole === 'treasurer');
$isMember = ($groupRole === 'member');

$gidParam = ($groupId) ? "?group_id=" . $groupId : "";
?>
<aside class="sidebar" id="sidebar">
    <div class="p-5 border-b border-gray-700/50 flex-shrink-0">
        <a href="<?php echo $base; ?>dashboard.php" class="flex items-center gap-3 no-underline">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-extrabold text-lg" style="background:#0F766E;">I</div>
            <span class="font-extrabold text-xl text-white tracking-tight">Ikimina<span style="color:#14b8a6;">AI</span></span>
        </a>
    </div>
    <nav class="flex-1 p-3 space-y-0.5">
        
        <!-- Dashboard - ALL ROLES -->
        <a href="<?php echo $base; ?>dashboard.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo (strpos($current_path, 'dashboard') !== false && strpos($current_path, '/groups/') === false && strpos($current_path, '/members/') === false && strpos($current_path, '/savings/') === false && strpos($current_path, '/loans/') === false && strpos($current_path, '/meetings/') === false && strpos($current_path, '/report') === false && strpos($current_path, '/admin/') === false) ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a>
        
        <!-- ============ SUPER ADMIN ============ -->
        <?php if ($isSuperAdmin): ?>
        <a href="<?php echo $base; ?>admin/users.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/admin/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> All Users
        </a>
        <a href="<?php echo $base; ?>groups/manage.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/groups/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg> All Groups
        </a>
        <a href="<?php echo $base; ?>savings/record.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/savings/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> All Savings
        </a>
        <a href="<?php echo $base; ?>loans/manage.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/loans/manage') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg> All Loans
        </a>
        <?php endif; ?>
        
        <!-- ============ GROUP ADMIN ============ -->
        <?php if ($isGroupAdmin): ?>
        <a href="<?php echo $base; ?>groups/manage.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/groups/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg> My Group
        </a>
        <a href="<?php echo $base; ?>members/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/members/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Members
        </a>
        <a href="<?php echo $base; ?>savings/record.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/savings/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Savings
        </a>
        <a href="<?php echo $base; ?>loans/review.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/loans/review') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Review Loans
        </a>
        <a href="<?php echo $base; ?>loans/disburse.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/loans/disburse') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Disburse Loans
        </a>
        <a href="<?php echo $base; ?>loans/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/loans/manage') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg> Manage Loans
        </a>
        <a href="<?php echo $base; ?>meetings/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/meetings/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="13" y2="11"/></svg> Meetings
        </a>
        <a href="<?php echo $base; ?>report.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/report') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> My Report
        </a>
        <?php endif; ?>
        
        <!-- ============ ASSISTANT ADMIN ============ -->
        <?php if ($isAssistantAdmin): ?>
        <a href="<?php echo $base; ?>members/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/members/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Members
        </a>
        <a href="<?php echo $base; ?>savings/record.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/savings/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Savings
        </a>
        <a href="<?php echo $base; ?>loans/review.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/loans/review') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Review Loans
        </a>
        <a href="<?php echo $base; ?>loans/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/loans/manage') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg> Manage Loans
        </a>
        <a href="<?php echo $base; ?>meetings/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/meetings/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="13" y2="11"/></svg> Meetings
        </a>
        <a href="<?php echo $base; ?>report.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/report') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> My Report
        </a>
        <?php endif; ?>
        
        <!-- ============ TREASURER ============ -->
        <?php if ($isTreasurer): ?>
        <a href="<?php echo $base; ?>savings/record.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/savings/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Record Savings
        </a>
        <a href="<?php echo $base; ?>loans/disburse.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/loans/disburse') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Disburse Loans
        </a>
        <a href="<?php echo $base; ?>meetings/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/meetings/') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="13" y2="11"/></svg> Meetings
        </a>
        <a href="<?php echo $base; ?>report.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/report') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> My Report
        </a>
        <?php endif; ?>

        <!-- ============ MEMBER / ASSISTANT / TREASURER ============ -->
        <?php if ($isMember || $isAssistantAdmin || $isTreasurer): ?>
        <a href="<?php echo $base; ?>loans/request.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/loans/request') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg> Request Loan
        </a>
        <a href="<?php echo $base; ?>report.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/report') !== false ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> My Report
        </a>
        <?php endif; ?>
        
    </nav>
    <div class="p-4 border-t border-gray-700/50 text-xs text-gray-500 flex-shrink-0">
        &copy; <?php echo date('Y'); ?> IkiminaAI v1.0
    </div>
</aside>
<div class="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open')"></div>