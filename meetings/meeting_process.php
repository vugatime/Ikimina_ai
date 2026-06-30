<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_REQUEST['action'] ?? '';
$groupId = $_REQUEST['group_id'] ?? null;

if (!$groupId) { header('Location: manage.php'); exit; }

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $meetingDate = $_POST['meeting_date'];
    $agenda = trim($_POST['agenda']);
    
    $stmt = $pdo->prepare("INSERT INTO meetings (group_id, meeting_date, agenda, status, created_by, created_at) VALUES (?, ?, ?, 'scheduled', ?, NOW())");
    $stmt->execute([$groupId, $meetingDate, $agenda, $current_user_id]);
    
    header('Location: manage.php?group_id=' . $groupId . '&msg=created'); exit;
}

if ($action === 'cancel' && isset($_GET['meeting_id'])) {
    $stmt = $pdo->prepare("UPDATE meetings SET status = 'cancelled' WHERE id = ? AND group_id = ?");
    $stmt->execute([$_GET['meeting_id'], $groupId]);
    header('Location: manage.php?group_id=' . $groupId); exit;
}

header('Location: manage.php?group_id=' . $groupId);