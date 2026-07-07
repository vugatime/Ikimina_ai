<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/language_switcher.php';
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('register'); ?> - IkiminaAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:{600:'#0F766E',700:'#115E59'}}}}}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-slate-50">
<div id="cookieBanner" class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-2xl p-4" style="display:none;">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-xs sm:text-sm text-gray-700 flex-1"><?php echo $lang==='en'?'We use cookies to enhance your experience.':'Dukoresha kuki kugira ngo tube inzira nziza.'; ?></p>
        <div class="flex gap-2"><button onclick="acceptCookies()" class="px-4 py-2 rounded-lg text-xs font-semibold text-white transition" style="background:#0F766E;"><?php echo $lang==='en'?'Accept All':'Emera Byose'; ?></button><button onclick="rejectCookies()" class="px-4 py-2 rounded-lg text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition"><?php echo $lang==='en'?'Reject':'Yanga'; ?></button></div>
    </div>
</div>
<script>function getCookie(n){const v=`; ${document.cookie}`;const p=v.split(`; ${n}=`);if(p.length===2)return p.pop().split(';').shift();}function setCookie(n,v,d){const t=new Date();t.setTime(t.getTime()+(d*24*60*60*1000));document.cookie=`${n}=${v};expires=${t.toUTCString()};path=/`;}function acceptCookies(){setCookie('cookie_consent','accepted',365);document.getElementById('cookieBanner').style.display='none';}function rejectCookies(){setCookie('cookie_consent','rejected',365);document.getElementById('cookieBanner').style.display='none';}if(!getCookie('cookie_consent'))document.getElementById('cookieBanner').style.display='block';</script>

    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xl shadow-gray-200/50 border border-gray-100">
            <div class="text-center mb-6">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background:#0F766E;"><span class="text-white font-extrabold text-xl">I</span></div>
                <h1 class="text-xl font-extrabold text-gray-900"><?php echo __('create_account'); ?></h1>
                <p class="text-gray-500 text-sm mt-1"><?php echo __('join_today'); ?></p>
            </div>

            <?php if ($error==='empty'): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">Please fill in all fields.</div>
            <?php elseif ($error==='mismatch'): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">Passwords do not match.</div>
            <?php elseif ($error==='exists'): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">Account with this email already exists.</div>
            <?php endif; ?>

            <form action="auth/register_process.php" method="POST" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1"><?php echo __('full_name'); ?></label>
                        <input type="text" name="fullname" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Robert Niyonkuru" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1"><?php echo __('phone_number'); ?></label>
                        <input type="text" name="phone" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="0795064502" required>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1"><?php echo __('email'); ?></label>
                    <input type="email" name="email" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="vugatime@gmail.com" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1"><?php echo __('password'); ?></label>
                        <input type="password" name="password" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Min 6 characters" required minlength="6">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1"><?php echo __('confirm_password'); ?></label>
                        <input type="password" name="confirm_password" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Confirm" required minlength="6">
                    </div>
                </div>
                <input type="hidden" name="role" value="group_admin">
                <button type="submit" name="register" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all" style="background:#0F766E;"><?php echo __('create_account'); ?></button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-4">
                <?php echo __('already_have_account'); ?> <a href="login.php" class="font-semibold hover:underline" style="color:#0F766E;"><?php echo __('sign_in'); ?></a>
            </p>
        </div>
        <p class="text-center text-xs text-gray-400 mt-4">&copy; <?php echo date('Y'); ?> IkiminaAI.</p>
    </div>
</body>
</html>