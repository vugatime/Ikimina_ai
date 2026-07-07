<?php
require_once __DIR__ . '/config/session.php';
session_unset();
session_destroy();
header('Location: index.php?logout=1');
exit;