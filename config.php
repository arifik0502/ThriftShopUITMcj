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
    error_log("Database connection failed: " . mysqli_connect_error());
    die("We're experiencing technical difficulties. Please try again later.");
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");

// Session Security Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS in production

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Site Configuration
define('SITE_NAME', 'Thrift Marketplace');
define('SITE_URL', 'http://localhost/thrift/');

// Email Configuration (PHPMailer) - Currently disabled
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@thrift.com');
define('SMTP_FROM_NAME', 'Thrift Marketplace');

// Security Functions

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF token input field
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Helper Functions

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 */
function getUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Redirect to another page
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Sanitize user input
 */
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return mysqli_real_escape_string($conn, $data);
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number'];
    }
    return ['valid' => true];
}

/**
 * Get product image path with fallback
 */
function getProductImage($imageName) {
    if (empty($imageName)) {
        return 'uploads/default-product.jpg';
    }
    $path = 'uploads/' . $imageName;
    if (!file_exists($path)) {
        return 'uploads/default-product.jpg';
    }
    return $path;
}

/**
 * Get user avatar path with fallback
 */
function getUserAvatar($avatarName) {
    if (empty($avatarName) || $avatarName == 'default-avatar.jpg') {
        return 'uploads/avatars/default-avatar.jpg';
    }
    $path = 'uploads/avatars/' . $avatarName;
    if (!file_exists($path)) {
        return 'uploads/avatars/default-avatar.jpg';
    }
    return $path;
}

/**
 * Validate image upload
 */
function validateImageUpload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF allowed'];
    }
    
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return ['success' => false, 'error' => 'File is not a valid image'];
    }
    
    return ['success' => true];
}

/**
 * Format price for display
 */
function formatPrice($price) {
    return 'RM ' . number_format($price, 2);
}

/**
 * Log security events
 */
function logSecurityEvent($event, $details = '') {
    $log_message = date('Y-m-d H:i:s') . " - " . $event;
    if (!empty($details)) {
        $log_message .= " - " . $details;
    }
    error_log($log_message);
}

/**
 * Rate limiting check
 */
function checkRateLimit($action, $user_identifier, $max_attempts = 5, $time_window = 300) {
    $key = $action . '_' . $user_identifier;
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$key];
    $time_passed = time() - $data['first_attempt'];
    
    if ($time_passed > $time_window) {
        $_SESSION['rate_limit'][$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    if ($data['attempts'] >= $max_attempts) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['attempts']++;
    return true;
}

/**
 * Generate secure random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Initialize CSRF token
generateCSRFToken();
?>