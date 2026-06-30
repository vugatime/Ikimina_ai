<?php
/**
 * IkiminaAI — AI Scoring Engine
 * Analyzes member behavior and generates trust scores, risk levels, and recommendations
 */

function calculateMemberScores($pdo, $membershipId, $groupId) {
    // Get member's savings data
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_savings_count,
            COALESCE(SUM(amount), 0) as total_saved,
            MAX(payment_date) as last_payment,
            MIN(payment_date) as first_payment,
            DATEDIFF(NOW(), MIN(payment_date)) as days_since_first
        FROM savings 
        WHERE member_id = ? AND group_id = ?
    ");
    $stmt->execute([$membershipId, $groupId]);
    $savingsData = $stmt->fetch();

    // Get group rules
    $stmt = $pdo->prepare("SELECT contribution_amount, contribution_frequency FROM `groups` WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupRules = $stmt->fetch();

    $expectedPerPeriod = $groupRules['contribution_amount'];
    $frequency = $groupRules['contribution_frequency'];
    
    if ($savingsData['days_since_first'] > 0) {
        switch ($frequency) {
            case 'weekly': $periods = ceil($savingsData['days_since_first'] / 7); break;
            case 'biweekly': $periods = ceil($savingsData['days_since_first'] / 14); break;
            case 'monthly': $periods = ceil($savingsData['days_since_first'] / 30); break;
            default: $periods = max(1, $savingsData['total_savings_count']);
        }
    } else {
        $periods = 1;
    }
    
    $expectedSavings = $expectedPerPeriod * $periods;
    $actualSavings = $savingsData['total_saved'];
    
    // Savings Score (0-100)
    if ($expectedSavings > 0) {
        $savingsScore = min(100, round(($actualSavings / $expectedSavings) * 100));
    } else {
        $savingsScore = $actualSavings > 0 ? 100 : 0;
    }
    if ($savingsData['total_savings_count'] >= $periods * 0.8) {
        $savingsScore = min(100, $savingsScore + 10);
    }
    
    // Get loan repayment data
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_loans,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_loans,
            SUM(CASE WHEN status = 'defaulted' THEN 1 ELSE 0 END) as defaulted_loans,
            SUM(CASE WHEN status IN ('active', 'approved') THEN 1 ELSE 0 END) as active_loans
        FROM loans 
        WHERE member_id = ? AND group_id = ?
    ");
    $stmt->execute([$membershipId, $groupId]);
    $loanData = $stmt->fetch();

    // Repayment Score (0-100)
    $repaymentScore = 50;
    if ($loanData['total_loans'] > 0) {
        $repaymentScore = 70;
        $completionRate = ($loanData['completed_loans'] / $loanData['total_loans']) * 100;
        $repaymentScore += min(20, $completionRate * 0.2);
        $defaultRate = ($loanData['defaulted_loans'] / $loanData['total_loans']) * 100;
        $repaymentScore -= min(40, $defaultRate * 2);
    }
    $repaymentScore = max(0, min(100, $repaymentScore));
    
    // Get attendance data
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_meetings,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
        FROM attendance 
        WHERE member_id = ?
    ");
    $stmt->execute([$membershipId]);
    $attendanceData = $stmt->fetch();

    // Attendance Score (0-100)
    $attendanceScore = 50;
    if ($attendanceData['total_meetings'] > 0) {
        $presentRate = ($attendanceData['present_count'] / $attendanceData['total_meetings']) * 100;
        $lateRate = ($attendanceData['late_count'] / $attendanceData['total_meetings']) * 100;
        $absentRate = ($attendanceData['absent_count'] / $attendanceData['total_meetings']) * 100;
        $attendanceScore = round($presentRate + ($lateRate * 0.5) - ($absentRate * 0.5));
    }
    $attendanceScore = max(0, min(100, $attendanceScore));
    
    // Overall Trust Score
    $trustScore = round(($savingsScore * 0.45) + ($repaymentScore * 0.35) + ($attendanceScore * 0.20));
    
    // Loan Eligibility
    $eligibilityScore = round(($savingsScore * 0.5) + ($repaymentScore * 0.3) + ($attendanceScore * 0.2));
    if ($loanData['active_loans'] > 0) $eligibilityScore -= ($loanData['active_loans'] * 10);
    $eligibilityScore = max(0, min(100, $eligibilityScore));
    
    // Default Risk
    $defaultRisk = max(0, min(100, round(100 - $trustScore * 0.8)));
    
    // Risk Label
    if ($trustScore >= 70) $riskLabel = 'LOW RISK';
    elseif ($trustScore >= 40) $riskLabel = 'MEDIUM RISK';
    else $riskLabel = 'HIGH RISK';
    
    // Health Label
    if ($trustScore >= 80) $healthLabel = 'EXCELLENT';
    elseif ($trustScore >= 60) $healthLabel = 'GOOD';
    elseif ($trustScore >= 40) $healthLabel = 'FAIR';
    else $healthLabel = 'POOR';
    
    // Recommendations
    $recommendations = [];
    if ($savingsScore < 50) $recommendations[] = "Increase savings consistency.";
    if ($repaymentScore < 50) $recommendations[] = "Improve loan repayment history.";
    if ($attendanceScore < 50) $recommendations[] = "Attend more meetings.";
    if ($eligibilityScore >= 80) $recommendations[] = "Eligible for larger loans.";
    if ($defaultRisk > 50) $recommendations[] = "High default risk. Consider smaller loans.";
    
    return [
        'trust_score' => $trustScore,
        'savings_score' => $savingsScore,
        'repayment_score' => $repaymentScore,
        'attendance_score' => $attendanceScore,
        'loan_eligibility' => $eligibilityScore,
        'default_risk' => $defaultRisk,
        'risk_label' => $riskLabel,
        'health_label' => $healthLabel,
        'recommendation' => !empty($recommendations) ? implode(" ", $recommendations) : "Member is performing well.",
        'factors' => [
            'savings' => ['expected' => $expectedSavings, 'actual' => $actualSavings],
            'loans' => $loanData,
            'attendance' => $attendanceData
        ]
    ];
}

function calculateGroupHealth($pdo, $groupId) {
    $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND deleted_at IS NULL");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($members)) return ['score' => 0, 'label' => 'No members'];
    
    $totalTrust = 0;
    foreach ($members as $memberId) {
        $scores = calculateMemberScores($pdo, $memberId, $groupId);
        $totalTrust += $scores['trust_score'];
    }
    
    $avgTrust = round($totalTrust / count($members));
    
    if ($avgTrust >= 75) $healthLabel = 'Excellent';
    elseif ($avgTrust >= 55) $healthLabel = 'Good';
    elseif ($avgTrust >= 35) $healthLabel = 'Fair';
    else $healthLabel = 'Needs Attention';
    
    return ['score' => $avgTrust, 'label' => $healthLabel];
}

function saveAIScores($pdo, $membershipId, $groupId, $scores) {
    // Delete old scores
    $stmt = $pdo->prepare("DELETE FROM ai_scores WHERE member_id = ? AND group_id = ?");
    $stmt->execute([$membershipId, $groupId]);
    
    // Insert trust score
    $stmt = $pdo->prepare("INSERT INTO ai_scores (member_id, group_id, score_type, score_value, score_label, factors, generated_at) VALUES (?, ?, 'trust', ?, ?, ?, NOW())");
    $stmt->execute([$membershipId, $groupId, $scores['trust_score'], $scores['health_label'], json_encode($scores['factors'])]);
    
    // Insert eligibility score
    $stmt = $pdo->prepare("INSERT INTO ai_scores (member_id, group_id, score_type, score_value, score_label, factors, generated_at) VALUES (?, ?, 'eligibility', ?, ?, ?, NOW())");
    $stmt->execute([$membershipId, $groupId, $scores['loan_eligibility'], $scores['risk_label'], json_encode($scores['factors'])]);
    
    // Insert default risk
    $stmt = $pdo->prepare("INSERT INTO ai_scores (member_id, group_id, score_type, score_value, score_label, factors, generated_at) VALUES (?, ?, 'default_risk', ?, ?, ?, NOW())");
    $stmt->execute([$membershipId, $groupId, $scores['default_risk'], $scores['risk_label'], json_encode($scores['factors'])]);
}

function getMemberAIScores($pdo, $membershipId, $groupId) {
    $stmt = $pdo->prepare("SELECT * FROM ai_scores WHERE member_id = ? AND group_id = ? AND score_type = 'trust' ORDER BY generated_at DESC LIMIT 1");
    $stmt->execute([$membershipId, $groupId]);
    $trust = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM ai_scores WHERE member_id = ? AND group_id = ? AND score_type = 'eligibility' ORDER BY generated_at DESC LIMIT 1");
    $stmt->execute([$membershipId, $groupId]);
    $eligibility = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM ai_scores WHERE member_id = ? AND group_id = ? AND score_type = 'default_risk' ORDER BY generated_at DESC LIMIT 1");
    $stmt->execute([$membershipId, $groupId]);
    $risk = $stmt->fetch();
    
    if (!$trust) return null;
    
    return [
        'trust_score' => $trust['score_value'],
        'loan_eligibility' => $eligibility ? $eligibility['score_value'] : 0,
        'default_risk' => $risk ? $risk['score_value'] : 0,
        'risk_label' => $trust['score_label'] === 'EXCELLENT' || $trust['score_label'] === 'GOOD' ? 'LOW RISK' : ($trust['score_label'] === 'FAIR' ? 'MEDIUM RISK' : 'HIGH RISK'),
        'recommendation' => $trust['factors'] ? json_decode($trust['factors'], true)['recommendation'] ?? '' : ''
    ];
}

function getGroupAIInsights($pdo, $groupId) {
    // Regenerate scores for all members
    $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND deleted_at IS NULL");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($members as $memberId) {
        $scores = calculateMemberScores($pdo, $memberId, $groupId);
        saveAIScores($pdo, $memberId, $groupId, $scores);
    }
    
    $health = calculateGroupHealth($pdo, $groupId);
    
    // Get all trust scores
    $stmt = $pdo->prepare("
        SELECT ai.score_value, ai.score_label, gm.member_id, u.fullname 
        FROM ai_scores ai 
        JOIN group_members gm ON ai.member_id = gm.id 
        JOIN users u ON gm.user_id = u.id 
        WHERE ai.group_id = ? AND ai.score_type = 'trust'
        AND ai.generated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY ai.score_value ASC
    ");
    $stmt->execute([$groupId]);
    $allScores = $stmt->fetchAll();
    
    $atRisk = array_slice($allScores, 0, 5);
    $topPerformers = array_slice(array_reverse($allScores), 0, 5);
    
    return [
        'health' => $health,
        'at_risk' => $atRisk,
        'top_performers' => $topPerformers
    ];
}