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
    <title>Create Account - IkiminaAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#f0fdfa', 100: '#ccfbf1', 500: '#14b8a6', 600: '#0F766E', 700: '#115E59', 800: '#134E4A' }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-slate-50">
    <!-- ========== COOKIE CONSENT BANNER ========== -->
<div id="cookieBanner" class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-2xl p-4" style="display:none;">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-xs sm:text-sm text-gray-700 flex-1">
            <?php echo $lang === 'en' 
                ? 'We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.'
                : 'Dukoresha kuki kugira ngo tube inzira nziza. Ukomeje gukoresha uru rubuga uremera gukoresha kuki.'; ?>
        </p>
        <div class="flex gap-2 flex-shrink-0">
            <button onclick="acceptCookies()" class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold text-white transition" style="background:#0F766E;">
                <?php echo $lang === 'en' ? 'Accept All' : 'Emera Byose'; ?>
            </button>
            <button onclick="rejectCookies()" class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
                <?php echo $lang === 'en' ? 'Reject' : 'Yanga'; ?>
            </button>
        </div>
    </div>
</div>

<script>
function getCookie(name){const v=`; ${document.cookie}`;const p=v.split(`; ${name}=`);if(p.length===2)return p.pop().split(';').shift();}
function setCookie(name,value,days){const d=new Date();d.setTime(d.getTime()+(days*24*60*60*1000));document.cookie=`${name}=${value};expires=${d.toUTCString()};path=/`;}
function acceptCookies(){setCookie('cookie_consent','accepted',365);document.getElementById('cookieBanner').style.display='none';}
function rejectCookies(){setCookie('cookie_consent','rejected',365);document.getElementById('cookieBanner').style.display='none';}
if(!getCookie('cookie_consent')){document.getElementById('cookieBanner').style.display='block';}
</script>
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl p-8 shadow-xl shadow-gray-200/50 border border-gray-100">
            <div class="text-center mb-6">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#0F766E;">
                    <span class="text-white font-extrabold text-2xl">I</span>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Create Your Account</h1>
                <p class="text-gray-500 text-sm mt-1">Join IkiminaAI today</p>
            </div>

            <?php if ($error === 'empty'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">Please fill in all required fields.</div>
            <?php elseif ($error === 'mismatch'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">Passwords do not match.</div>
            <?php elseif ($error === 'exists'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">An account with this email already exists.</div>
            <?php elseif ($error === 'role'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">Please select a valid role.</div>
            <?php elseif ($error === 'server'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">Server error. Please try again.</div>
            <?php endif; ?>

            <form action="auth/register_process.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name</label>
                    <input type="text" name="fullname" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Robert Niyonkuru" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                    <input type="email" name="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="vugatime@gmail.com" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Phone Number</label>
                    <input type="text" name="phone" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="0795064502" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Role</label>
                    <select name="role" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" required>
                        <option value="">Select your role</option>
                        <option value="group_admin">Group Admin</option>
                        <option value="treasurer">Treasurer</option>
                        <option value="member">Member</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Minimum 6 characters" required minlength="6">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm Password</label>
                    <input type="password" name="confirm_password" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Repeat your password" required minlength="6">
                </div>
                <button type="submit" name="register" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">
                    Create Account
                </button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-6">
                Already have an account? <a href="login.php" class="font-semibold hover:underline" style="color:#0F766E;">Sign in</a>
            </p>
        </div>
        <p class="text-center text-xs text-gray-400 mt-6">&copy; <?php echo date('Y'); ?> IkiminaAI.</p>
    </div>
</body>
</html>