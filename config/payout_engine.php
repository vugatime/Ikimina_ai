<?php
/**
 * IkiminaAI — Kuzenguruka Payout Engine
 * AI-driven rotating payout system for community savings groups
 */

/**
 * Calculate who should receive the payout this cycle
 * Based on: savings consistency, time since last received, loan repayment, attendance
 */
function selectPayoutRecipients($pdo, $groupId, $cycleAmount, $recipientsCount = 1) {
    // Get all active members with their stats
    $stmt = $pdo->prepare("
        SELECT 
            gm.id as membership_id,
            gm.member_id,
            u.fullname,
            COALESCE(s.total_saved, 0) as total_savings,
            COALESCE(s.savings_count, 0) as savings_count,
            COALESCE(l.active_loans, 0) as active_loans,
            COALESCE(l.defaulted_loans, 0) as defaulted_loans,
            COALESCE(a.attendance_rate, 0) as attendance_rate,
            COALESCE(pr.times_received, 0) as times_received,
            COALESCE(pr.last_received, '2000-01-01') as last_received,
            DATEDIFF(NOW(), COALESCE(pr.last_received, '2000-01-01')) as days_since_received
        FROM group_members gm
        JOIN users u ON gm.user_id = u.id
        LEFT JOIN (
            SELECT member_id, SUM(amount) as total_saved, COUNT(*) as savings_count 
            FROM savings GROUP BY member_id
        ) s ON gm.id = s.member_id
        LEFT JOIN (
            SELECT member_id, 
                SUM(CASE WHEN status IN ('active','approved') THEN 1 ELSE 0 END) as active_loans,
                SUM(CASE WHEN status = 'defaulted' THEN 1 ELSE 0 END) as defaulted_loans
            FROM loans GROUP BY member_id
        ) l ON gm.id = l.member_id
        LEFT JOIN (
            SELECT member_id,
                COUNT(*) as total_meetings,
                SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as attendance_rate
            FROM attendance GROUP BY member_id
        ) a ON gm.id = a.member_id
        LEFT JOIN (
            SELECT member_id, COUNT(*) as times_received, MAX(received_at) as last_received
            FROM payout_recipients GROUP BY member_id
        ) pr ON gm.id = pr.member_id
        WHERE gm.group_id = ? AND gm.deleted_at IS NULL
    ");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll();
    
    if (empty($members)) return [];
    
    // Calculate AI score for each member
    $scored = [];
    foreach ($members as $m) {
        $score = 50; // Base score
        
        // Bonus for savings consistency (up to +20)
        if ($m['total_savings'] > 0) {
            $score += min(20, ($m['savings_count'] / max(1, $m['savings_count'])) * 20);
        }
        
        // Bonus for time since last received (up to +25)
        if ($m['days_since_received'] > 30) $score += 10;
        if ($m['days_since_received'] > 60) $score += 15;
        if ($m['days_since_received'] > 90) $score += 25;
        
        // Penalty for active loans (up to -20)
        $score -= min(20, $m['active_loans'] * 10);
        
        // Penalty for defaults (up to -30)
        $score -= min(30, $m['defaulted_loans'] * 15);
        
        // Bonus for attendance (up to +10)
        $score += min(10, $m['attendance_rate'] * 0.1);
        
        // Penalty for having received many times (up to -15)
        $score -= min(15, $m['times_received'] * 5);
        
        // First-time recipients get priority
        if ($m['times_received'] == 0) $score += 15;
        
        $m['ai_score'] = max(0, min(100, $score));
        $scored[] = $m;
    }
    
    // Sort by AI score (highest first)
    usort($scored, function($a, $b) {
        return $b['ai_score'] - $a['ai_score'];
    });
    
    // Select top N recipients
    $selected = array_slice($scored, 0, $recipientsCount);
    
    // Calculate amount per recipient
    $amountPerPerson = $cycleAmount / count($selected);
    
    // Generate reasons
    foreach ($selected as &$s) {
        $reasons = [];
        if ($s['times_received'] == 0) $reasons[] = "First-time recipient - priority";
        if ($s['days_since_received'] > 60) $reasons[] = "Not received in " . $s['days_since_received'] . " days";
        if ($s['total_savings'] > 0) $reasons[] = "Saved consistently: " . number_format($s['total_savings']) . " RWF";
        if ($s['attendance_rate'] >= 80) $reasons[] = "Excellent attendance: " . round($s['attendance_rate']) . "%";
        if ($s['active_loans'] > 0) $reasons[] = "Note: Has " . $s['active_loans'] . " active loan(s)";
        $s['amount'] = round($amountPerPerson);
        $s['reason'] = implode(". ", $reasons);
    }
    
    return $selected;
}

/**
 * Create a new payout cycle
 */
function createPayoutCycle($pdo, $groupId, $cycleName, $totalAmount, $recipientsCount = 1) {
    $stmt = $pdo->prepare("INSERT INTO payout_cycles (group_id, cycle_name, total_amount, recipients_count, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
    $stmt->execute([$groupId, $cycleName, $totalAmount, $recipientsCount]);
    $cycleId = $pdo->lastInsertId();
    
    // Select recipients using AI
    $recipients = selectPayoutRecipients($pdo, $groupId, $totalAmount, $recipientsCount);
    
    // Save recipients
    foreach ($recipients as $r) {
        $stmt = $pdo->prepare("INSERT INTO payout_recipients (cycle_id, member_id, amount_received, ai_score, selection_reason, received_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$cycleId, $r['membership_id'], $r['amount'], $r['ai_score'], $r['reason']]);
    }
    
    return [
        'cycle_id' => $cycleId,
        'recipients' => $recipients
    ];
}

/**
 * Get payout history for a group
 */
function getPayoutHistory($pdo, $groupId) {
    $stmt = $pdo->prepare("
        SELECT pc.*, 
               COUNT(pr.id) as recipient_count,
               SUM(pr.amount_received) as total_disbursed
        FROM payout_cycles pc
        LEFT JOIN payout_recipients pr ON pc.id = pr.cycle_id
        WHERE pc.group_id = ?
        GROUP BY pc.id
        ORDER BY pc.created_at DESC
    ");
    $stmt->execute([$groupId]);
    $cycles = $stmt->fetchAll();
    
    // Get recipients for each cycle
    foreach ($cycles as &$cycle) {
        $stmt = $pdo->prepare("
            SELECT pr.*, gm.member_id, u.fullname
            FROM payout_recipients pr
            JOIN group_members gm ON pr.member_id = gm.id
            JOIN users u ON gm.user_id = u.id
            WHERE pr.cycle_id = ?
        ");
        $stmt->execute([$cycle['id']]);
        $cycle['recipients'] = $stmt->fetchAll();
    }
    
    return $cycles;
}

/**
 * Get payout stats for a member
 */
function getMemberPayoutStats($pdo, $membershipId) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as times_received,
            COALESCE(SUM(amount_received), 0) as total_received,
            MAX(received_at) as last_received
        FROM payout_recipients
        WHERE member_id = ?
    ");
    $stmt->execute([$membershipId]);
    return $stmt->fetch();
}