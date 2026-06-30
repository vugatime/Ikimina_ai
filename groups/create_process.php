<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['create_group'])) {
    header('Location: create.php'); exit;
}

// Basic info
$group_name = trim($_POST['group_name']);
$district = trim($_POST['district']);
$sector = trim($_POST['sector']);
$cell_name = trim($_POST['cell_name']);
$village = trim($_POST['village']);
$description = trim($_POST['description']);

// Savings rules
$contribution_amount = $_POST['contribution_amount'] ?? 0;
$contribution_frequency = $_POST['contribution_frequency'] ?? 'monthly';
$expected_day = $_POST['expected_day'] ?? '1';

// Penalty rules
$late_penalty_type = $_POST['late_penalty_type'] ?? 'fixed';
$late_penalty_value = $_POST['late_penalty_value'] ?? 0;
$late_penalty_frequency = $_POST['late_penalty_frequency'] ?? 'weekly';
$grace_period_days = $_POST['grace_period_days'] ?? 3;
$meeting_absence_fine = $_POST['meeting_absence_fine'] ?? 0;
$meeting_late_fine = $_POST['meeting_late_fine'] ?? 0;

// Loan rules
$min_savings_for_loan = $_POST['min_savings_for_loan'] ?? 0;
$loan_max_amount = $_POST['loan_max_amount'] ?? 0;
$loan_interest_type = $_POST['loan_interest_type'] ?? 'monthly';
$loan_interest_rate = $_POST['loan_interest_rate'] ?? 10;
$loan_max_duration = $_POST['loan_max_duration'] ?? 3;

if (empty($group_name) || empty($district) || empty($sector) || empty($contribution_amount)) {
    header('Location: create.php?error=empty'); exit;
}

try {
    // Insert group with all rules
    $stmt = $pdo->prepare("INSERT INTO `groups` (
        group_name, district, sector, cell_name, village, 
        contribution_amount, contribution_frequency, expected_day,
        late_penalty_type, late_penalty_value, late_penalty_frequency, grace_period_days,
        meeting_absence_fine, meeting_late_fine, min_savings_for_loan,
        description, created_by, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $group_name, $district, $sector, $cell_name, $village,
        $contribution_amount, $contribution_frequency, $expected_day,
        $late_penalty_type, $late_penalty_value, $late_penalty_frequency, $grace_period_days,
        $meeting_absence_fine, $meeting_late_fine, $min_savings_for_loan,
        $description, $current_user_id
    ]);
    
    $groupId = $pdo->lastInsertId();

    // Add creator as group_admin
    $stmt = $pdo->prepare("INSERT INTO group_members (member_id, group_id, user_id, role_in_group, joined_at) VALUES (?, ?, ?, 'group_admin', NOW())");
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $group_name), 0, 3));
    if (strlen($prefix) < 3) $prefix = str_pad($prefix, 3, 'X');
    $memberId = $prefix . '-001';
    $stmt->execute([$memberId, $groupId, $current_user_id]);

    // Create default loan product if max amount > 0
    if ($loan_max_amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO loan_products (group_id, product_name, max_amount, interest_type, interest_rate, max_duration_months, late_penalty_type, late_penalty_value, late_penalty_frequency, min_savings_required) VALUES (?, 'Standard Loan', ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$groupId, $loan_max_amount, $loan_interest_type, $loan_interest_rate, $loan_max_duration, $late_penalty_type, $late_penalty_value, $late_penalty_frequency, $min_savings_for_loan]);
    }

    // Log activity
    $desc = "Group '{$group_name}' created with rules";
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, new_data, created_at) VALUES (?, ?, 'group_created', ?, ?, NOW())");
    $stmt->execute([$current_user_id, $groupId, $desc, json_encode([
        'contribution' => $contribution_amount . ' RWF / ' . $contribution_frequency,
        'penalty' => $late_penalty_value . ' ' . $late_penalty_type . ' / ' . $late_penalty_frequency,
        'loan_interest' => $loan_interest_rate . '% ' . $loan_interest_type
    ])]);

    // Notification
    createNotification($pdo, $current_user_id, 'Group Created', "Your group '{$group_name}' has been created with your custom rules.", 'success');

    // Email
    $freqLabel = ['daily' => 'Day', 'weekly' => 'Week', 'biweekly' => '2 Weeks', 'monthly' => 'Month'];
    $freq = $freqLabel[$contribution_frequency] ?? 'Month';
    $subject = 'Group Created: ' . $group_name;
    $subtitle = 'Your ikimina is ready with custom rules.';
    $content = "<p><strong>{$group_name}</strong><br>Location: {$district}, {$sector}<br>Contribution: " . number_format($contribution_amount) . " RWF per {$freq}<br>Late Penalty: {$late_penalty_value} {$late_penalty_type} per {$late_penalty_frequency}<br>Loan Interest: {$loan_interest_rate}% {$loan_interest_type}</p>";
    $body = emailTemplate($subject, $subtitle, $content, APP_URL . '/members/manage.php?group_id=' . $groupId, 'Add Members Now');
    queueAndSendEmail($pdo, $current_user_id, $_SESSION['user_email'], $subject, $body);

    // Notify Super Admin
    $stmt = $pdo->query("SELECT id, email FROM users WHERE role = 'super_admin' LIMIT 1");
    $superAdmin = $stmt->fetch();
    if ($superAdmin) {
        createNotification($pdo, $superAdmin['id'], 'New Group', "{$group_name} created by {$current_user_name}", 'info');
        queueAndSendEmail($pdo, $superAdmin['id'], $superAdmin['email'], 'New Group: ' . $group_name, emailTemplate('New Group', '', "<p>{$group_name} by {$current_user_name}</p>", '', ''));
    }

    header('Location: manage.php?created=1'); exit;
} catch (PDOException $e) {
    error_log("Create group error: " . $e->getMessage());
    header('Location: create.php?error=server'); exit;
}