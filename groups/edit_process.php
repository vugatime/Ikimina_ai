<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

// Handle delete product (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'delete_product') {
    $productId = $_GET['product_id'] ?? null;
    $groupId = $_GET['group_id'] ?? null;
    if ($productId && $groupId) {
        $stmt = $pdo->prepare("UPDATE loan_products SET status = 'inactive' WHERE id = ? AND group_id = ?");
        $stmt->execute([$productId, $groupId]);
        header('Location: edit.php?id=' . $groupId . '&msg=product_deleted'); exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage.php'); exit;
}

$groupId = $_POST['group_id'] ?? null;
if (!$groupId) { header('Location: manage.php'); exit; }

try {
    // Add new loan product
    if (isset($_POST['add_product'])) {
        $stmt = $pdo->prepare("INSERT INTO loan_products (group_id, product_name, max_amount, interest_type, interest_rate, max_duration_months, min_savings_required) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $groupId,
            trim($_POST['new_product_name']),
            $_POST['new_product_max'],
            $_POST['new_product_interest_type'],
            $_POST['new_product_rate'],
            $_POST['new_product_duration'],
            $_POST['new_product_min_savings']
        ]);
        header('Location: edit.php?id=' . $groupId . '&msg=product_added'); exit;
    }

    // Save all rules
    if (isset($_POST['save_rules'])) {
        $stmt = $pdo->prepare("UPDATE `groups` SET 
            group_name = ?, district = ?, sector = ?, cell_name = ?, village = ?, description = ?,
            contribution_amount = ?, contribution_frequency = ?, expected_day = ?,
            late_penalty_type = ?, late_penalty_value = ?, late_penalty_frequency = ?, grace_period_days = ?,
            meeting_absence_fine = ?, meeting_late_fine = ?, min_savings_for_loan = ?
            WHERE id = ?");
        $stmt->execute([
            trim($_POST['group_name']), trim($_POST['district']), trim($_POST['sector']),
            trim($_POST['cell_name']), trim($_POST['village']), trim($_POST['description']),
            $_POST['contribution_amount'], $_POST['contribution_frequency'], trim($_POST['expected_day']),
            $_POST['late_penalty_type'], $_POST['late_penalty_value'], $_POST['late_penalty_frequency'], $_POST['grace_period_days'],
            $_POST['meeting_absence_fine'], $_POST['meeting_late_fine'], $_POST['min_savings_for_loan'],
            $groupId
        ]);

        // Log
        $desc = "Group rules updated for " . trim($_POST['group_name']);
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, created_at) VALUES (?, ?, 'rules_updated', ?, NOW())");
        $stmt->execute([$current_user_id, $groupId, $desc]);

        createNotification($pdo, $current_user_id, 'Rules Updated', 'Group rules have been updated successfully.', 'success');

        header('Location: edit.php?id=' . $groupId . '&msg=saved'); exit;
    }
} catch (PDOException $e) {
    error_log("Edit group error: " . $e->getMessage());
    header('Location: edit.php?id=' . $groupId . '&msg=error'); exit;
}