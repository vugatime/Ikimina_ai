            </div><!-- Close page content -->

            <!-- Footer - Full Width -->
            <footer class="bg-sidebar text-gray-400 w-full flex-shrink-0">
                <div class="max-w-7xl mx-auto px-6 py-10">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                        <!-- Brand -->
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm" style="background:#0F766E;">I</div>
                                <span class="font-extrabold text-white text-lg">Ikimina<span style="color:#14b8a6;">AI</span></span>
                            </div>
                            <p class="text-sm leading-relaxed text-gray-400">Smart digital savings and loan management platform built for Rwandan community savings groups. Save smarter, borrow fairly, grow together.</p>
                        </div>

                        <!-- Platform -->
                        <div>
                            <h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-4">Platform</h4>
                            <ul class="space-y-2">
                                <li><a href="<?php echo $base; ?>dashboard.php" class="text-sm text-gray-400 hover:text-white transition no-underline">Dashboard</a></li>
                                <li><a href="<?php echo $base; ?>groups/manage.php" class="text-sm text-gray-400 hover:text-white transition no-underline">My Groups</a></li>
                                <li><a href="<?php echo $base; ?>members/manage.php" class="text-sm text-gray-400 hover:text-white transition no-underline">Members</a></li>
                                <li><a href="<?php echo $base; ?>savings/record.php" class="text-sm text-gray-400 hover:text-white transition no-underline">Savings</a></li>
                                <li><a href="<?php echo $base; ?>loans/manage.php" class="text-sm text-gray-400 hover:text-white transition no-underline">Loans</a></li>
                            </ul>
                        </div>

                        <!-- Legal -->
                        <div>
                            <h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-4">Legal</h4>
                            <ul class="space-y-2">
                                <li><button onclick="openModal('privacy')" class="text-sm text-gray-400 hover:text-white transition bg-transparent border-none cursor-pointer p-0">Privacy Policy</button></li>
                                <li><button onclick="openModal('terms')" class="text-sm text-gray-400 hover:text-white transition bg-transparent border-none cursor-pointer p-0">Terms of Service</button></li>
                                <li><button onclick="openModal('cookies')" class="text-sm text-gray-400 hover:text-white transition bg-transparent border-none cursor-pointer p-0">Cookie Policy</button></li>
                                <li><button onclick="openModal('data')" class="text-sm text-gray-400 hover:text-white transition bg-transparent border-none cursor-pointer p-0">Data Protection</button></li>
                                <li><button onclick="openModal('refund')" class="text-sm text-gray-400 hover:text-white transition bg-transparent border-none cursor-pointer p-0">Refund Policy</button></li>
                            </ul>
                        </div>

                        <!-- Contact -->
                        <div>
                            <h4 class="text-white text-xs font-semibold uppercase tracking-wider mb-4">Contact</h4>
                            <ul class="space-y-2 text-sm text-gray-400">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    vugatime@gmail.com
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                                    0795064502
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Kigali, Rwanda
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Bottom Bar -->
                    <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row justify-between items-center gap-2">
                        <p class="text-sm text-gray-500">&copy; <?php echo date('Y'); ?> IkiminaAI. All rights reserved. Built in Rwanda.</p>
                        <p class="text-xs text-gray-600">Version 1.0 — Phase 1</p>
                    </div>
                </div>
            </footer>
        </div><!-- Close main content wrapper -->
    </div><!-- Close flex wrapper -->

    <!-- ============ LEGAL MODALS ============ -->
    
    <!-- Privacy Policy Modal -->
    <div id="modal-privacy" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto shadow-2xl">
            <div class="sticky top-0 bg-white flex justify-between items-center p-5 border-b z-10">
                <h3 class="font-extrabold text-lg text-gray-900">Privacy Policy</h3>
                <button onclick="closeModal('privacy')" class="text-2xl text-gray-400 hover:text-gray-800 leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">&times;</button>
            </div>
            <div class="p-6 text-sm leading-relaxed text-gray-700 space-y-4">
                <p><strong class="text-gray-900">Last updated:</strong> <?php echo date('F d, Y'); ?></p>
                
                <h4 class="text-base font-bold text-gray-900 mt-4">1. Information We Collect</h4>
                <p>IkiminaAI collects the following personal information when you register and use our platform:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Personal Data:</strong> Full name, email address, phone number, and role within your savings group.</li>
                    <li><strong>Financial Data:</strong> Savings amounts, loan applications, repayment history, and contribution records.</li>
                    <li><strong>Group Data:</strong> Group names, locations (district, sector, cell, village), and membership details.</li>
                    <li><strong>Technical Data:</strong> IP address, browser type, device information, and login timestamps for security purposes.</li>
                </ul>

                <h4 class="text-base font-bold text-gray-900 mt-4">2. How We Use Your Information</h4>
                <p>Your data is used exclusively for the following purposes:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Managing your savings group records and transactions with complete transparency.</li>
                    <li>Calculating AI-powered credit scores and loan eligibility assessments.</li>
                    <li>Generating financial reports and group health analytics for informed decision-making.</li>
                    <li>Sending payment reminders, meeting notifications, and important platform updates.</li>
                    <li>Improving platform security, preventing fraud, and detecting suspicious activities.</li>
                </ul>
                <p><strong>We do NOT sell, rent, or trade your personal data to third parties for marketing purposes.</strong></p>

                <h4 class="text-base font-bold text-gray-900 mt-4">3. Data Sharing & Third Parties</h4>
                <p>Your information is only shared in these limited circumstances:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Within Your Group:</strong> Group admins and treasurers can view member savings, loans, and attendance records relevant to group management.</li>
                    <li><strong>Legal Requirements:</strong> If required by Rwandan law enforcement or regulatory authorities with proper legal documentation.</li>
                    <li><strong>Service Providers:</strong> Trusted third parties who help us operate (hosting, SMS delivery) — bound by strict confidentiality agreements.</li>
                </ul>

                <h4 class="text-base font-bold text-gray-900 mt-4">4. Data Security</h4>
                <p>We implement bank-level security measures including:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Passwords encrypted using bcrypt hashing (never stored in plain text).</li>
                    <li>All data transmitted over HTTPS secure connections.</li>
                    <li>Role-based access control — members only see their own data.</li>
                    <li>Regular security audits and automated vulnerability scanning.</li>
                </ul>

                <h4 class="text-base font-bold text-gray-900 mt-4">5. Your Rights</h4>
                <p>Under Rwandan data protection principles, you have the right to:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Access all personal data we hold about you at any time.</li>
                    <li>Request corrections to inaccurate or incomplete information.</li>
                    <li>Request deletion of your account and associated data.</li>
                    <li>Export your financial records in standard formats (PDF, CSV).</li>
                    <li>Withdraw consent at any time, subject to ongoing group obligations.</li>
                </ul>
                <p>To exercise these rights, contact us at <strong>vugatime@gmail.com</strong>.</p>

                <h4 class="text-base font-bold text-gray-900 mt-4">6. Data Retention</h4>
                <p>We retain your data for as long as your account is active. Financial transaction records are kept for a minimum of 5 years to comply with audit requirements, even after account closure. You may request early deletion of non-financial personal data.</p>

                <h4 class="text-base font-bold text-gray-900 mt-4">7. Contact Information</h4>
                <p>For privacy-related inquiries:<br>Email: <strong>vugatime@gmail.com</strong><br>Phone: <strong>0795064502</strong><br>Address: Kigali, Rwanda</p>
            </div>
        </div>
    </div>

    <!-- Terms of Service Modal -->
    <div id="modal-terms" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto shadow-2xl">
            <div class="sticky top-0 bg-white flex justify-between items-center p-5 border-b z-10">
                <h3 class="font-extrabold text-lg text-gray-900">Terms of Service</h3>
                <button onclick="closeModal('terms')" class="text-2xl text-gray-400 hover:text-gray-800 leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">&times;</button>
            </div>
            <div class="p-6 text-sm leading-relaxed text-gray-700 space-y-4">
                <p><strong class="text-gray-900">Last updated:</strong> <?php echo date('F d, Y'); ?></p>
                
                <h4 class="text-base font-bold text-gray-900 mt-4">1. Acceptance of Terms</h4>
                <p>By accessing or using IkiminaAI ("the Platform"), you agree to be bound by these Terms of Service. If you disagree with any part of these terms, you may not access the Platform.</p>

                <h4 class="text-base font-bold text-gray-900 mt-4">2. Eligibility</h4>
                <p>You must be at least 18 years of age to create an account and use the Platform. By registering, you represent and warrant that you meet this age requirement.</p>

                <h4 class="text-base font-bold text-gray-900 mt-4">3. Account Responsibilities</h4>
                <ul class="list-disc pl-5 space-y-1">
                    <li>You are responsible for maintaining the confidentiality of your login credentials.</li>
                    <li>You agree to provide accurate, current, and complete information during registration and usage.</li>
                    <li>You must not share your account credentials or allow unauthorized access to your account.</li>
                    <li>Notify us immediately at vugatime@gmail.com if you suspect any unauthorized use of your account.</li>
                </ul>

                <h4 class="text-base font-bold text-gray-900 mt-4">4. Prohibited Activities</h4>
                <p>Users agree NOT to:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Manipulate, falsify, or misrepresent financial records or transactions.</li>
                    <li>Use the platform for money laundering, fraud, or any illegal activities.</li>
                    <li>Harass, threaten, or defraud other members of the platform.</li>
                    <li>Attempt to hack, reverse-engineer, or compromise platform security.</li>
                    <li>Create fake accounts, impersonate others, or submit false information.</li>
                </ul>
                <p>Violation of these terms may result in immediate account suspension, permanent termination, and legal action where applicable under Rwandan law.</p>

                <h4 class="text-base font-bold text-gray-900 mt-4">5. Financial Disclaimer</h4>
                <p><strong>IkiminaAI is a record-keeping and analytics platform, NOT a bank, SACCO, or licensed financial institution.</strong> We do NOT:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Hold member funds or process monetary transactions directly (Phase 1-4).</li>
                    <li>Guarantee loan approvals or repayment by any member.</li>
                    <li>Take legal responsibility for disputes between group members.</li>
                    <li>Provide regulated financial advice. AI-generated scores are advisory tools only.</li>
                </ul>

                <h4 class="text-base font-bold text-gray-900 mt-4">6. Governing Law</h4>
                <p>These terms shall be governed by and construed in accordance with the laws of the Republic of Rwanda. Any disputes arising shall be subject to the exclusive jurisdiction of Rwandan courts.</p>

                <h4 class="text-base font-bold text-gray-900 mt-4">7. Contact</h4>
                <p>For questions about these terms, contact: <strong>vugatime@gmail.com</strong> | <strong>0795064502</strong></p>
            </div>
        </div>
    </div>

    <!-- Cookie Policy Modal -->
    <div id="modal-cookies" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto shadow-2xl">
            <div class="sticky top-0 bg-white flex justify-between items-center p-5 border-b z-10">
                <h3 class="font-extrabold text-lg text-gray-900">Cookie Policy</h3>
                <button onclick="closeModal('cookies')" class="text-2xl text-gray-400 hover:text-gray-800 leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">&times;</button>
            </div>
            <div class="p-6 text-sm leading-relaxed text-gray-700 space-y-4">
                <p><strong class="text-gray-900">Last updated:</strong> <?php echo date('F d, Y'); ?></p>
                <h4 class="text-base font-bold text-gray-900 mt-4">What Are Cookies?</h4>
                <p>Cookies are small text files stored on your device when you visit websites. They help the platform remember your preferences and improve your experience.</p>
                <h4 class="text-base font-bold text-gray-900 mt-4">Cookies We Use</h4>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Essential Cookies:</strong> Required for login sessions, security, and core platform functionality. The platform cannot function without these.</li>
                    <li><strong>Preference Cookies:</strong> Remember your language preferences and dashboard layout choices.</li>
                </ul>
                <h4 class="text-base font-bold text-gray-900 mt-4">Third-Party Cookies</h4>
                <p>We do NOT use advertising or tracking cookies from third-party networks. We do not sell or share cookie data with advertisers or data brokers.</p>
                <h4 class="text-base font-bold text-gray-900 mt-4">Managing Cookies</h4>
                <p>You can disable cookies in your browser settings. However, essential cookies are required for the Platform to function correctly. Disabling them may prevent you from logging in or using core features.</p>
            </div>
        </div>
    </div>

    <!-- Data Protection Modal -->
    <div id="modal-data" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto shadow-2xl">
            <div class="sticky top-0 bg-white flex justify-between items-center p-5 border-b z-10">
                <h3 class="font-extrabold text-lg text-gray-900">Data Protection</h3>
                <button onclick="closeModal('data')" class="text-2xl text-gray-400 hover:text-gray-800 leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">&times;</button>
            </div>
            <div class="p-6 text-sm leading-relaxed text-gray-700 space-y-4">
                <p><strong class="text-gray-900">Last updated:</strong> <?php echo date('F d, Y'); ?></p>
                <h4 class="text-base font-bold text-gray-900 mt-4">Our Commitment</h4>
                <p>IkiminaAI is committed to protecting your personal and financial data in accordance with Rwandan data protection principles and international best practices including GDPR standards where applicable.</p>
                <h4 class="text-base font-bold text-gray-900 mt-4">Protection Measures</h4>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Password Security:</strong> All passwords are hashed using bcrypt encryption — they cannot be reversed or decrypted.</li>
                    <li><strong>Database Security:</strong> Access restricted to authorized personnel only with multi-factor authentication.</li>
                    <li><strong>Input Validation:</strong> All user inputs are validated and sanitized to prevent SQL injection and XSS attacks.</li>
                    <li><strong>Session Management:</strong> Automatic session timeout after 1 hour of inactivity.</li>
                    <li><strong>Backups:</strong> Encrypted daily backups stored securely with 30-day retention.</li>
                </ul>
                <h4 class="text-base font-bold text-gray-900 mt-4">Breach Notification</h4>
                <p>In the unlikely event of a data breach, affected users will be notified within 72 hours via email and/or SMS with details of the breach and recommended actions.</p>
            </div>
        </div>
    </div>

    <!-- Refund Policy Modal -->
    <div id="modal-refund" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto shadow-2xl">
            <div class="sticky top-0 bg-white flex justify-between items-center p-5 border-b z-10">
                <h3 class="font-extrabold text-lg text-gray-900">Refund Policy</h3>
                <button onclick="closeModal('refund')" class="text-2xl text-gray-400 hover:text-gray-800 leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">&times;</button>
            </div>
            <div class="p-6 text-sm leading-relaxed text-gray-700 space-y-4">
                <p><strong class="text-gray-900">Last updated:</strong> <?php echo date('F d, Y'); ?></p>
                <h4 class="text-base font-bold text-gray-900 mt-4">Subscription Refunds</h4>
                <p>Monthly subscriptions can be cancelled at any time. Refunds are provided on a pro-rata basis for the remaining days in the billing cycle if requested within 7 days of payment.</p>
                <h4 class="text-base font-bold text-gray-900 mt-4">Free Tier</h4>
                <p>The Free plan requires no payment and can be used indefinitely with no charges.</p>
                <h4 class="text-base font-bold text-gray-900 mt-4">Contact</h4>
                <p>For refund requests: <strong>vugatime@gmail.com</strong> | <strong>0795064502</strong></p>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('show');
        }
        function toggleDropdown() {
            document.getElementById('dropdownMenu').classList.toggle('hidden');
        }
        document.addEventListener('click', function(e) {
            const dd = document.getElementById('userDropdown');
            if (!dd.contains(e.target)) {
                document.getElementById('dropdownMenu').classList.add('hidden');
            }
        });
        function openModal(id) {
            document.getElementById('modal-' + id).classList.remove('hidden');
            document.getElementById('modal-' + id).classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById('modal-' + id).classList.add('hidden');
            document.getElementById('modal-' + id).classList.remove('flex');
            document.body.style.overflow = '';
        }
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('fixed') && e.target.classList.contains('flex') && e.target.id.startsWith('modal-')) {
                e.target.classList.add('hidden');
                e.target.classList.remove('flex');
                document.body.style.overflow = '';
            }
        });
    </script>
</body>
</html>