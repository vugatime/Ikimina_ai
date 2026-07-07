# IkiminaAI — Smart Community Finance Platform

**Tagline:** Save Smarter. Borrow Fairly. Grow Together.

Built for Rwandan community savings groups (ibimina), IkiminaAI digitizes savings, loans, meetings, and provides AI-powered financial insights.

---

## Features

### Core Modules
- **Role-Based Dashboards** — Super Admin, Group Admin, Assistant Admin, Treasurer, Member
- **Group Management** — Create groups with custom savings rules, penalty rules, and loan products
- **Member Management** — Add/edit/remove members with unique Member IDs (ABI-0001 format)
- **Savings Recording** — Record contributions with auto-penalty detection for late payments
- **Loan Management** — Full workflow: Member requests → Admin reviews → Treasurer disburses → Repayments
- **Meeting Management** — Schedule meetings, take attendance, auto-apply fines
- **Financial Reports** — Personal reports with savings compliance, loan status, attendance history
- **AI Scoring Engine** — Trust scores, loan eligibility, default risk prediction, group health analytics

### Additional Features
- **Multi-language Support** — English and Kinyarwanda translations
- **Email Notifications** — Gmail SMTP integration for welcome emails, loan updates
- **Activity Logging** — Every action tracked with timestamps
- **Notification System** — In-app bell notifications
- **Cookie Consent** — GDPR-compliant cookie banner
- **Responsive Design** — Mobile-first with horizontal scroll on mobile
- **Landing Page** — Professional photography, pricing plans, features showcase

---

## Technology Stack

- **Backend:** PHP 8.2+
- **Database:** MySQL / MariaDB
- **Frontend:** Tailwind CSS (CDN), Chart.js, SweetAlert2
- **Fonts:** Inter (Google Fonts)
- **Email:** PHPMailer / PHP mail()

---

## Installation (Localhost - XAMPP)

### Prerequisites
- XAMPP with PHP 8.2+ and MySQL
- Apache mod_rewrite enabled

### Steps

1. **Clone or extract the project** into `C:\xampp\htdocs\ikimina-ai\`

2. **Create the database:**
   - Open `http://localhost/phpmyadmin`
   - Create a new database named `ikimina_ai`
   - Import `sql/ikimina_ai.sql`

3. **Configure database connection:**
   - Open `config/database.php`
   - Default credentials (XAMPP):

DB_HOST: localhost
DB_NAME: ikimina_ai
DB_USER: root
DB_PASS: (empty)


4. **Configure email (optional):**
- Open `config/email.php`
- Set your Gmail address and App Password
- Update `APP_URL` to `http://localhost/ikimina-ai`

5. **Start Apache and MySQL** in XAMPP Control Panel

6. **Access the application:**

http://localhost/ikimina-ai/


---

## Default Login Credentials

| Role | Login | Password |
|------|-------|----------|
| Super Admin | agasobanuyenews@gmail.com | Joselove@250 |
---

## User Roles & Permissions

| Feature | Super Admin | Group Admin | Assistant Admin | Treasurer | Member |
|---------|:-----------:|:-----------:|:---------------:|:---------:|:------:|
| View All Groups | Yes | - | - | - | - |
| Create Group | - | Yes | - | - | - |
| Manage Members | - | Yes | Yes | - | - |
| Record Savings | - | Yes | Yes | Yes | - |
| Review Loans | - | Yes | Yes | - | - |
| Disburse Loans | - | Yes | - | Yes | - |
| Request Loan | - | - | - | - | Yes |
| Manage Meetings | - | Yes | Yes | Yes | - |
| View Reports | - | Yes | Yes | Yes | Yes |

---

## File Structure
ikimina-ai/
├── index.php # Landing page
├── login.php # Login form
├── register.php # Registration form
├── dashboard.php # Role-based dashboard
├── report.php # Personal financial report
├── profile.php # User profile & password change
├── settings.php # System settings
├── forgot_password.php # Password reset request
├── verify_code.php # OTP verification
├── reset_password.php # New password form
├── logout.php # Logout handler
├── config/
│ ├── database.php # Database connection
│ ├── session.php # Session configuration
│ ├── email.php # Email functions & templates
│ └── ai_engine.php # AI scoring engine
├── includes/
│ ├── header.php # HTML head, sidebar, topbar
│ ├── footer.php # Footer & legal modals
│ ├── topbar.php # Notification bell, user dropdown
│ ├── sidebar.php # Role-based navigation
│ ├── auth_check.php # Authentication middleware
│ └── language_switcher.php # EN/RW language toggle
├── auth/
│ ├── login_process.php # Login processing
│ ├── register_process.php # Registration processing
│ ├── forgot_process.php # Password reset OTP send
│ ├── verify_process.php # OTP verification
│ └── reset_process.php # Password update
├── groups/
│ ├── create.php # Create group form
│ ├── create_process.php # Group creation processing
│ ├── manage.php # Group list
│ ├── edit.php # Edit group rules
│ └── edit_process.php # Rules update processing
├── members/
│ ├── manage.php # Member list & management
│ └── manage_process.php # Add/edit/remove processing
├── savings/
│ ├── record.php # Savings recording form
│ └── record_process.php # Savings processing
├── loans/
│ ├── request.php # Member loan request
│ ├── request_process.php # Loan request processing
│ ├── review.php # Admin loan review
│ ├── review_process.php # Review processing
│ ├── disburse.php # Treasurer disbursement
│ ├── disburse_process.php # Disbursement processing
│ ├── manage.php # Loan management
│ ├── apply_process.php # Loan application
│ ├── approve.php # Loan approval
│ ├── reject.php # Loan rejection
│ └── repay.php # Repayment recording
├── meetings/
│ ├── manage.php # Meeting list & creation
│ ├── meeting_process.php # Meeting processing
│ ├── attendance.php # Attendance taking
│ └── attendance_process.php # Attendance processing
├── admin/
│ └── users.php # Super Admin user management
├── lang/
│ ├── en.php # English translations
│ └── rw.php # Kinyarwanda translations
├── assets/
│ ├── css/
│ │ └── style.css # Global styles
│ └── images/ # Landing page images
│ ├── aa.png
│ ├── ab.png
│ ├── ac.png
│ └── ad.png
└── sql/
└── ikimina_ai.sql # Database schema


---

## Deployment Notes

When deploying to hosting:
1. Move `auth/login_process.php` and `auth/register_process.php` to root
2. Change form actions from `auth/` to `/`
3. Update `$base` in `includes/header.php` to `/`
4. Update database credentials in `config/database.php`
5. Update `APP_URL` in `config/email.php`

---

## Pricing Plans

| Plan | Price | Features |
|------|-------|----------|
| Starter | Free (30 days) | 1 Group, 15 Members, Savings & Loans |
| Community | 5,000 RWF/month | 3 Groups, 75 Members, Reports |
| Growth | 15,000 RWF/month | 10 Groups, 500 Members, AI Insights |
| Business | 50,000 RWF/month | Unlimited, Full AI, API Access |

---

## Credits

- **Developer:** Robert Niyonkuru
- **Contact:** vugatime@gmail.com
- **Phone:** 0795064502
- **Location:** Kigali, Rwanda
- **Version:** 1.0

---

## License

All rights reserved. Built for Rwanda.