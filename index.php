<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/language_switcher.php';
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IkiminaAI — <?php echo __('app_tagline'); ?></title>
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
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .gradient-hero { background: linear-gradient(160deg, #0F172A 0%, #134E4A 35%, #0F766E 70%, #115E59 100%); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideInLeft { from { opacity: 0; transform: translateX(-40px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
        .animate-fade-in { animation: fadeIn 0.8s ease forwards; }
        .animate-slide-left { animation: slideInLeft 0.8s ease forwards; }
        .animate-slide-right { animation: slideInRight 0.8s ease forwards; }
        .delay-200 { animation-delay: 0.2s; }
        .hero-image { border-radius: 16px; box-shadow: 0 25px 60px rgba(0,0,0,0.3); object-fit: cover; width: 100%; height: auto; }
        .feature-image { border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); object-fit: cover; width: 100%; height: 280px; transition: transform 0.3s ease; }
        .feature-card:hover .feature-image { transform: scale(1.02); }
        
        /* Horizontal Scroll */
        .scroll-container {
            display: flex; overflow-x: auto; scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch; scrollbar-width: none;
            gap: 1rem; padding: 0.5rem 1rem 2rem 1rem;
        }
        .scroll-container::-webkit-scrollbar { display: none; }
        .scroll-card { flex: 0 0 auto; scroll-snap-align: start; }
        
        /* Scroll Indicator Dots */
        .scroll-dots { display: flex; justify-content: center; gap: 0.5rem; margin-top: -0.5rem; padding-bottom: 1.5rem; }
        .scroll-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; transition: all 0.3s ease; cursor: pointer; }
        .scroll-dot.active { background: #0F766E; width: 24px; border-radius: 4px; }
        
        /* Scroll Hint Arrow */
        .scroll-hint { display: flex; align-items: center; justify-content: center; gap: 0.25rem; color: #94a3b8; font-size: 0.75rem; font-weight: 500; padding-top: 0.25rem; animation: bounce 2s infinite; }
        @keyframes bounce { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(6px); } }
        
        @media (max-width: 640px) {
            .feature-card-mobile { width: 80vw; }
            .plan-card-mobile { width: 85vw; }
            .step-card-mobile { width: 75vw; }
        }
        @media (min-width: 1024px) {
            .scroll-dots, .scroll-hint { display: none; }
        }
        
        /* Legal Modal */
        .legal-modal { display: none; }
        .legal-modal.open { display: flex; }
    </style>
</head>
<body class="bg-surface">

<!-- ========== COOKIE CONSENT BANNER ========== -->
<div id="cookieBanner" class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-2xl p-4" style="display:none;">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex-1">
            <p class="text-sm text-gray-700">
                <?php echo $lang === 'en' ? 'We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.' : 'Dukoresha kuki kugira ngo tube inzira nziza. Ukomeje gukoresha uru rubuga uremera gukoresha kuki.'; ?>
                <a href="javascript:void(0)" onclick="openLegal('cookies')" class="font-semibold underline" style="color:#0F766E;"><?php echo $lang === 'en' ? 'Learn more' : 'Menya byinshi'; ?></a>
            </p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <button onclick="acceptCookies()" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background:#0F766E;"><?php echo $lang === 'en' ? 'Accept All' : 'Emera Byose'; ?></button>
            <button onclick="rejectCookies()" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition"><?php echo $lang === 'en' ? 'Reject' : 'Yanga'; ?></button>
        </div>
    </div>
</div>

<!-- ========== NAVBAR ========== -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-xl border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="index.php" class="flex items-center gap-2 no-underline">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-extrabold text-lg" style="background:#0F766E;">I</div>
                <span class="font-extrabold text-xl text-gray-900">Ikimina<span style="color:#0F766E;">AI</span></span>
            </a>
            <div class="flex items-center gap-3">
                <a href="?lang=<?php echo $lang === 'en' ? 'rw' : 'en'; ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-200 hover:bg-gray-50 transition no-underline text-gray-600"><?php echo $lang === 'en' ? 'KI' : 'EN'; ?></a>
                <a href="auth.php" class="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-gray-900 no-underline transition"><?php echo __('sign_in'); ?></a>
                <a href="auth.php?tab=register" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white no-underline transition shadow-md" style="background:#0F766E;"><?php echo __('get_started'); ?></a>
            </div>
        </div>
    </div>
</nav>

<!-- ========== HERO ========== -->
<section class="gradient-hero pt-24 pb-16 md:pt-32 md:pb-20 px-4">
    <div class="max-w-6xl mx-auto flex flex-col lg:flex-row items-center gap-8 lg:gap-12">
        <div class="flex-1 text-center lg:text-left animate-slide-left">
            <div class="inline-block px-4 py-1.5 rounded-full text-sm font-semibold mb-6" style="background:rgba(20,184,166,0.2);color:#5eead4;border:1px solid rgba(94,234,212,0.3);"><?php echo __('built_for_rwanda'); ?></div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-6"><?php echo __('hero_title'); ?></h1>
            <p class="text-base sm:text-lg text-gray-300 max-w-xl mb-8 leading-relaxed"><?php echo __('hero_subtitle'); ?></p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                <a href="auth.php?tab=register" class="px-8 py-3.5 rounded-xl text-base font-bold text-white no-underline transition shadow-lg" style="background:#0F766E;"><?php echo __('start_free_trial'); ?></a>
                <a href="#features" class="px-8 py-3.5 rounded-xl text-base font-semibold text-white no-underline transition" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.2);"><?php echo __('learn_more'); ?></a>
            </div>
        </div>
        <div class="flex-1 animate-slide-right delay-200">
            <img src="assets/images/ab.png" alt="IkiminaAI Dashboard" class="hero-image">
        </div>
    </div>
