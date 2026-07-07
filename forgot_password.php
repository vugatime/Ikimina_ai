<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/language_switcher.php';
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$sent = $_GET['sent'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - IkiminaAI</title>
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
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl p-8 shadow-xl shadow-gray-200/50 border border-gray-100">
            <div class="text-center mb-8">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#0F766E;">
                    <span class="text-white font-extrabold text-2xl">I</span>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Reset Password</h1>
                <p class="text-gray-500 text-sm mt-1">Enter your email to receive a reset code</p>
            </div>

            <?php if ($sent === '1'): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm mb-5">
                    If an account exists with that email, a reset code has been sent. Check your inbox.
                </div>
            <?php endif; ?>
            <?php if ($error === 'email'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-5">Please enter a valid email address.</div>
            <?php endif; ?>

            <form action="/auth/forgot_process.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                    <input type="email" name="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-50 transition" placeholder="vugatime@gmail.com" required autofocus>
                </div>
                <button type="submit" name="forgot" class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all" style="background:#0F766E;" onmouseover="this.style.background='#115E59'" onmouseout="this.style.background='#0F766E'">
                    Send Reset Code
                </button>
            </form>
            <p class="text-center text-sm text-gray-500 mt-6">
                Remember your password? <a href="auth.php" class="font-semibold hover:underline" style="color:#0F766E;">Sign in</a>
            </p>
        </div>
    </div>
</body>
</html>