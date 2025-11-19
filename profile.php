<?php
/*this file sheet is for php profile page
    hadif hashim*/

require_once 'config.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = getUserId();
$success = '';
$error = '';

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token";
    } else {
        $username = sanitizeInput($_POST['username']);
        $phone = sanitizeInput($_POST['phone']);
        $bio = sanitizeInput($_POST['bio']);
        $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
        $chat_notif = isset($_POST['chat_notifications']) ? 1 : 0;
        $push_notif = isset($_POST['push_notifications']) ? 1 : 0;
        
        // Validate phone number
        if (!empty($phone) && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
            $error = "Invalid phone number format";
        } else {
            $update_sql = "UPDATE users SET username = ?, phone = ?, bio = ?, 
                           email_notifications = ?, chat_notifications = ?, push_notifications = ? 
                           WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "sssiiii", $username, $phone, $bio, $email_notif, $chat_notif, $push_notif, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Profile updated successfully!";
                $_SESSION['username'] = $username;
                $user['username'] = $username;
                $user['phone'] = $phone;
                $user['bio'] = $bio;
                $user['email_notifications'] = $email_notif;
                $user['chat_notifications'] = $chat_notif;
                $user['push_notifications'] = $push_notif;
            } else {
                $error = "Error updating profile!";
            }
        }
    }
}

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token";
    } else {
        $validation = validateImageUpload($_FILES['avatar']);
        if (!$validation['success']) {
            $error = $validation['error'];
        } else {
            $target_dir = "uploads/avatars/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
            $new_filename = "avatar_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                // Delete old avatar if exists
                if (!empty($user['avatar']) && $user['avatar'] != 'default-avatar.jpg') {
                    $old_avatar = $target_dir . $user['avatar'];
                    if (file_exists($old_avatar)) {
                        unlink($old_avatar);
                    }
                }
                
                $update_avatar_sql = "UPDATE users SET avatar = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_avatar_sql);
                mysqli_stmt_bind_param($stmt, "si", $new_filename, $user_id);
                mysqli_stmt_execute($stmt);
                $user['avatar'] = $new_filename;
                $success = "Avatar updated successfully!";
            } else {
                $error = "Failed to upload avatar";
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
    <title>Marketplace 2st | Profile</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
    <header>
        <div class="logo">MarketPlace 2st</div>
        <div class="userMenu">
            <div class="userAvatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
            <span><?php echo htmlspecialchars($user['username']); ?></span>
        </div>
    </header>

    <?php if ($success): ?>
        <div style="background: #51cf66; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #ff6b6b; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="settingCont">
        <button class="sidebarTog" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="sidebarOver" id="sidebarOverlay"></div>

        <div class="sidebar" id="sidebar">
            <ul class="sidebarMenu">
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile Information</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                <li><a href="#"><i class="fas fa-shield-alt"></i> Account Security</a></li>
                <li><a href="#"><i class="fas fa-credit-card"></i> Payment Methods</a></li>
                <li><a href="main.php"><i class="fas fa-home"></i> Back to Home</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h1 class="section-title"><i class="fas fa-user-cog"></i> Profile Information</h1>
            
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?php echo htmlspecialchars(getUserAvatar($user['avatar'])); ?>" 
                         alt="Profile" class="avatar-img">
                    <form method="POST" enctype="multipart/form-data" id="avatarForm">
                        <?php echo csrfField(); ?>
                        <input type="file" name="avatar" id="avatarInput" style="display: none;" accept="image/*" onchange="this.form.submit()">
                        <div class="change-avatar" onclick="document.getElementById('avatarInput').click()">
                            <i class="fas fa-camera"></i>
                        </div>
                    </form>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p>Seller since <?php echo date('F Y', strtotime($user['seller_since'])); ?></p>
                    <div class="rating">
                        <?php 
                        $rating = $user['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - 0.5 <= $rating) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                        <span><?php echo number_format($rating, 1); ?> (<?php echo $user['reviews_count']; ?> reviews)</span>
                    </div>
                </div>
            </div>

            <form method="POST" action="">
                <?php echo csrfField(); ?>
                
                <div class="formRow">
                    <div class="formGroup">
                        <label for="myUsername">Username</label>
                        <input type="text" id="myUsername" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="formGroup">
                        <label for="myPhone">Phone Number</label>
                        <input type="tel" id="myPhone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                </div>

                <div class="formGroup">
                    <label for="myEmail">Email (Cannot be changed)</label>
                    <input type="email" id="myEmail" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>

                <div class="formGroup">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                </div>

                <div class="formGroup">
                    <label>Notification Preferences</label>
                    <div class="notifications">
                        <span>Email notifications</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_notifications" <?php echo $user['email_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="notifications">
                        <span>Chat notifications</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="chat_notifications" <?php echo $user['chat_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="notifications">
                        <span>Push notifications</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="push_notifications" <?php echo $user['push_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" name="update_profile" class="btn btnSave">Save Changes</button>
                    <button type="button" class="btn btnCancel" onclick="window.location.reload()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
    });
    
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
    });
    </script>
</body>
</html>
