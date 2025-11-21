<?php
/*this file sheet is for php login with sliding animation
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --white: #e9e9e9;
            --gray: #333;
            --blue: #0367a6;
            --lightblue: #008997;
            --accent: #d4af37;
            --button-radius: 0.7rem;
            --max-width: 850px;
            --max-height: 500px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: grid;
            height: 100vh;
            place-items: center;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .error-message, .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            z-index: 10000;
            animation: slideIn 0.5s;
        }

        .error-message {
            background: #ff6b6b;
            color: white;
        }

        .success-message {
            background: #51cf66;
            color: white;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .form__title {
            font-weight: 400;
            margin: 0 0 1.25rem;
            color: var(--gray);
        }

        .link {
            color: var(--blue);
            font-size: 0.9rem;
            margin: 1rem 0;
            text-decoration: none;
            display: block;
        }

        .link:hover {
            text-decoration: underline;
        }

        .container {
            background-color: var(--white);
            border-radius: var(--button-radius);
            box-shadow: 0 0.9rem 1.7rem rgba(0, 0, 0, 0.25), 0 0.7rem 0.7rem rgba(0, 0, 0, 0.22);
            height: var(--max-height);
            max-width: var(--max-width);
            overflow: hidden;
            position: relative;
            width: 100%;
        }

        .container__form {
            height: 100%;
            position: absolute;
            top: 0;
            transition: all 0.6s ease-in-out;
        }

        .container--signin {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .container.right-panel-active .container--signin {
            transform: translateX(100%);
        }

        .container--signup {
            left: 0;
            opacity: 0;
            width: 50%;
            z-index: 1;
        }

        .container.right-panel-active .container--signup {
            animation: show 0.6s;
            opacity: 1;
            transform: translateX(100%);
            z-index: 5;
        }

        .container__overlay {
            height: 100%;
            left: 50%;
            overflow: hidden;
            position: absolute;
            top: 0;
            transition: transform 0.6s ease-in-out;
            width: 50%;
            z-index: 100;
        }

        .container.right-panel-active .container__overlay {
            transform: translateX(-100%);
        }

        .overlay {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            left: -100%;
            position: relative;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
            width: 200%;
        }

        .container.right-panel-active .overlay {
            transform: translateX(50%);
        }

        .overlay__panel {
            align-items: center;
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: center;
            position: absolute;
            text-align: center;
            top: 0;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
            width: 50%;
            color: white;
            padding: 0 2rem;
        }

        .overlay__panel h1 {
            margin-bottom: 1rem;
        }

        .overlay__panel p {
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .overlay--left {
            transform: translateX(-20%);
        }

        .container.right-panel-active .overlay--left {
            transform: translateX(0);
        }

        .overlay--right {
            right: 0;
            transform: translateX(0);
        }

        .container.right-panel-active .overlay--right {
            transform: translateX(20%);
        }

        .btn {
            background: linear-gradient(90deg, var(--blue) 0%, var(--lightblue) 74%);
            border-radius: 20px;
            border: 1px solid var(--blue);
            color: white;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: bold;
            letter-spacing: 0.1rem;
            padding: 0.9rem 4rem;
            text-transform: uppercase;
            transition: transform 80ms ease-in;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn:active {
            transform: scale(0.95);
        }

        .btn:focus {
            outline: none;
        }

        .form {
            background-color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 3rem;
            height: 100%;
            text-align: center;
        }

        .input {
            background-color: #fff;
            border: 2px solid #e0e0e0;
            padding: 0.9rem;
            margin: 0.5rem 0;
            width: 100%;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .input:focus {
            outline: none;
            border-color: var(--blue);
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            width: 100%;
        }

        .checkbox-container input {
            width: auto;
        }

        .home-link {
            position: fixed;
            top: 20px;
            left: 20px;
            background: white;
            color: var(--blue);
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .home-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        @keyframes show {
            0%, 49.99% {
                opacity: 0;
                z-index: 1;
            }
            50%, 100% {
                opacity: 1;
                z-index: 5;
            }
        }

        @media (max-width: 768px) {
            .container {
                max-width: 90%;
                height: auto;
                min-height: 500px;
            }
            
            .overlay__panel {
                padding: 1rem;
            }
            
            .form {
                padding: 0 2rem;
            }
        }
    </style>
</head>
<body>
    <a href="main.php" class="home-link">
        <i class="fas fa-home"></i> Back to Home
    </a>

    <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="container" id="container">
        <!-- Sign Up -->
        <div class="container__form container--signup">
            <form action="" method="POST" class="form" id="form1">
                <?php echo csrfField(); ?>
                <h2 class="form__title">Create Account</h2>
                <input type="text" name="reg_username" placeholder="Username" class="input" required />
                <input type="email" name="reg_email" placeholder="Email" class="input" required />
                <input type="password" name="reg_password" placeholder="Password" class="input" required />
                <small style="color: #666; margin: 0.5rem 0;">Min 8 chars, 1 uppercase, 1 number</small>
                <button type="submit" name="register" class="btn" style="margin-top: 1rem;">Sign Up</button>
            </form>
        </div>
    
        <!-- Sign In -->
        <div class="container__form container--signin">
            <form action="" method="POST" class="form" id="form2">
                <?php echo csrfField(); ?>
                <h2 class="form__title">Sign In</h2>
                <input type="text" name="username" placeholder="Username or Email" class="input" required />
                <input type="password" name="password" placeholder="Password" class="input" required />
                <div class="checkbox-container">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="forgot-password.php" class="link">Forgot your password?</a>
                <button type="submit" name="login" class="btn">Sign In</button>
            </form>
        </div>
    
        <!-- Overlay -->
        <div class="container__overlay">
            <div class="overlay">
                <div class="overlay__panel overlay--left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button class="btn" id="signIn">Sign In</button>
                </div>
                <div class="overlay__panel overlay--right">
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start journey with us</p>
                    <button class="btn" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const signInBtn = document.getElementById("signIn");
        const signUpBtn = document.getElementById("signUp");
        const container = document.getElementById("container");

        signInBtn.addEventListener("click", () => {
            container.classList.remove("right-panel-active");
        });

        signUpBtn.addEventListener("click", () => {
            container.classList.add("right-panel-active");
        });

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.error-message, .success-message');
            messages.forEach(msg => {
                msg.style.animation = 'slideIn 0.5s reverse';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>