</section>

<!-- ========== FEATURES ========== -->
<section id="features" class="py-16 md:py-20 px-0 bg-white">
    <div class="max-w-6xl mx-auto px-4 mb-10">
        <div class="text-center md:text-left">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 mb-4"><?php echo __('everything_you_need'); ?></h2>
            <p class="text-gray-500 text-base sm:text-lg max-w-2xl"><?php echo __('features_subtitle'); ?></p>
        </div>
    </div>
    
    <div class="scroll-container px-4 lg:grid lg:grid-cols-3 lg:gap-6 lg:overflow-visible lg:max-w-6xl lg:mx-auto" id="featureScroll">
        <div class="scroll-card feature-card-mobile lg:w-auto bg-white rounded-2xl border border-gray-100 shadow-sm flex-shrink-0 overflow-hidden feature-card">
            <img src="assets/images/aa.png" alt="Savings" class="feature-image">
            <div class="p-5"><h3 class="text-lg font-bold text-gray-900 mb-2"><?php echo __('smart_savings_title'); ?></h3><p class="text-gray-500 text-sm leading-relaxed"><?php echo __('smart_savings_desc'); ?></p></div>
        </div>
        <div class="scroll-card feature-card-mobile lg:w-auto bg-white rounded-2xl border border-gray-100 shadow-sm flex-shrink-0 overflow-hidden feature-card">
            <img src="assets/images/ac.png" alt="Loans" class="feature-image">
            <div class="p-5"><h3 class="text-lg font-bold text-gray-900 mb-2"><?php echo __('loan_management_title'); ?></h3><p class="text-gray-500 text-sm leading-relaxed"><?php echo __('loan_management_desc'); ?></p></div>
        </div>
        <div class="scroll-card feature-card-mobile lg:w-auto bg-white rounded-2xl border border-gray-100 shadow-sm flex-shrink-0 overflow-hidden feature-card">
            <img src="assets/images/ad.png" alt="Mobile" class="feature-image">
            <div class="p-5"><h3 class="text-lg font-bold text-gray-900 mb-2"><?php echo __('mobile_first_title'); ?></h3><p class="text-gray-500 text-sm leading-relaxed"><?php echo __('mobile_first_desc'); ?></p></div>
        </div>
    </div>
    <div class="scroll-dots" id="featureDots"></div>
    <div class="scroll-hint lg:hidden"><?php echo $lang === 'en' ? 'Swipe to see more' : 'Kanda urebe ibindi'; ?> <span style="font-size:1rem;">&rarr;</span></div>
