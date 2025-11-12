<?php
/*this file sheet is for php configuration
    hadif hashim*/

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'thriftshop_db');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site Configuration
define('SITE_NAME', 'Thrift Marketplace');
define('SITE_URL', 'http://localhost/thrift/');

// Email Configuration (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@thrift.com');
define('SMTP_FROM_NAME', 'Thrift Marketplace');

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUsername() {
    return $_SESSION['username'] ?? null;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}
?>