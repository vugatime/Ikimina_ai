<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'] ?? 'member';
$current_user_name = $_SESSION['user_name'] ?? 'Robert';