</section>

<!-- ========== HOW IT WORKS ========== -->
<section class="py-16 md:py-20 px-0 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 mb-10 text-center">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 mb-4"><?php echo __('how_it_works'); ?></h2>
        <p class="text-gray-500 text-base sm:text-lg"><?php echo __('how_it_works_subtitle'); ?></p>
    </div>
    <div class="scroll-container px-4 lg:grid lg:grid-cols-4 lg:gap-6 lg:overflow-visible lg:max-w-6xl lg:mx-auto" id="stepsScroll">
        <div class="scroll-card step-card-mobile lg:w-auto bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex-shrink-0 text-center"><div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-white font-extrabold text-2xl" style="background:#0F766E;">1</div><h4 class="font-bold text-gray-900 mb-2"><?php echo __('step1_title'); ?></h4><p class="text-sm text-gray-500"><?php echo __('step1_desc'); ?></p></div>
        <div class="scroll-card step-card-mobile lg:w-auto bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex-shrink-0 text-center"><div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-white font-extrabold text-2xl" style="background:#0F766E;">2</div><h4 class="font-bold text-gray-900 mb-2"><?php echo __('step2_title'); ?></h4><p class="text-sm text-gray-500"><?php echo __('step2_desc'); ?></p></div>
        <div class="scroll-card step-card-mobile lg:w-auto bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex-shrink-0 text-center"><div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-white font-extrabold text-2xl" style="background:#0F766E;">3</div><h4 class="font-bold text-gray-900 mb-2"><?php echo __('step3_title'); ?></h4><p class="text-sm text-gray-500"><?php echo __('step3_desc'); ?></p></div>
        <div class="scroll-card step-card-mobile lg:w-auto bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex-shrink-0 text-center"><div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-white font-extrabold text-2xl" style="background:#0F766E;">4</div><h4 class="font-bold text-gray-900 mb-2"><?php echo __('step4_title'); ?></h4><p class="text-sm text-gray-500"><?php echo __('step4_desc'); ?></p></div>
    </div>
    <div class="scroll-dots" id="stepsDots"></div>
    <div class="scroll-hint lg:hidden"><?php echo $lang === 'en' ? 'Swipe to see more' : 'Kanda urebe ibindi'; ?> <span style="font-size:1rem;">&rarr;</span></div>
</section>

