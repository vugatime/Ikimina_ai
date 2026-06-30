<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['save_attendance'])) {
    header('Location: manage.php'); exit;
}

$meetingId = $_POST['meeting_id'];
$groupId = $_POST['group_id'];
$attendance = $_POST['attendance'] ?? [];
$fines = $_POST['fines'] ?? [];

try {
    foreach ($attendance as $memberId => $status) {
        $fineAmount = $fines[$memberId] ?? 0;
        
        // Check if attendance already exists
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE meeting_id = ? AND member_id = ?");
        $stmt->execute([$meetingId, $memberId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, fine_amount = ? WHERE id = ?");
            $stmt->execute([$status, $fineAmount, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (meeting_id, member_id, status, fine_amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$meetingId, $memberId, $status, $fineAmount]);
        }
        
        // Auto-create fine record if absent or late
        if (in_array($status, ['absent', 'late']) && $fineAmount > 0) {
            $reason = $status === 'absent' ? 'absent_meeting' : 'late_meeting';
            // Check if fine already exists
            $stmt = $pdo->prepare("SELECT id FROM fines WHERE member_id = ? AND meeting_id = ?");
            $stmt->execute([$memberId, $meetingId]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO fines (member_id, group_id, meeting_id, amount, reason, status, issued_date) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                $stmt->execute([$memberId, $groupId, $meetingId, $fineAmount, $reason]);
            }
        }
    }
    
    // Mark meeting as completed
    $stmt = $pdo->prepare("UPDATE meetings SET status = 'completed' WHERE id = ?");
    $stmt->execute([$meetingId]);
    
    // Log
    $desc = "Attendance recorded for meeting on " . date('d M Y');
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, group_id, action, description, created_at) VALUES (?, ?, 'attendance_saved', ?, NOW())");
    $stmt->execute([$current_user_id, $groupId, $desc]);
    
    header('Location: manage.php?group_id=' . $groupId . '&msg=attendance_saved'); exit;
} catch (PDOException $e) {
    error_log("Attendance error: " . $e->getMessage());
    header('Location: attendance.php?meeting_id=' . $meetingId . '&group_id=' . $groupId); exit;
}