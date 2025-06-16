<?php
session_start();

// Database configuration
require_once 'database.php';

// Application settings
define('UPLOAD_DIR', 'uploads/resumes/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf']);

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatSalary($min, $max) {
    if ($min && $max) {
        return 'KSH ' . number_format($min) . ' - KSH ' . number_format($max);
    } elseif ($min) {
        return 'KSH ' . number_format($min) . '+';
    }
    return 'Competitive';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}

// Email Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'lawrencesumukwo203@gmail.com');
define('SMTP_PASSWORD', 'nmkm nzqd pebe lrew');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'lawrencesumukwo203@gmail.com');
define('SMTP_FROM_NAME', 'Geonet Technologies Limited');
define('SMTP_REPLY_TO', 'collinskipyego2019@gmail.com');
?>
