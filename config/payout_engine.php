<?php
/**
 * IkiminaAI — Kuzenguruka Payout Engine
 * AI-driven rotating payout system with Generation tracking
 * 
 * Generation = A complete cycle where all eligible members receive their share.
 * When all members have been paid, a new Generation starts automatically.
 */

function isMemberBlocked($pdo, $membershipId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = ? AND status = 'defaulted'");
    $stmt->execute([$membershipId]);
    if ($stmt->fetchColumn() > 0) return ['blocked' => true, 'reason' => 'Has an unpaid failed loan'];
    return ['blocked' => false, 'reason' => ''];
}

function getMemberEligibleAmount($pdo, $membershipId, $groupId) {
    // Total contributed (positive savings)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE member_id = ? AND group_id = ? AND amount > 0");
    $stmt->execute([$membershipId, $groupId]);
    $totalSaved = $stmt->fetchColumn();
    
    // Total received from ALL generations (never resets)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_received), 0) FROM payout_recipients WHERE member_id = ?");
    $stmt->execute([$membershipId]);
    $totalReceived = $stmt->fetchColumn();
    
    // Outstanding active loans
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(l.total_repayable - COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0)), 0)
        FROM loans l WHERE l.member_id = ? AND l.status IN ('active', 'approved')
    ");
    $stmt->execute([$membershipId]);
    $outstandingLoans = $stmt->fetchColumn();
    
    // Eligible = total contributed - total received (all time) - outstanding loans
    $eligible = $totalSaved - $totalReceived - $outstandingLoans;
    
    return [
        'total_saved' => $totalSaved,
        'total_received' => $totalReceived,
        'outstanding_loans' => $outstandingLoans,
        'eligible' => max(0, $eligible)
    ];
}

function getCurrentGeneration($pdo, $groupId) {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(generation), 0) FROM payout_cycles WHERE group_id = ?");
    $stmt->execute([$groupId]);
    return $stmt->fetchColumn();
}

function selectPayoutRecipients($pdo, $groupId, $totalPayout, $recipientsCount = 1, $generation = null) {
    if (!$generation) $generation = getCurrentGeneration($pdo, $groupId) + 1;
    
    $stmt = $pdo->prepare("
        SELECT gm.id as membership_id, gm.member_id, u.fullname,
            COALESCE(s.total_saved, 0) as total_savings,
            COALESCE(s.savings_count, 0) as savings_count,
            COALESCE(l.active_loans, 0) as active_loans,
            COALESCE(l.failed_loans, 0) as failed_loans,
            COALESCE(a.attendance_rate, 0) as attendance_rate,
            COALESCE(pr.times_received, 0) as times_received,
            DATEDIFF(NOW(), COALESCE(pr.last_received, '2000-01-01')) as days_since_received
        FROM group_members gm JOIN users u ON gm.user_id = u.id
        LEFT JOIN (SELECT member_id, SUM(amount) as total_saved, COUNT(*) as savings_count FROM savings WHERE amount > 0 GROUP BY member_id) s ON gm.id = s.member_id
        LEFT JOIN (SELECT member_id, SUM(CASE WHEN status IN ('active','approved') THEN 1 ELSE 0 END) as active_loans, SUM(CASE WHEN status = 'defaulted' THEN 1 ELSE 0 END) as failed_loans FROM loans GROUP BY member_id) l ON gm.id = l.member_id
        LEFT JOIN (SELECT member_id, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) * 100.0 / GREATEST(COUNT(*), 1) as attendance_rate FROM attendance GROUP BY member_id) a ON gm.id = a.member_id
        LEFT JOIN (SELECT pr.member_id, COUNT(*) as times_received, MAX(pr.received_at) as last_received FROM payout_recipients pr GROUP BY pr.member_id) pr ON gm.id = pr.member_id
        WHERE gm.group_id = ? AND gm.deleted_at IS NULL
    ");
    $stmt->execute([$generation, $groupId]);
    $allMembers = $stmt->fetchAll();
    
    if (empty($allMembers)) return ['error' => 'No members in group'];
    
    $eligible = [];
    $blocked = [];
    
    foreach ($allMembers as $m) {
        $blockStatus = isMemberBlocked($pdo, $m['membership_id']);
        if ($blockStatus['blocked']) { $m['blocked_reason'] = $blockStatus['reason']; $blocked[] = $m; continue; }
        
        $eligibility = getMemberEligibleAmount($pdo, $m['membership_id'], $groupId, $generation);
        if ($eligibility['eligible'] <= 0) { $m['blocked_reason'] = 'Already received full share this generation'; $blocked[] = $m; continue; }
        
        $m['eligible_amount'] = $eligibility['eligible'];
        $score = 50;
        if ($m['total_savings'] > 0 && $m['savings_count'] > 0) $score += min(20, $m['savings_count'] * 2);
        if ($m['days_since_received'] > 30) $score += 10;
        if ($m['days_since_received'] > 60) $score += 15;
        if ($m['days_since_received'] > 90) $score += 25;
        if ($m['times_received'] == 0) $score += 15;
        $score -= min(20, $m['active_loans'] * 10);
        $score -= min(15, $m['times_received'] * 5);
        $score += min(10, $m['attendance_rate'] * 0.1);
        $m['ai_score'] = max(0, min(100, $score));
        $eligible[] = $m;
    }
    
    if (empty($eligible)) return ['error' => 'No eligible members — all have received their share this generation', 'blocked' => $blocked, 'generation_complete' => true];
    
    usort($eligible, function($a, $b) { return $b['ai_score'] - $a['ai_score']; });
    $selected = array_slice($eligible, 0, $recipientsCount);
    $totalEligible = array_sum(array_column($selected, 'eligible_amount'));
    
    foreach ($selected as &$s) {
        $s['amount'] = $totalEligible > 0 ? min($s['eligible_amount'], round(($s['eligible_amount'] / $totalEligible) * $totalPayout)) : round($totalPayout / count($selected));
        $reasons = [];
        if ($s['times_received'] == 0) $reasons[] = "First time this generation — priority";
        if ($s['days_since_received'] > 60) $reasons[] = "Not received in " . $s['days_since_received'] . " days";
        if ($s['total_savings'] > 0) $reasons[] = "Saved " . number_format($s['total_savings']) . " RWF";
        if ($s['attendance_rate'] >= 80) $reasons[] = "Good attendance: " . round($s['attendance_rate']) . "%";
        $s['reason'] = implode(". ", $reasons);
    }
    
    return ['selected' => $selected, 'blocked' => $blocked, 'generation' => $generation];
}

