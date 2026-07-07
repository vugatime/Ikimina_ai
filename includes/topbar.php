<?php
$unread = 0;
$notifications = [];
if (isset($pdo) && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unread = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll();
    } catch(Exception $e) {}
}

$uname = $_SESSION['user_name'] ?? 'User';
$urole = $_SESSION['user_role'] ?? 'member';
$init = strtoupper(substr($uname, 0, 1));

// Get group role for proper display
$groupRole = null;
if ($urole !== 'super_admin' && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role_in_group FROM group_members WHERE user_id = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $groupRole = $stmt->fetchColumn();
}
$displayRole = $groupRole ?: $urole;

$rb = [
    'super_admin'    => ['bg'=>'#fef3c7','tc'=>'#92400e','l'=>'Super Admin'],
    'group_admin'    => ['bg'=>'#dbeafe','tc'=>'#1e40af','l'=>'Group Admin'],
    'assistant_admin'=> ['bg'=>'#ccfbf1','tc'=>'#0F766E','l'=>'Assistant Admin'],
    'treasurer'      => ['bg'=>'#d1fae5','tc'=>'#065f46','l'=>'Treasurer'],
    'member'         => ['bg'=>'#f1f5f9','tc'=>'#475569','l'=>'Member']
];
$ri = $rb[$displayRole] ?? $rb['member'];
$bp = isset($base_path) ? $base_path : '';
?>
<header class="topbar">
    <div class="flex items-center gap-3">
        <button onclick="document.getElementById('sidebar').classList.toggle('open'); document.getElementById('overlay').classList.toggle('show')" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div>
            <h2 class="text-lg font-bold text-gray-900"><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></h2>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <!-- Notification Bell -->
        <div class="relative" id="notifBell">
            <button onclick="toggleNotifications()" class="relative p-2 rounded-lg hover:bg-gray-100 transition text-gray-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                <?php if($unread > 0): ?><span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white rounded-full text-xs font-bold flex items-center justify-center"><?php echo $unread; ?></span><?php endif; ?>
            </button>
            <div id="notifDropdown" class="absolute right-0 top-full mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-100 py-2 hidden z-50">
                <div class="px-4 py-2 border-b border-gray-50 flex justify-between items-center">
                    <span class="font-semibold text-sm">Notifications</span>
                    <?php if($unread > 0): ?><span class="text-xs text-brand-600"><?php echo $unread; ?> new</span><?php endif; ?>
                </div>
                <div class="max-h-64 overflow-y-auto">
                    <?php if(empty($notifications)): ?>
                        <p class="text-gray-500 text-xs text-center py-6">No notifications yet.</p>
                    <?php else: ?>
                        <?php foreach($notifications as $n): ?>
                            <div class="px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 cursor-pointer <?php echo $n['is_read'] ? '' : 'bg-brand-50'; ?>">
                                <p class="text-xs font-semibold text-gray-900"><?php echo htmlspecialchars($n['title']); ?></p>
                                <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($n['message']); ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo date('d M H:i', strtotime($n['created_at'])); ?></p>
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
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs" style="background:#0F766E;"><?php echo $init; ?></div>
                <div class="hidden sm:block text-left">
                    <div class="text-sm font-semibold text-gray-900 leading-tight"><?php echo htmlspecialchars($uname); ?></div>
                    <div class="text-xs" style="color:<?php echo $ri['tc']; ?>;"><?php echo $ri['l']; ?></div>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div id="userMenu" class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 hidden z-50">
                <div class="px-4 py-3 border-b border-gray-50">
                    <p class="text-sm font-semibold"><?php echo htmlspecialchars($uname); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['user_email']??''); ?></p>
                </div>
                <a href="<?php echo $bp; ?>profile.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 no-underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/></svg> My Profile
                </a>
                <a href="<?php echo $bp; ?>settings.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 no-underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg> Settings
                </a>
                <div class="border-t border-gray-50 my-1"></div>
                <a href="<?php echo $bp; ?>logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 no-underline font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Sign Out
                </a>
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