<?php
/*this file sheet is for creating new products
    hadif hashim*/

require_once 'config.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = getUserId();
$success = '';
$error = '';

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_product'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token";
    } else {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $price = (float)$_POST['price'];
        $category_id = (int)$_POST['category_id'];
        $stock = (int)$_POST['stock'];
        
        // Validate inputs
        if (empty($title) || empty($description) || $price <= 0 || $stock < 0) {
            $error = "Please fill in all required fields with valid values";
        } else {
            // Handle image upload
            $image_name = '';
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                $validation = validateImageUpload($_FILES['product_image']);
                if (!$validation['success']) {
                    $error = $validation['error'];
                } else {
                    $target_dir = "uploads/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
                    $image_name = "product_" . $user_id . "_" . time() . "." . $file_extension;
                    $target_file = $target_dir . $image_name;
                    
                    if (!move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                        $error = "Failed to upload image";
                        $image_name = '';
                    }
                }
            }
            
            if (empty($error)) {
                $sql = "INSERT INTO products (user_id, category_id, title, description, price, stock, image, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iissdis", $user_id, $category_id, $title, $description, $price, $stock, $image_name);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Product created successfully!";
                    logSecurityEvent("Product created", "User: $user_id, Product: $title");
                    // Reset form
                    $_POST = array();
                } else {
                    $error = "Error creating product. Please try again.";
                }
            }
        }
    }
}

// Get categories
$categories_sql = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_sql);

// Get user data
$user_sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Product | Thrift</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
    <header>
        <div class="logo">MarketPlace 2st</div>
        <div class="nav-links" style="display: flex; gap: 20px; margin-right: 20px;">
            <a href="main.php" style="color: var(--accent); text-decoration: none;">Home</a>
            <a href="item.php" style="color: var(--accent); text-decoration: none;">Buy</a>
            <a href="profile.php" style="color: var(--accent); text-decoration: none;">Sell</a>
            <a href="item.php" style="color: var(--accent); text-decoration: none;">Items</a>
            <a href="ticket.php" style="color: var(--accent); text-decoration: none;">Ticket</a>
        </div>
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
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile Information</a></li>
                <li>
                    <a href="#" onclick="toggleSellSubmenu(event)" id="sellToggle">
                        <i class="fas fa-store"></i> Sell 
                        <i class="fas fa-chevron-down" style="float: right; transition: transform 0.3s; transform: rotate(180deg);" id="sellChevron"></i>
                    </a>
                    <ul class="sub-menu" id="sellSubmenu" style="display: block; padding-left: 20px; margin-top: 10px;">
                        <li><a href="create-product.php" class="active"><i class="fas fa-plus-circle"></i> Create New Product</a></li>
                        <li><a href="my-products.php"><i class="fas fa-box"></i> My Products/Listings</a></li>
                        <li><a href="sales-dashboard.php"><i class="fas fa-chart-line"></i> Sales Dashboard</a></li>
                    </ul>
                </li>
                <li><a href="main.php"><i class="fas fa-home"></i> Back to Home</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h1 class="section-title"><i class="fas fa-plus-circle"></i> Create New Product</h1>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                
                <div class="formGroup">
                    <label for="title">Product Title *</label>
                    <input type="text" id="title" name="title" placeholder="Enter product title" required>
                </div>

                <div class="formGroup">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" placeholder="Describe your product..." required></textarea>
                </div>

                <div class="formRow">
                    <div class="formGroup">
                        <label for="price">Price (RM) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="formGroup">
                        <label for="stock">Stock Quantity *</label>
                        <input type="number" id="stock" name="stock" min="0" placeholder="0" required>
                    </div>
                </div>

                <div class="formRow">
                    <div class="formGroup">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="formGroup">
                    <label for="product_image">Product Image</label>
                    <input type="file" id="product_image" name="product_image" accept="image/*">
                    <small style="color: #888;">Max file size: 5MB. Accepted formats: JPG, PNG, GIF</small>
                </div>

                <div class="action-buttons">
                    <button type="submit" name="create_product" class="btn btnSave">
                        <i class="fas fa-check"></i> Create Product
                    </button>
                    <button type="reset" class="btn btnCancel">
                        <i class="fas fa-times"></i> Clear Form
                    </button>
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
    
    function toggleSellSubmenu(event) {
        event.preventDefault();
        const submenu = document.getElementById('sellSubmenu');
        const chevron = document.getElementById('sellChevron');
        
        if (submenu.style.display === 'none' || submenu.style.display === '') {
            submenu.style.display = 'block';
            chevron.style.transform = 'rotate(180deg)';
        } else {
            submenu.style.display = 'none';
            chevron.style.transform = 'rotate(0deg)';
        }
    }
    </script>
</body>
</html>