function createPayoutCycle($pdo, $groupId, $cycleName, $totalAmount, $recipientsCount = 1, $createdBy = null) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE group_id = ?");
    $stmt->execute([$groupId]);
    $availableSavings = $stmt->fetchColumn();
    if ($totalAmount > $availableSavings) return ['error' => 'Not enough group savings. Available: ' . number_format($availableSavings) . ' RWF'];
    
    $generation = getCurrentGeneration($pdo, $groupId) + 1;
    $result = selectPayoutRecipients($pdo, $groupId, $totalAmount, $recipientsCount, $generation);
    
    if (isset($result['error'])) return $result;
    if (empty($result['selected'])) return ['error' => 'No eligible members'];
    
    $stmt = $pdo->prepare("INSERT INTO payout_cycles (group_id, cycle_name, total_amount, recipients_count, generation, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
    $stmt->execute([$groupId, $cycleName, $totalAmount, $recipientsCount, $generation]);
    $cycleId = $pdo->lastInsertId();
    
    foreach ($result['selected'] as $r) {
        $stmt = $pdo->prepare("INSERT INTO payout_recipients (cycle_id, member_id, amount_received, ai_score, selection_reason, received_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$cycleId, $r['membership_id'], $r['amount'], $r['ai_score'], $r['reason']]);
        
        $stmt = $pdo->prepare("INSERT INTO savings (member_id, group_id, amount, savings_type, payment_date, recorded_by, notes, created_at) VALUES (?, ?, ?, 'payout', NOW(), ?, ?, NOW())");
        $stmt->execute([$r['membership_id'], $groupId, -$r['amount'], $createdBy, 'Generation ' . $generation . ': ' . $cycleName]);
        
        $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE id = ?");
        $stmt->execute([$r['membership_id']]);
        $userId = $stmt->fetchColumn();
        if ($userId) {
            $msg = 'You received ' . number_format($r['amount']) . ' RWF in Generation ' . $generation;
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, 'Kuzenguruka Payout!', ?, 'success', NOW())");
            $stmt->execute([$userId, $msg]);
        }
    }
    
    return ['success' => true, 'cycle_id' => $cycleId, 'generation' => $generation, 'recipients' => $result['selected'], 'blocked' => $result['blocked'] ?? []];
}

function getPayoutHistory($pdo, $groupId) {
    $stmt = $pdo->prepare("SELECT pc.*, COUNT(pr.id) as recipient_count, SUM(pr.amount_received) as total_disbursed FROM payout_cycles pc LEFT JOIN payout_recipients pr ON pc.id = pr.cycle_id WHERE pc.group_id = ? GROUP BY pc.id ORDER BY pc.generation DESC, pc.created_at DESC");
    $stmt->execute([$groupId]);
    $cycles = $stmt->fetchAll();
    foreach ($cycles as &$cycle) {
        $stmt = $pdo->prepare("SELECT pr.*, gm.member_id, u.fullname FROM payout_recipients pr JOIN group_members gm ON pr.member_id = gm.id JOIN users u ON gm.user_id = u.id WHERE pr.cycle_id = ?");
        $stmt->execute([$cycle['id']]);
        $cycle['recipients'] = $stmt->fetchAll();
    }
    return $cycles;
}

function getMemberPayoutStats($pdo, $membershipId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as times_received, COALESCE(SUM(amount_received), 0) as total_received, MAX(received_at) as last_received FROM payout_recipients WHERE member_id = ?");
    $stmt->execute([$membershipId]);
    return $stmt->fetch();
}