<?php
/*this file sheet is for sales dashboard
    hadif hashim*/

require_once 'config.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = getUserId();

// Get statistics
// Total products
$total_products_sql = "SELECT COUNT(*) as total FROM products WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $total_products_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$total_products = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

// Total value of inventory
$inventory_value_sql = "SELECT SUM(price * stock) as total_value FROM products WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $inventory_value_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$inventory_value = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_value'] ?? 0;

// Total stock
$total_stock_sql = "SELECT SUM(stock) as total_stock FROM products WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $total_stock_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$total_stock = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_stock'] ?? 0;

// Products by category
$category_stats_sql = "SELECT c.name, COUNT(p.id) as product_count, SUM(p.stock) as total_stock
                       FROM products p
                       JOIN categories c ON p.category_id = c.id
                       WHERE p.user_id = ?
                       GROUP BY c.id, c.name
                       ORDER BY product_count DESC";
$stmt = mysqli_prepare($conn, $category_stats_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$category_stats = mysqli_stmt_get_result($stmt);

// Recent products
$recent_products_sql = "SELECT p.*, c.name as category_name 
                        FROM products p 
                        JOIN categories c ON p.category_id = c.id 
                        WHERE p.user_id = ? 
                        ORDER BY p.created_at DESC 
                        LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_products_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$recent_products = mysqli_stmt_get_result($stmt);

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
    <title>Sales Dashboard | Thrift</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
            opacity: 0.8;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .dashboard-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .category-item:last-child {
            border-bottom: none;
        }
        .category-name {
            font-weight: 500;
            color: #2c3e50;
        }
        .category-stats {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }
        .recent-product {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
        }
        .recent-product:last-child {
            border-bottom: none;
        }
        .recent-product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: #f0f0f0;
        }
        .recent-product-info {
            flex: 1;
        }
        .recent-product-title {
            font-weight: 500;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .recent-product-meta {
            font-size: 14px;
            color: #666;
        }
        .recent-product-price {
            font-size: 18px;
            font-weight: bold;
            color: #d4af37;
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
                        <li><a href="my-products.php"><i class="fas fa-box"></i> My Products/Listings</a></li>
                        <li><a href="sales-dashboard.php" class="active"><i class="fas fa-chart-line"></i> Sales Dashboard</a></li>
                    </ul>
                </li>
                <li><a href="main.php"><i class="fas fa-home"></i> Back to Home</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <h1 class="section-title"><i class="fas fa-chart-line"></i> Sales Dashboard</h1>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-box"></i></div>
                    <div class="stat-value"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-value">RM <?php echo number_format($inventory_value, 2); ?></div>
                    <div class="stat-label">Inventory Value</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-warehouse"></i></div>
                    <div class="stat-value"><?php echo $total_stock; ?></div>
                    <div class="stat-label">Total Stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-value">0</div>
                    <div class="stat-label">Total Sales</div>
                </div>
            </div>

            <!-- Products by Category -->
            <div class="dashboard-section">
                <div class="section-title">
                    <i class="fas fa-tags"></i> Products by Category
                </div>
                <?php if (mysqli_num_rows($category_stats) > 0): ?>
                    <?php while ($cat = mysqli_fetch_assoc($category_stats)): ?>
                    <div class="category-item">
                        <div class="category-name">
                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($cat['name']); ?>
                        </div>
                        <div class="category-stats">
                            <span><i class="fas fa-box"></i> <?php echo $cat['product_count']; ?> products</span>
                            <span><i class="fas fa-warehouse"></i> <?php echo $cat['total_stock']; ?> stock</span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">No products yet</p>
                <?php endif; ?>
            </div>

            <!-- Recent Products -->
            <div class="dashboard-section">
                <div class="section-title">
                    <i class="fas fa-clock"></i> Recent Products
                </div>
                <?php if (mysqli_num_rows($recent_products) > 0): ?>
                    <?php while ($product = mysqli_fetch_assoc($recent_products)): ?>
                    <div class="recent-product">
                        <img src="<?php echo htmlspecialchars(getProductImage($product['image'])); ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>"
                             class="recent-product-img">
                        <div class="recent-product-info">
                            <div class="recent-product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                            <div class="recent-product-meta">
                                <?php echo htmlspecialchars($product['category_name']); ?> • 
                                Stock: <?php echo $product['stock']; ?> • 
                                <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                            </div>
                        </div>
                        <div class="recent-product-price">RM <?php echo number_format($product['price'], 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">No products yet</p>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="action-buttons">
                <a href="create-product.php" class="btn btnSave" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-plus"></i> Create New Product
                </a>
                <a href="my-products.php" class="btn btnCancel" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-box"></i> View All Products
                </a>
            </div>
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