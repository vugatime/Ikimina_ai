<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/language_switcher.php';
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$error = $_GET['error'] ?? '';
$expired = $_GET['expired'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - IkiminaAI</title>
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
            <div class="text-center mb-8">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#0F766E;">
                    <span class="text-white font-extrabold text-2xl">I</span>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Ikimina<span style="color:#0F766E;">AI</span></h1>
                <p class="text-gray-500 text-sm mt-1">Sign in to your account</p>
            </div>

            <?php if ($error === 'invalid'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-5">Invalid login credentials. Check your Member ID, email or phone and password.</div>
            <?php endif; ?>
            <?php if ($expired === '1'): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl text-sm mb-5">Session expired. Please sign in again.</div>
            <?php endif; ?>
            <?php if (isset($_GET['logout'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm mb-5">You have been logged out.</div>
            <?php endif; ?>
            <?php if (isset($_GET['reset'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm mb-5">Password reset successful. Please sign in.</div>
            <?php endif; ?>

            <form action="auth/login_process.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Member ID, Email or Phone</label>
                    <input type="text" name="login" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="e.g., ABI-001 or vugatime@gmail.com or 0795064502" required autofocus>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="Enter your password" required>
                </div>
                <div class="flex justify-end">
                    <a href="forgot_password.php" class="text-xs font-medium hover:underline" style="color:#0F766E;">Forgot password?</a>
                </div>
                <button type="submit" name="login" id="loginBtn" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">
                    <span id="loginText">Sign In</span>
                    <span id="loginSpinner" class="hidden">
                        <svg class="animate-spin h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Authenticating...
                    </span>
                </button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-6">
                Don't have an account? <a href="register.php" class="font-semibold hover:underline" style="color:#0F766E;">Create one</a>
            </p>
        </div>
        <p class="text-center text-xs text-gray-400 mt-6">&copy; <?php echo date('Y'); ?> IkiminaAI.</p>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('loginText').classList.add('hidden');
            document.getElementById('loginSpinner').classList.remove('hidden');
            document.getElementById('loginBtn').disabled = true;
            document.getElementById('loginBtn').style.opacity = '0.7';
        });
    </script>
</body>
</html>