<!-- ========== PRICING ========== -->
<section id="pricing" class="py-16 md:py-20 px-0 bg-white">
    <div class="max-w-6xl mx-auto px-4 mb-10 text-center">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 mb-4"><?php echo __('plans_grow'); ?></h2>
        <p class="text-gray-500 text-base sm:text-lg"><?php echo __('plans_subtitle'); ?></p>
    </div>
    <div class="scroll-container px-4 lg:grid lg:grid-cols-4 lg:gap-6 lg:overflow-visible lg:max-w-6xl lg:mx-auto" id="plansScroll">
        <div class="scroll-card plan-card-mobile lg:w-auto bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex-shrink-0"><h3 class="text-lg font-bold text-gray-900"><?php echo __('starter_plan'); ?></h3><p class="text-sm text-gray-500"><?php echo __('starter_desc'); ?></p><div class="my-4"><span class="text-4xl font-extrabold"><?php echo __('free_30_days'); ?></span></div><ul class="space-y-2 mb-6 text-sm"><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('one_group'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('up_to_15_members'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('savings_tracking'); ?></li><li class="flex items-center gap-2 text-gray-400 line-through"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/></svg> <?php echo __('ai_insights_title'); ?></li></ul><a href="auth.php?tab=register" class="block w-full text-center py-3 rounded-xl text-sm font-bold text-white transition" style="background:#0F766E;"><?php echo __('choose_starter'); ?></a><p class="text-xs text-gray-400 text-center mt-2"><?php echo __('no_credit_card'); ?></p></div>
        <div class="scroll-card plan-card-mobile lg:w-auto bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex-shrink-0"><h3 class="text-lg font-bold text-gray-900"><?php echo __('community_plan'); ?></h3><p class="text-sm text-gray-500"><?php echo __('community_desc'); ?></p><div class="my-4"><span class="text-4xl font-extrabold">5,000</span><span class="text-sm text-gray-500"> RWF<?php echo __('per_month'); ?></span></div><ul class="space-y-2 mb-6 text-sm"><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('up_to_3_groups'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('up_to_75_members'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('pdf_excel_export'); ?></li></ul><a href="auth.php?tab=register" class="block w-full text-center py-3 rounded-xl text-sm font-bold transition" style="color:#0F766E;border:2px solid #0F766E;"><?php echo __('choose_community'); ?></a></div>
        <div class="scroll-card plan-card-mobile lg:w-auto bg-white rounded-2xl p-6 shadow-lg flex-shrink-0 relative" style="border:2.5px solid #0F766E;"><div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full text-xs font-bold text-white" style="background:#0F766E;"><?php echo __('most_popular'); ?></div><h3 class="text-lg font-bold text-gray-900 mt-2"><?php echo __('growth_plan'); ?></h3><p class="text-sm text-gray-500"><?php echo __('growth_desc'); ?></p><div class="my-4"><span class="text-4xl font-extrabold">15,000</span><span class="text-sm text-gray-500"> RWF<?php echo __('per_month'); ?></span></div><ul class="space-y-2 mb-6 text-sm"><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('up_to_10_groups'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('up_to_500_members'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('ai_loan_analysis'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('audit_logs'); ?></li></ul><a href="auth.php?tab=register" class="block w-full text-center py-3 rounded-xl text-sm font-bold text-white transition" style="background:#0F766E;"><?php echo __('choose_growth'); ?></a></div>
        <div class="scroll-card plan-card-mobile lg:w-auto bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex-shrink-0"><h3 class="text-lg font-bold text-gray-900"><?php echo __('business_plan'); ?></h3><p class="text-sm text-gray-500"><?php echo __('business_desc'); ?></p><div class="my-4"><span class="text-4xl font-extrabold">50,000</span><span class="text-sm text-gray-500"> RWF<?php echo __('per_month'); ?></span></div><ul class="space-y-2 mb-6 text-sm"><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('unlimited_groups'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('unlimited_members'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('full_ai'); ?></li><li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?php echo __('priority_support'); ?></li></ul><a href="auth.php?tab=register" class="block w-full text-center py-3 rounded-xl text-sm font-bold transition" style="color:#0F766E;border:2px solid #0F766E;"><?php echo __('choose_business'); ?></a></div>
    </div>
    <div class="scroll-dots" id="plansDots"></div>
    <div class="scroll-hint lg:hidden"><?php echo $lang === 'en' ? 'Swipe to compare plans' : 'Kanda urebe gahunda'; ?> <span style="font-size:1rem;">&rarr;</span></div>
</section>

<!-- ========== CTA ========== -->
<section class="py-16 px-4" style="background:#0F172A;">
    <div class="max-w-3xl mx-auto text-center">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-4"><?php echo __('cta_title'); ?></h2>
        <p class="text-gray-400 text-base sm:text-lg mb-8"><?php echo __('cta_subtitle'); ?></p>
        <a href="auth.php?tab=register" class="inline-block px-8 py-3.5 rounded-xl text-base font-bold text-white no-underline transition shadow-lg" style="background:#0F766E;"><?php echo __('get_started_free'); ?></a>
    </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="bg-sidebar text-gray-400 py-12 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
            <div class="col-span-2 lg:col-span-1"><div class="flex items-center gap-2 mb-4"><div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm" style="background:#0F766E;">I</div><span class="font-extrabold text-white text-lg">Ikimina<span style="color:#14b8a6;">AI</span></span></div><p class="text-sm leading-relaxed"><?php echo __('footer_brand'); ?></p></div>
            <div><h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-4"><?php echo __('platform_links'); ?></h4><ul class="space-y-2 text-sm"><li><a href="auth.php" class="text-gray-400 hover:text-white transition no-underline"><?php echo __('sign_in'); ?></a></li><li><a href="auth.php?tab=register" class="text-gray-400 hover:text-white transition no-underline"><?php echo __('register'); ?></a></li><li><a href="#features" class="text-gray-400 hover:text-white transition no-underline"><?php echo __('everything_you_need'); ?></a></li><li><a href="#pricing" class="text-gray-400 hover:text-white transition no-underline"><?php echo __('plans_grow'); ?></a></li></ul></div>
            <div><h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-4"><?php echo __('legal_links'); ?></h4><ul class="space-y-2 text-sm"><li><a href="javascript:void(0)" onclick="openLegal('privacy')" class="text-gray-400 hover:text-white transition no-underline"><?php echo __('privacy_policy'); ?></a></li><li><a href="javascript:void(0)" onclick="openLegal('terms')" class="text-gray-400 hover:text-white transition no-underline"><?php echo __('terms_of_service'); ?></a></li><li><a href="javascript:void(0)" onclick="openLegal('cookies')" class="text-gray-400 hover:text-white transition no-underline"><?php echo __('cookie_policy'); ?></a></li></ul></div>
            <div><h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-4"><?php echo __('contact'); ?></h4><ul class="space-y-2 text-sm text-gray-400"><li>vugatime@gmail.com</li><li>0795064502</li><li>Kigali, Rwanda</li></ul></div>
        </div>
        <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row justify-between items-center gap-2 text-sm"><p>&copy; <?php echo date('Y'); ?> IkiminaAI. <?php echo __('copyright'); ?>. <?php echo __('built_in'); ?>.</p></div>
    </div>
</footer>

<!-- ========== LEGAL MODALS ========== -->
<div id="modal-privacy" class="legal-modal fixed inset-0 z-50 bg-black/60 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-85vh overflow-y-auto shadow-2xl"><div class="sticky top-0 bg-white flex justify-between items-center p-5 border-b z-10"><h3 class="font-extrabold text-lg"><?php echo __('privacy_policy'); ?></h3><button onclick="closeLegal('privacy')" class="text-2xl text-gray-400 hover:text-gray-800">&times;</button></div>
    <div class="p-6 text-sm leading-relaxed text-gray-700 space-y-4"><p><strong>Last updated:</strong> <?php echo date('F d, Y'); ?></p><h4 class="font-bold text-gray-900">1. Information We Collect</h4><p>IkiminaAI collects: Full name, phone number, email address, savings records, loan history, group membership data, meeting attendance, and technical data.</p><h4 class="font-bold text-gray-900">2. How We Use Your Data</h4><p>Your data is used to manage your savings group, calculate AI credit scores, generate reports, send notifications, and prevent fraud. We do NOT sell your data.</p><h4 class="font-bold text-gray-900">3. Data Security</h4><p>Passwords are encrypted with bcrypt. Role-based access control ensures members only see their own records.</p><h4 class="font-bold text-gray-900">4. Contact</h4><p>Email: vugatime@gmail.com | Phone: 0795064502 | Kigali, Rwanda</p></div></div>
</div>

<div id="modal-terms" class="legal-modal fixed inset-0 z-50 bg-black/60 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-85vh overflow-y-auto shadow-2xl"><div class="sticky top-0 bg-white flex justify-between items-center p-5 border-b z-10"><h3 class="font-extrabold text-lg"><?php echo __('terms_of_service'); ?></h3><button onclick="closeLegal('terms')" class="text-2xl text-gray-400 hover:text-gray-800">&times;</button></div>
    <div class="p-6 text-sm leading-relaxed text-gray-700 space-y-4"><p><strong>Last updated:</strong> <?php echo date('F d, Y'); ?></p><h4 class="font-bold text-gray-900">1. Acceptance</h4><p>By using IkiminaAI, you agree to these terms. You must be 18+.</p><h4 class="font-bold text-gray-900">2. Account Responsibility</h4><p>Keep your login credentials safe.</p><h4 class="font-bold text-gray-900">3. Financial Disclaimer</h4><p>IkiminaAI is a record-keeping platform, NOT a bank or SACCO. AI scores are advisory.</p><h4 class="font-bold text-gray-900">4. Governing Law</h4><p>Republic of Rwanda.</p></div></div>
</div>

<div id="modal-cookies" class="legal-modal fixed inset-0 z-50 bg-black/60 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-85vh overflow-y-auto shadow-2xl"><div class="sticky top-0 bg-white flex justify-between items-center p-5 border-b z-10"><h3 class="font-extrabold text-lg"><?php echo __('cookie_policy'); ?></h3><button onclick="closeLegal('cookies')" class="text-2xl text-gray-400 hover:text-gray-800">&times;</button></div>
    <div class="p-6 text-sm leading-relaxed text-gray-700 space-y-4"><p><strong>Last updated:</strong> <?php echo date('F d, Y'); ?></p><h4 class="font-bold text-gray-900">What Are Cookies?</h4><p>Small text files stored on your device when you visit websites.</p><h4 class="font-bold text-gray-900">Cookies We Use</h4><p>Only essential cookies for login sessions and security. No advertising or tracking cookies.</p></div></div>
</div>

<!-- ========== SCRIPTS ========== -->
<script>
// Cookie Consent
function getCookie(n){const v=`; ${document.cookie}`;const p=v.split(`; ${n}=`);if(p.length===2)return p.pop().split(';').shift();}
function setCookie(n,v,d){const t=new Date();t.setTime(t.getTime()+(d*24*60*60*1000));document.cookie=`${n}=${v};expires=${t.toUTCString()};path=/`;}
function acceptCookies(){setCookie('cookie_consent','accepted',365);document.getElementById('cookieBanner').style.display='none';}
function rejectCookies(){setCookie('cookie_consent','rejected',365);document.getElementById('cookieBanner').style.display='none';}
if(!getCookie('cookie_consent'))document.getElementById('cookieBanner').style.display='block';

// Legal Modals
function openLegal(t){const m=document.getElementById('modal-'+t);m.classList.add('open');document.body.style.overflow='hidden';}
function closeLegal(t){const m=document.getElementById('modal-'+t);m.classList.remove('open');document.body.style.overflow='';}
document.addEventListener('click',function(e){if(e.target.classList.contains('legal-modal')&&e.target.classList.contains('open')){e.target.classList.remove('open');document.body.style.overflow='';}});

// Scroll Indicators
function setupScrollDots(containerId,dotsId){
    const c=document.getElementById(containerId),d=document.getElementById(dotsId);
    if(!c||!d)return;
    const cards=c.querySelectorAll('.scroll-card');d.innerHTML='';
    cards.forEach((_,i)=>{const dot=document.createElement('div');dot.className='scroll-dot';dot.onclick=()=>cards[i].scrollIntoView({behavior:'smooth',block:'nearest',inline:'start'});d.appendChild(dot);});
    c.addEventListener('scroll',()=>{const s=c.scrollLeft,w=cards[0].offsetWidth+16,i=Math.round(s/w);d.querySelectorAll('.scroll-dot').forEach((dt,j)=>dt.classList.toggle('active',j===i));});
    d.querySelector('.scroll-dot')?.classList.add('active');
}
setupScrollDots('featureScroll','featureDots');
setupScrollDots('stepsScroll','stepsDots');
setupScrollDots('plansScroll','plansDots');
</script>

</body>
</html>
