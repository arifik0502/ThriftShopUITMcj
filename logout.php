<?php
/*this file sheet is for php logout
    hadif hashim*/

require_once 'config.php';

// Clear remember token from database
if (isLoggedIn()) {
    $user_id = getUserId();
    $sql = "UPDATE users SET remember_token = NULL WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    
    logSecurityEvent("User logged out", "User ID: $user_id");
}

// Clear remember cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, "/");
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
redirect('login.php');
?>
