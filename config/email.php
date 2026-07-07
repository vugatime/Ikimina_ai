<?php
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'agasobanuyenews@gmail.com');
define('MAIL_FROM_NAME', 'IkiminaAI');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/ikimina-ai');

function sendEmail($to, $subject, $body) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    return mail($to, $subject, $body, $headers);
}

function queueAndSendEmail($pdo, $userId, $to, $subject, $body) {
    $stmt = $pdo->prepare("INSERT INTO email_queue (user_id, recipient_email, subject, body, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $to, $subject, $body]);
    $queueId = $pdo->lastInsertId();
    $sent = sendEmail($to, $subject, $body);
    $status = $sent ? 'sent' : 'failed';
    $stmt = $pdo->prepare("UPDATE email_queue SET status = ?, sent_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $queueId]);
    return $sent;
}

function createNotification($pdo, $userId, $title, $message, $type = 'info') {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, $title, $message, $type]);
}

function emailTemplate($title, $subtitle, $content, $actionLink = '', $actionText = '') {
    $actionButton = '';
    if ($actionLink && $actionText) {
        $actionButton = "
            <tr><td style='text-align:center;padding:25px 0 15px 0;'>
                <a href='{$actionLink}' style='background:#0F766E;color:#ffffff;padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;display:inline-block;letter-spacing:0.3px;'>$actionText</a>
            </td></tr>
        ";
    }
    
    return "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:Inter,system-ui,-apple-system,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f1f5f9;padding:40px 20px;'>
<tr><td align='center'>
<table width='100%' cellpadding='0' cellspacing='0' style='max-width:580px;'>
    
    <tr><td style='background:#0F172A;padding:30px 30px 25px 30px;text-align:center;border-radius:16px 16px 0 0;'>
        <table cellpadding='0' cellspacing='0' align='center'>
            <tr>
                <td style='width:44px;height:44px;background:#0F766E;border-radius:12px;text-align:center;vertical-align:middle;'>
                    <span style='color:#ffffff;font-size:22px;font-weight:800;line-height:44px;'>I</span>
                </td>
                <td style='padding-left:12px;'>
                    <span style='color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-0.3px;'>Ikimina<span style='color:#14b8a6;'>AI</span></span>
                </td>
            </tr>
        </table>
    </td></tr>
    
    <tr><td style='background:#ffffff;padding:35px 30px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;'>
        <h2 style='color:#0F172A;font-size:20px;font-weight:700;margin:0 0 5px 0;'>{$title}</h2>
        <p style='color:#94a3b8;font-size:13px;margin:0 0 20px 0;'>{$subtitle}</p>
        <div style='color:#475569;font-size:14px;line-height:1.8;'>
            {$content}
        </div>
        {$actionButton}
    </td></tr>
    
    <tr><td style='background:#f8fafc;padding:20px 30px;text-align:center;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 16px 16px;'>
        <p style='color:#94a3b8;font-size:11px;margin:0 0 4px 0;line-height:1.6;'>IkiminaAI — Smart Community Finance Platform</p>
        <p style='color:#94a3b8;font-size:11px;margin:0;'>Kigali, Rwanda | agasobanuyenews@gmail.com | 0795064502</p>
        <p style='color:#cbd5e1;font-size:10px;margin:10px 0 0 0;'>This is an automated message from IkiminaAI. Please do not reply to this email.</p>
    </td></tr>
    
</table>
</td></tr>
</table>
</body>
</html>";
}
