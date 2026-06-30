<?php
// Detect language from session or default to English
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'rw'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$lang = $_SESSION['lang'];
require_once __DIR__ . '/../lang/' . $lang . '.php';

// Helper function
function __($key) {
    global $t;
    return $t[$key] ?? $key;
}
?>