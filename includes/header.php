<?php
require_once __DIR__ . '/language_switcher.php';
require_once __DIR__ . '/../config/session.php';
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ? '/Ikimina_ai/' : '/';
$user_name = $_SESSION['user_name'] ?? 'Robert';
$user_email = $_SESSION['user_email'] ?? 'vugatime@gmail.com';
$user_role = $_SESSION['user_role'] ?? 'member';
$user_id = $_SESSION['user_id'] ?? 0;
$user_init = strtoupper(substr($user_name, 0, 1));
$page_title = $page_title ?? __('dashboard');

// Get group role
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

$displayRole = $groupRole ?: $user_role;
$role_labels = [
    'super_admin' => __('super_admin'),
    'group_admin' => __('group_admin'),
    'assistant_admin' => __('assistant_admin'),
    'treasurer' => __('treasurer'),
    'member' => __('member')
];
$role_label = $role_labels[$displayRole] ?? __('member');

$isSuperAdmin = ($user_role === 'super_admin');
$isGroupAdmin = ($groupRole === 'group_admin');
$isAssistantAdmin = ($groupRole === 'assistant_admin');
$isTreasurer = ($groupRole === 'treasurer');
$isMember = ($groupRole === 'member');

// Check if user is group_admin in users table but has no group yet
$needsGroup = ($user_role === 'group_admin' && !$groupRole);

$gidParam = ($groupId) ? "?group_id=" . $groupId : "";

