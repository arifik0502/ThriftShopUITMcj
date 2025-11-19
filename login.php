<?php
/*this file sheet is for php login
    hadif hashim*/

require_once 'config.php';

// If already logged in, redirect to main page
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? 1 : 0;
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // Handle remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $token, $user['id']);
                    mysqli_stmt_execute($stmt);
                    
                    setcookie('remember_token', $token, time() + (86400 * 30), "/");
                }
                
                redirect('main.php');
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}

// Handle Registration (simplified)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = sanitizeInput($_POST['reg_username']);
    $email = sanitizeInput($_POST['reg_email']);
    $password = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Registration successful! Please login.";
    } else {
        $error = "Username or email already exists";
    }
}
?>
<!DOCType html>
<html>
    <head>
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
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div style="color: #51cf66; text-align: center; margin-bottom: 15px; padding: 10px; background: rgba(81, 207, 102, 0.1); border-radius: 5px;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form class="login-form" id="loginForm" method="POST" action="">
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
                <div class="divider">OR CONNECT WITH</div>
            
                <div class="socLogin">
                    <a href="#" class="social-btn" title="Login with Google">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-btn" title="Login with Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-btn" title="Login with Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </div>

                <div class="register-link">New here? <a href="#" onclick="showRegisterModal(); return false;">Create an Account now</a></div>
            </form>
        </div>

        <!-- Simple Registration Modal (you can enhance this) -->
        <div id="registerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: rgba(26, 26, 46, 0.9); padding: 30px; border-radius: 20px; max-width: 400px; width: 90%;">
                <h2 style="color: var(--accent); margin-bottom: 20px;">Create Account</h2>
                <form method="POST" action="">
                    <input type="text" name="reg_username" placeholder="Username" required style="width: 100%; padding: 12px; margin-bottom: 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white;">
                    <input type="email" name="reg_email" placeholder="Email" required style="width: 100%; padding: 12px; margin-bottom: 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white;">
                    <input type="password" name="reg_password" placeholder="Password" required style="width: 100%; padding: 12px; margin-bottom: 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white;">
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
        </script>
    </body>
</html>
