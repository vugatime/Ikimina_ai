<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/language_switcher.php';
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }

$tab = $_GET['tab'] ?? 'login';

if ($tab === 'register') {
    include __DIR__ . '/register.php';
} else {
    include __DIR__ . '/login.php';
}