// Fetch notifications
$unread = 0;
$notifications = [];
if (isset($pdo) && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unread = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll();
    } catch(Exception $e) {
        $unread = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_name'); ?> - <?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#f0fdfa', 100: '#ccfbf1', 500: '#14b8a6', 600: '#0F766E', 700: '#115E59', 800: '#134E4A' },
                        sidebar: '#0F172A',
                        surface: '#F8FAFC'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #F8FAFC; margin: 0; }
        .sidebar-link { transition: all 0.15s ease; }
        .sidebar-link:hover { background: rgba(255,255,255,0.06); }
        .sidebar-link.active { background: #0F766E; color: white; }
        #sidebar { transition: transform 0.3s ease; }
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #overlay { display: none; }
            #overlay.show { display: block; }
        }
        #dropdownMenu { display: none; }
        #dropdownMenu.show { display: block; }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <div id="overlay" class="fixed inset-0 bg-black/50 z-30 hidden" onclick="toggleSidebar()"></div>
    
    <div class="flex flex-1">
        <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-sidebar text-white z-40 flex flex-col overflow-y-auto">
            <div class="p-5 border-b border-gray-700/50 flex-shrink-0">
                <a href="<?php echo $base; ?>dashboard.php" class="flex items-center gap-3 no-underline">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-extrabold text-lg" style="background:#0F766E;">I</div>
                    <span class="font-extrabold text-xl text-white tracking-tight">Ikimina<span style="color:#14b8a6;">AI</span></span>
                </a>
            </div>
            <nav class="flex-1 p-3 space-y-0.5">
                
                <!-- Dashboard - Always visible -->
                <a href="<?php echo $base; ?>dashboard.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium active">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <?php echo __('dashboard'); ?>
                </a>
                
                <!-- SUPER ADMIN MENU -->
                <?php if ($isSuperAdmin): ?>
                <a href="<?php echo $base; ?>admin/users.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('all_users'); ?></a>
                <a href="<?php echo $base; ?>groups/manage.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('all_groups'); ?></a>
                <a href="<?php echo $base; ?>savings/record.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('all_savings'); ?></a>
                <a href="<?php echo $base; ?>loans/manage.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('all_loans'); ?></a>
                <?php endif; ?>
                
                <!-- NEW GROUP ADMIN - Needs to create group first -->
                <?php if ($needsGroup): ?>
                <a href="<?php echo $base; ?>groups/create.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-white text-sm font-medium" style="background:#0F766E;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <?php echo __('create_group'); ?>
                </a>
                <?php endif; ?>

                <!-- GROUP ADMIN MENU -->
                <?php if ($isGroupAdmin): ?>
                <a href="<?php echo $base; ?>groups/manage.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('my_group'); ?></a>
                <a href="<?php echo $base; ?>members/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('members'); ?></a>
                <a href="<?php echo $base; ?>savings/record.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('savings'); ?></a>
                <a href="<?php echo $base; ?>loans/review.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('review_loans'); ?></a>
                <a href="<?php echo $base; ?>loans/disburse.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('disburse_loans'); ?></a>
                <a href="<?php echo $base; ?>loans/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('manage_loans'); ?></a>
                <a href="<?php echo $base; ?>meetings/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('meetings'); ?></a>
                <a href="<?php echo $base; ?>payouts/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Kuzenguruka
                </a>
                <a href="<?php echo $base; ?>report.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('my_report'); ?></a>
                <?php endif; ?>
                
                <!-- ASSISTANT ADMIN MENU -->
                <?php if ($isAssistantAdmin): ?>
                <a href="<?php echo $base; ?>members/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('members'); ?></a>
                <a href="<?php echo $base; ?>savings/record.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('savings'); ?></a>
                <a href="<?php echo $base; ?>loans/review.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('review_loans'); ?></a>
                <a href="<?php echo $base; ?>loans/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('manage_loans'); ?></a>
                <a href="<?php echo $base; ?>meetings/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('meetings'); ?></a>
                <a href="<?php echo $base; ?>payouts/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Kuzenguruka
                </a>
                <a href="<?php echo $base; ?>report.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('my_report'); ?></a>
                <?php endif; ?>
                
                <!-- TREASURER MENU -->
                <?php if ($isTreasurer): ?>
                <a href="<?php echo $base; ?>savings/record.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('record_savings'); ?></a>
                <a href="<?php echo $base; ?>loans/disburse.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('disburse_loans'); ?></a>
                <a href="<?php echo $base; ?>meetings/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('meetings'); ?></a>
                <a href="<?php echo $base; ?>payouts/manage.php<?php echo $gidParam; ?>" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Kuzenguruka
                </a>
                <a href="<?php echo $base; ?>report.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium"><?php echo __('my_report'); ?></a>
                <?php endif; ?>
                
                <!-- MEMBER / ASSISTANT / TREASURER (all group members) -->
                <?php if ($isMember || $isAssistantAdmin || $isTreasurer): ?>
                <a href="<?php echo $base; ?>loans/request.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 text-sm font-medium <?php echo strpos($current_path, '/loans/request') !== false ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg> <?php echo __('request_loan'); ?>
                </a>
                <?php endif; ?>
                
            </nav>
            <div class="p-4 border-t border-gray-700/50 text-xs text-gray-500 flex-shrink-0">
                &copy; <?php echo date('Y'); ?> IkiminaAI v1.0
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-h-screen ml-0 md:ml-64">
            <header class="sticky top-0 z-20 bg-white/90 backdrop-blur-xl border-b border-gray-200/60 px-4 md:px-6 py-3 flex items-center justify-between flex-shrink-0 shadow-sm">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($page_title); ?></h2>
                    </div>
                </div>
                <div class="flex items-center gap-3 relative">
                    <!-- Notification Bell -->
                    <div class="relative" id="notifBell">
                        <button onclick="toggleNotifications()" class="relative p-2 rounded-lg hover:bg-gray-100 transition text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                            <?php if($unread > 0): ?>
                                <span class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white rounded-full text-xs font-bold flex items-center justify-center"><?php echo $unread; ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="notifDropdown" class="absolute right-0 top-full mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-100 py-2 hidden z-50">
                            <div class="px-4 py-3 border-b border-gray-50 flex justify-between items-center">
                                <span class="font-semibold text-sm">Notifications</span>
                                <?php if($unread > 0): ?><span class="text-xs text-brand-600 font-semibold"><?php echo $unread; ?> new</span><?php endif; ?>
                            </div>
                            <div class="max-h-72 overflow-y-auto">
                                <?php if(empty($notifications)): ?>
                                    <div class="text-center py-8">
                                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                                        <p class="text-gray-400 text-xs">No notifications yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($notifications as $n): 
                                        $iconColor = match($n['type']) {
                                            'success' => '#10b981',
                                            'warning' => '#f59e0b',
                                            'danger' => '#ef4444',
                                            default => '#0F766E'
                                        };
                                    ?>
                                        <div class="px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 cursor-pointer transition <?php echo $n['is_read'] ? '' : 'bg-brand-50'; ?>">
                                            <div class="flex gap-2">
                                                <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" style="background:<?php echo $iconColor; ?>;"></div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-semibold text-gray-900"><?php echo htmlspecialchars($n['title']); ?></p>
                                                    <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($n['message']); ?></p>
                                                    <p class="text-xs text-gray-400 mt-1"><?php echo date('d M H:i', strtotime($n['created_at'])); ?></p>
                                                </div>
                                                <?php if(!$n['is_read']): ?>
                                                    <div class="w-1.5 h-1.5 rounded-full bg-brand-600 flex-shrink-0 mt-1.5"></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Language Switcher -->
                    <a href="?lang=<?php echo $lang === 'en' ? 'rw' : 'en'; ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-200 hover:bg-gray-50 transition no-underline text-gray-600">
                        <?php echo $lang === 'en' ? 'KI' : 'EN'; ?>
                    </a>
                    
                    <!-- User Dropdown -->
                    <div class="relative" id="userDropdown">
                        <button onclick="toggleUserMenu()" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-50 border border-gray-200 hover:bg-gray-100 transition text-left">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs" style="background:#0F766E;"><?php echo $user_init; ?></div>
                            <div class="hidden sm:block text-left">
                                <div class="text-sm font-semibold text-gray-900 leading-tight"><?php echo htmlspecialchars($user_name); ?></div>
                                <div class="text-xs text-gray-500"><?php echo $role_label; ?></div>
                            </div>
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div id="userMenu" class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 hidden z-50">
                            <div class="px-4 py-3 border-b border-gray-50">
                                <p class="text-sm font-semibold"><?php echo htmlspecialchars($user_name); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                            </div>
                            <a href="<?php echo $base; ?>profile.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 no-underline"><?php echo __('my_profile'); ?></a>
                            <a href="<?php echo $base; ?>settings.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 no-underline"><?php echo __('settings'); ?></a>
                            <div class="border-t border-gray-50 my-1"></div>
                            <a href="<?php echo $base; ?>logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 no-underline font-medium"><?php echo __('sign_out'); ?></a>
                        </div>
                    </div>
                </div>
            </header>

<script>
function toggleNotifications() {
    document.getElementById('notifDropdown').classList.toggle('hidden');
    document.getElementById('userMenu').classList.add('hidden');
}
function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('hidden');
    document.getElementById('notifDropdown').classList.add('hidden');
}
document.addEventListener('click', function(e) {
    if (!document.getElementById('notifBell').contains(e.target)) document.getElementById('notifDropdown').classList.add('hidden');
    if (!document.getElementById('userDropdown').contains(e.target)) document.getElementById('userMenu').classList.add('hidden');
});
</script>
            <div class="flex-1 p-4 md:p-6">