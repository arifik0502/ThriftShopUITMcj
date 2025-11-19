<?php
/*this file sheet is for php items page
    hadif hashim*/

require_once 'config.php';

// Get category filter
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Limit search query length
if (strlen($search_query) > 100) {
    $search_query = substr($search_query, 0, 100);
}

// Build products query
$products_sql = "SELECT p.*, c.name as category_name FROM products p 
                 JOIN categories c ON p.category_id = c.id WHERE 1=1";

if ($category_filter > 0) {
    $products_sql .= " AND p.category_id = $category_filter";
}

if (!empty($search_query)) {
    $products_sql .= " AND (p.title LIKE '%$search_query%' OR p.description LIKE '%$search_query%')";
}

$products_sql .= " ORDER BY p.created_at DESC LIMIT 20";
$products_result = mysqli_query($conn, $products_sql);

// Get categories
$categories_sql = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_sql);

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Invalid security token');
    }
    
    $product_id = (int)$_POST['product_id'];
    $user_id = getUserId();
    
    // Check if already in cart
    $check_sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update quantity
        $update_sql = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
        mysqli_stmt_execute($stmt);
    } else {
        // Add new item
        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
        mysqli_stmt_execute($stmt);
    }
    
    echo "<script>alert('Product added to cart!');</script>";
}

// Get cart count
$cart_count = 0;
if (isLoggedIn()) {
    $cart_sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = " . getUserId();
    $cart_result = mysqli_query($conn, $cart_sql);
    $cart_row = mysqli_fetch_assoc($cart_result);
    $cart_count = $cart_row['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketPlace 2st | Items</title>
    <link rel="stylesheet" href="item.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">Thrift</div>
            <div class="search-bar">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search for products..." value="<?php echo htmlspecialchars($search_query); ?>">
                </form>
            </div>
            <div class="user-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php">Account</a>
                    <a href="#">Wishlist</a>
                    <a href="cart.php">Cart (<?php echo $cart_count; ?>)</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="main-content">
            <aside class="sidebar">
                <h3>Categories</h3>
                <ul class="categories">
                    <li><a href="item.php" class="<?php echo $category_filter == 0 ? 'active' : ''; ?>">All Products</a></li>
                    <?php 
                    mysqli_data_seek($categories_result, 0);
                    while ($category = mysqli_fetch_assoc($categories_result)): 
                    ?>
                    <li>
                        <a href="item.php?category=<?php echo $category['id']; ?>" 
                           class="<?php echo $category_filter == $category['id'] ? 'active' : ''; ?>">
                            <i class="fas fa-solid <?php echo htmlspecialchars($category['icon']); ?>"></i> 
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </aside>

            <main class="content-area">
                <div class="category-tabs">
                    <a href="item.php" class="active">Featured</a>
                    <a href="item.php?filter=new">New Arrivals</a>
                    <a href="item.php?filter=best">Best Sellers</a>
                    <a href="item.php?filter=sale">On Sale</a>
                    <a href="item.php?filter=trending">Trending</a>
                    <a href="item.php?filter=recommended">Recommended</a>
                </div>

                <div class="market-container">
                    <div class="market">
                        <div class="market-slide">
                            <h2>Summer Collection 2025</h2>
                            <p>Discover our new summer lineup with exclusive discounts and fresh styles for the season.</p>
                            <button class="btn">Shop Now</button>
                        </div>
                        <div class="market-slide">
                            <h2>Tech Gadgets Sale</h2>
                            <p>Up to 40% off on the latest electronics and smart home devices. Limited time offer!</p>
                            <button class="btn">Explore Deals</button>
                        </div>
                        <div class="market-slide">
                            <h2>Free Shipping Weekend</h2>
                            <p>Enjoy free shipping on all orders this weekend. No minimum purchase required.</p>
                            <button class="btn">Learn More</button>
                        </div>
                    </div>
                    <div class="market-arrow prev">&#10094;</div>
                    <div class="market-arrow next">&#10095;</div>
                    <div class="market-nav">
                        <div class="market-dot active"></div>
                        <div class="market-dot"></div>
                        <div class="market-dot"></div>
                    </div>
                </div>

                <div class="products-section">
                    <div class="section-header">
                        <h2>Popular Products</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="products-grid">
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars(getProductImage($product['image'])); ?>" 
                                         alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    Product Image
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                                <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                <div class="product-rating">
                                    <?php 
                                    $rating = $product['rating'];
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '★';
                                        } elseif ($i - 0.5 <= $rating) {
                                            echo '⯨';
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                    ?>
                                </div>
                                <form method="POST" action="">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="add_to_cart" class="add-to-cart">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </main>
        </div>

        <footer>
            <p>&copy; Thrift 2strian copy.</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const market = document.querySelector('.market');
            const slides = document.querySelectorAll('.market-slide');
            const dots = document.querySelectorAll('.market-dot');
            const prevBtn = document.querySelector('.market-arrow.prev');
            const nextBtn = document.querySelector('.market-arrow.next');
            
            let currentSlide = 0;
            const totalSlides = slides.length;
            
            function goToSlide(slideIndex) {
                if (slideIndex < 0) {
                    currentSlide = totalSlides - 1;
                } else if (slideIndex >= totalSlides) {
                    currentSlide = 0;
                } else {
                    currentSlide = slideIndex;
                }
                
                market.style.transform = `translateX(-${currentSlide * 100}%)`;
                
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentSlide);
                });
            }
            
            function nextSlide() {
                goToSlide(currentSlide + 1);
            }
            
            function prevSlide() {
                goToSlide(currentSlide - 1);
            }
            
            nextBtn.addEventListener('click', nextSlide);
            prevBtn.addEventListener('click', prevSlide);
            
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => goToSlide(index));
            });
            
            setInterval(nextSlide, 5000);
        });
    </script>
</body>
</html>
