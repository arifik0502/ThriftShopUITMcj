<?php
/*this file sheet is for php login
    hadif hashim*/

require_once 'config.php';

// If already logged in, redirect to main page
if (isLoggedIn()) {
    redirect('main.php');
}

$error = '';
$success = '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
        logSecurityEvent("CSRF token validation failed", $_SERVER['REMOTE_ADDR']);
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? 1 : 0;
        
        if (!checkRateLimit('login', $_SERVER['REMOTE_ADDR'], 5, 300)) {
            $error = "Too many login attempts. Please try again in 5 minutes.";
            logSecurityEvent("Rate limit exceeded for login", $_SERVER['REMOTE_ADDR']);
        } elseif (empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } else {
            $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                    $error = "Account temporarily locked. Please try again later.";
                    logSecurityEvent("Login attempt on locked account", $username);
                } elseif (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    
                    $reset_sql = "UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?";
                    $reset_stmt = mysqli_prepare($conn, $reset_sql);
                    mysqli_stmt_bind_param($reset_stmt, "i", $user['id']);
                    mysqli_stmt_execute($reset_stmt);
                    
                    if ($remember) {
                        $token = generateRandomString(64);
                        $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "si", $token, $user['id']);
                        mysqli_stmt_execute($stmt);
                        setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
                    }
                    
                    session_regenerate_id(true);
                    logSecurityEvent("Successful login", $username);
                    redirect('main.php');
                } else {
                    $attempts = $user['login_attempts'] + 1;
                    $locked_until = NULL;
                    
                    if ($attempts >= 5) {
                        $locked_until = date('Y-m-d H:i:s', time() + 900);
                        $error = "Too many failed attempts. Account locked for 15 minutes.";
                        logSecurityEvent("Account locked due to failed attempts", $username);
                    } else {
                        $error = "Invalid username or password";
                    }
                    
                    $update_sql = "UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "isi", $attempts, $locked_until, $user['id']);
                    mysqli_stmt_execute($update_stmt);
                    logSecurityEvent("Failed login attempt", $username);
                }
            } else {
                $error = "Invalid username or password";
                logSecurityEvent("Login attempt for non-existent user", $username);
            }
        }
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
        logSecurityEvent("CSRF token validation failed on registration", $_SERVER['REMOTE_ADDR']);
    } else {
        $username = sanitizeInput($_POST['reg_username']);
        $email = sanitizeInput($_POST['reg_email']);
        $password = $_POST['reg_password'];
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = "All fields are required";
        } elseif (!validateEmail($email)) {
            $error = "Invalid email format";
        } else {
            $passwordCheck = validatePassword($password);
            if (!$passwordCheck['valid']) {
                $error = $passwordCheck['message'];
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    $error = "Username or email already exists";
                } else {
                    $sql = "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Registration successful! Please login.";
                        logSecurityEvent("New user registered", $username);
                    } else {
                        $error = "Registration failed. Please try again.";
                        logSecurityEvent("Registration failed", mysqli_error($conn));
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketPlace 2st | Login</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="" alt="">
            <h2>login</h2>
        </div>

        <?php if ($error): ?>
            <div style="color: #ff6b6b; text-align: center; margin-bottom: 15px; padding: 10px; background: rgba(255, 107, 107, 0.1); border-radius: 5px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="color: #51cf66; text-align: center; margin-bottom: 15px; padding: 10px; background: rgba(81, 207, 102, 0.1); border-radius: 5px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" id="loginForm" method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="input">
                <input type="text" id="myUsername" name="username" placeholder="Username or Email" required>
                <div class="errMsg" id="myUsernameErr">Please enter valid Username</div>
            </div>

            <div class="input">
                <input type="password" id="myPassword" name="password" placeholder="Password" required>
                <div class="errMsg" id="myPasswordErr">Your Password is incorrect</div>
            </div>

            <div class="inputOpt">
                <div class="rememberMe">
                    <input type="checkbox" id="myRemember" name="remember">
                    <label for="myRemember">Remember me</label>
                </div>
                <div class="fgtPassword">
                    <a href="forgot-password.php">Forgot password?</a>
                </div>
            </div>

            <button type="submit" name="login" class="loginButt">Login</button>
            
            <div class="register-link">New here? <a href="#" onclick="showRegisterModal(); return false;">Create an Account now</a></div>
        </form>
    </div>

    <div id="registerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: rgba(26, 26, 46, 0.9); padding: 30px; border-radius: 20px; max-width: 400px; width: 90%;">
            <h2 style="color: var(--accent); margin-bottom: 20px;">Create Account</h2>
            <form method="POST" action="" onsubmit="return validateRegistration()">
                <?php echo csrfField(); ?>
                <input type="text" name="reg_username" id="reg_username" placeholder="Username" required style="width: 100%; padding: 12px; margin-bottom: 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white;">
                <input type="email" name="reg_email" id="reg_email" placeholder="Email" required style="width: 100%; padding: 12px; margin-bottom: 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white;">
                <input type="password" name="reg_password" id="reg_password" placeholder="Password (min 8 chars, 1 uppercase, 1 number)" required style="width: 100%; padding: 12px; margin-bottom: 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white;">
                <div id="password-strength" style="margin-bottom: 15px; color: #fff; font-size: 12px;"></div>
                <button type="submit" name="register" class="loginButt">Register</button>
                <button type="button" onclick="hideRegisterModal()" style="width: 100%; margin-top: 10px; padding: 12px; background: transparent; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; cursor: pointer;">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function showRegisterModal() {
            document.getElementById('registerModal').style.display = 'flex';
        }
        
        function hideRegisterModal() {
            document.getElementById('registerModal').style.display = 'none';
        }
        
        function validateRegistration() {
            const password = document.getElementById('reg_password').value;
            const username = document.getElementById('reg_username').value;
            
            if (username.length < 3) {
                alert('Username must be at least 3 characters long');
                return false;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return false;
            }
            
            if (!/[A-Z]/.test(password)) {
                alert('Password must contain at least one uppercase letter');
                return false;
            }
            
            if (!/[a-z]/.test(password)) {
                alert('Password must contain at least one lowercase letter');
                return false;
            }
            
            if (!/[0-9]/.test(password)) {
                alert('Password must contain at least one number');
                return false;
            }
            
            return true;
        }
        
        document.getElementById('reg_password')?.addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('password-strength');
            let score = 0;
            
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            if (score < 3) {
                strength.textContent = 'Weak password';
                strength.style.color = '#ff6b6b';
            } else if (score < 4) {
                strength.textContent = 'Medium strength';
                strength.style.color = '#ffd43b';
            } else {
                strength.textContent = 'Strong password';
                strength.style.color = '#51cf66';
            }
        });
    </script>
</body>
</html>
