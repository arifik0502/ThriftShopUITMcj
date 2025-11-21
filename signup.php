<?php
/*this file sheet is for php signup
    hadif hashim*/

require_once 'config.php';

// If already logged in, redirect to main page
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// Handle Signup Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']) ? 1 : 0;
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!$terms) {
        $error = "You must agree to the Terms and Conditions";
    } else {
        // Check if username or email already exists
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash the password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", $first_name, $last_name, $username, $email, $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Account created successfully! Please login.";
            } else {
                $error = "Error creating account. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>MarketPlace 2st | Sign Up</title>
        <link rel="stylesheet" href="signup.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>
    <body>
        <div class="signup-container">
            <div class="logo">
                <img src="" alt="">
                <h2>sign up</h2>
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

            <form class="signup-form" id="signupForm" method="POST" action="">
                <div class="input-row">
                    <div class="input">
                        <input type="text" id="myFirstName" name="first_name" placeholder="First Name" required>
                        <div class="errMsg" id="myFirstNameErr">Please enter your first name</div>
                    </div>
                    <div class="input">
                        <input type="text" id="myLastName" name="last_name" placeholder="Last Name" required>
                        <div class="errMsg" id="myLastNameErr">Please enter your last name</div>
                    </div>
                </div>

                <div class="input">
                    <input type="text" id="myUsername" name="username" placeholder="Username" required>
                    <div class="errMsg" id="myUsernameErr">Please enter a valid username</div>
                </div>

                <div class="input">
                    <input type="email" id="myEmail" name="email" placeholder="Email" required>
                    <div class="errMsg" id="myEmailErr">Please enter a valid email</div>
                </div>

                <div class="input">
                    <input type="password" id="myPassword" name="password" placeholder="Password" required>
                    <div class="errMsg" id="myPasswordErr">Password must be at least 6 characters</div>
                </div>

                <div class="input">
                    <input type="password" id="myConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                    <div class="errMsg" id="myConfirmPasswordErr">Passwords must match</div>
                </div>

                <div class="terms">
                    <input type="checkbox" id="myTerms" name="terms">
                    <label for="myTerms">I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a></label>
                </div>

                <button type="submit" name="signup" class="signupButt">Sign Up</button>

                <div class="button-container">
                    <a href="login.php" class="return-btn">
                        <i class="fas fa-arrow-left"></i> Return to Login
                    </a>
                </div>

                <div class="login-link">Already have an account? <a href="login.php">Login now</a></div>
            </form>
        </div>
    </body>
</html>