<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$timeout = 3600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset(); 
    session_destroy();
}
$_SESSION['last_activity'] = time();