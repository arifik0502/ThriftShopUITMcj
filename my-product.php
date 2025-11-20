<?php
/*this file sheet is for viewing user's products
    hadif hashim*/

require_once 'config.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = getUserId();
$success = '';
$error = '';

// Handle product deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    // Verify product belongs to user
    $check_sql = "SELECT image FROM products WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($product = mysqli_fetch_assoc($result)) {
        // Delete image file if exists
        if (!empty($product['image']) && file_exists('uploads/' . $product['image'])) {
            unlink('uploads/' . $product['image']);
        }
        
        // Delete product
        $delete_sql = "DELETE FROM products WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($stmt, "ii", $product_id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Product deleted successfully!";
            logSecurityEvent("Product deleted", "User: $user_id, Product ID: $product_id");
        } else {
            $error = "Error deleting product";
        }
    }
}

// Get user's products
$products_sql = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 JOIN categories c ON p.category_id = c.id 
                 WHERE p.user_id = ? 
                 ORDER BY p.created_at DESC";
$stmt = mysqli_prepare($conn, $products_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);

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
    <title>My Products | Thrift</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f0f0f0;
        }
        .product-info {
            padding: 15px;
        }
        .product-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .product-price {
            font-size: 20px;
            color: #d4af37;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .product-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-edit, .btn-delete {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-edit {
            background: #3498db;
            color: white;
        }
        .btn-edit:hover {
            background: #2980b9;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-delete:hover {
            background: #c0392b;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            margin-top: 30px;
        }
        .empty-state i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
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
                        <li><a href="create-product.php"><i class="fas fa-plus-circle"></i> Create New Product</a></li>
                        <li><a href="my-products.php" class="active"><i class="fas fa-box"></i> My Products/Listings</a></li>
                        <li><a href="sales-dashboard.php"><i class="fas fa-chart-line"></i> Sales Dashboard</a></li>
                    </ul>
                </li>
                <li><a href="main.php"><i class="fas fa-home"></i> Back to Home</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1 class="section-title"><i class="fas fa-box"></i> My Products/Listings</h1>
                <a href="create-product.php" class="btn btnSave" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>

            <?php if (mysqli_num_rows($products_result) > 0): ?>
                <div class="products-grid">
                    <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars(getProductImage($product['image'])); ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                            <div class="product-price">RM <?php echo number_format($product['price'], 2); ?></div>
                            <div class="product-meta">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                            </div>
                            <div class="product-meta">
                                <i class="fas fa-box"></i> Stock: <?php echo $product['stock']; ?>
                            </div>
                            <div class="product-meta">
                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                            </div>
                            <div class="product-actions">
                                <button class="btn-edit" onclick="alert('Edit feature coming soon!')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-delete" onclick="confirmDelete(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h2>No Products Yet</h2>
                    <p style="color: #666; margin: 20px 0;">You haven't created any products. Start selling by creating your first product!</p>
                    <a href="create-product.php" class="btn btnSave" style="text-decoration: none; display: inline-block;">
                        <i class="fas fa-plus"></i> Create Your First Product
                    </a>
                </div>
            <?php endif; ?>
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
    
    function confirmDelete(productId) {
        if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            window.location.href = 'my-products.php?delete=' + productId;
        }
    }
    </script>
</body>
</html>
