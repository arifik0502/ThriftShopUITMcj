<?php
/*this file sheet is for php items page with seller reviews
    hadif hashim*/

require_once 'config.php';

// Get category filter
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Limit search query length
if (strlen($search_query) > 100) {
    $search_query = substr($search_query, 0, 100);
}

// Handle Submit Review
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        echo "<script>alert('Please login to submit a review!');</script>";
    } else {
        if (!verifyCSRFToken($_POST['csrf_token'])) {
            echo "<script>alert('Invalid security token');</script>";
        } else {
            $reviewer_id = getUserId();
            $seller_id = (int)$_POST['seller_id'];
            $product_id = (int)$_POST['product_id'];
            $rating = (int)$_POST['rating'];
            $comment = sanitizeInput($_POST['comment']);
            
            // Check if user has purchased from this seller
            $check_purchase_sql = "SELECT id FROM orders WHERE user_id = ? AND seller_id = ? AND product_id = ? AND status = 'completed'";
            $stmt = mysqli_prepare($conn, $check_purchase_sql);
            mysqli_stmt_bind_param($stmt, "iii", $reviewer_id, $seller_id, $product_id);
            mysqli_stmt_execute($stmt);
            $purchase_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($purchase_result) == 0) {
                echo "<script>alert('You can only review sellers you have purchased from!');</script>";
            } else {
                // Check if already reviewed
                $check_review_sql = "SELECT id FROM seller_reviews WHERE seller_id = ? AND reviewer_id = ? AND product_id = ?";
                $stmt = mysqli_prepare($conn, $check_review_sql);
                mysqli_stmt_bind_param($stmt, "iii", $seller_id, $reviewer_id, $product_id);
                mysqli_stmt_execute($stmt);
                $review_check = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($review_check) > 0) {
                    echo "<script>alert('You have already reviewed this seller for this product!');</script>";
                } else {
                    // Insert review
                    $insert_review_sql = "INSERT INTO seller_reviews (seller_id, reviewer_id, product_id, rating, comment) VALUES (?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_review_sql);
                    mysqli_stmt_bind_param($stmt, "iiiis", $seller_id, $reviewer_id, $product_id, $rating, $comment);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Update seller's average rating
                        $update_rating_sql = "UPDATE users SET 
                                            rating = (SELECT AVG(rating) FROM seller_reviews WHERE seller_id = ?),
                                            reviews_count = (SELECT COUNT(*) FROM seller_reviews WHERE seller_id = ?)
                                            WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $update_rating_sql);
                        mysqli_stmt_bind_param($stmt, "iii", $seller_id, $seller_id, $seller_id);
                        mysqli_stmt_execute($stmt);
                        
                        echo "<script>alert('Review submitted successfully!'); window.location.reload();</script>";
                    } else {
                        echo "<script>alert('Error submitting review');</script>";
                    }
                }
            }
        }
    }
}

// Build products query
$products_sql = "SELECT p.*, c.name as category_name, u.username as seller_name, u.rating as seller_rating, u.reviews_count 
                 FROM products p 
                 JOIN categories c ON p.category_id = c.id 
                 JOIN users u ON p.user_id = u.id
                 WHERE 1=1";

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
        echo "<script>alert('Please login to add items to cart!');</script>";
    } else {
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
}

// Get cart count
$cart_count = 0;
$unread_messages = 0;
if (isLoggedIn()) {
    $cart_sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = " . getUserId();
    $cart_result = mysqli_query($conn, $cart_sql);
    $cart_row = mysqli_fetch_assoc($cart_result);
    $cart_count = $cart_row['total'] ?? 0;
    
    // Get unread message count
    $unread_sql = "SELECT COUNT(*) as total FROM messages WHERE receiver_id = " . getUserId() . " AND is_read = 0";
    $unread_result = mysqli_query($conn, $unread_sql);
    $unread_row = mysqli_fetch_assoc($unread_result);
    $unread_messages = $unread_row['total'] ?? 0;
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
    <style>
        /* Product Details Popup Styles */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            overflow-y: auto;
        }
        
        .popup-content {
            background: white;
            max-width: 900px;
            margin: 50px auto;
            border-radius: 15px;
            position: relative;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        
        .popup-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 30px;
            cursor: pointer;
            color: #999;
            z-index: 10;
        }
        
        .popup-close:hover {
            color: #333;
        }
        
        .popup-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 40px;
        }
        
        .popup-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
            background: #f0f0f0;
        }
        
        .popup-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .product-title-popup {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .product-price-popup {
            font-size: 32px;
            color: #d4af37;
            font-weight: bold;
        }
        
        .product-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 15px 0;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }
        
        .seller-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .seller-rating {
            color: #f39c12;
            font-size: 18px;
        }
        
        .popup-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        
        .popup-actions button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-add-cart {
            background: #1a1a1a;
            color: white;
        }
        
        .btn-add-cart:hover {
            background: #d4af37;
        }
        
        /* Reviews Section */
        .reviews-section {
            padding: 30px 40px;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
        }
        
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .reviews-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .review-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .rating-input {
            display: flex;
            gap: 10px;
            margin: 10px 0;
            flex-direction: row-reverse;
            justify-content: flex-end;
            font-size: 30px;
        }
        
        .rating-input input {
            display: none;
        }
        
        .rating-input label {
            cursor: pointer;
            color: #ddd;
        }
        
        .rating-input input:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #f39c12;
        }
        
        .review-comment {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 80px;
            font-family: inherit;
        }
        
        .review-submit {
            background: #1a1a1a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .review-submit:hover {
            background: #d4af37;
        }
        
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .review-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .reviewer-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .review-rating {
            color: #f39c12;
        }
        
        .review-date {
            font-size: 12px;
            color: #999;
        }
        
        .review-comment-text {
            color: #666;
            line-height: 1.6;
        }
        
        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .popup-body {
                grid-template-columns: 1fr;
            }
            
            .popup-actions {
                flex-direction: column;
            }
        }
        
        .product-card {
            cursor: pointer;
        }
        
        .message-badge {
            position: relative;
            display: inline-block;
        }
        
        .message-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff6b6b;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
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
                    <a href="main.php">Home</a>
                    <a href="item.php">Buy</a>
                    <a href="profile.php">Sell</a>
                    <a href="ticket.php">Ticket</a>
                    <a href="profile.php">Account</a>
                    <a href="inbox.php" class="message-badge">
                        <i class="fas fa-envelope"></i> Messages
                        <?php if ($unread_messages > 0): ?>
                            <span class="message-count"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="cart.php">Cart (<?php echo $cart_count; ?>)</a>
                <?php else: ?>
                    <a href="main.php">Home</a>
                    <a href="item.php">Buy</a>
                    <a href="ticket.php">Ticket</a>
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
                        <div class="product-card" onclick="showProductPopup(<?php echo $product['id']; ?>)">
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
                                    $seller_rating = $product['seller_rating'];
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $seller_rating) {
                                            echo '★';
                                        } elseif ($i - 0.5 <= $seller_rating) {
                                            echo '⯨';
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                    echo ' (' . $product['reviews_count'] . ')';
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </main>
        </div>

        <footer style="background: #2d2d2d; color: white; margin-top: 50px;">
            <div style="max-width: 800px; margin: 0 auto; text-align: center; padding: 40px 20px;">
                <div>
                    <h3 style="font-size: 28px; margin-bottom: 15px; color: #d4af37;">Thrift</h3>
                    <p style="color: #b8b8b8; margin-bottom: 30px; line-height: 1.6;">Creating beautiful websites for businesses of all sizes since 2025.</p>
                </div>
                <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
                    <p style="color: #b8b8b8; font-size: 0.9rem;">&copy; thrift-ing since 2005.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Product Details Popup -->
    <div id="productPopup" class="popup-overlay" onclick="closePopupOnOverlay(event)">
        <div class="popup-content">
            <span class="popup-close" onclick="closeProductPopup()">&times;</span>
            <div id="popupBodyContent"></div>
        </div>
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

        function showProductPopup(productId) {
            // Fetch product details via AJAX
            fetch(`get-product-details.php?id=${productId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('popupBodyContent').innerHTML = html;
                    document.getElementById('productPopup').style.display = 'block';
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    console.error('Error loading product details:', error);
                    alert('Error loading product details');
                });
        }

        function closeProductPopup() {
            document.getElementById('productPopup').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closePopupOnOverlay(event) {
            if (event.target.id === 'productPopup') {
                closeProductPopup();
            }
        }

        function addToCartFromPopup(productId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo generateCSRFToken(); ?>';
            
            const productInput = document.createElement('input');
            productInput.type = 'hidden';
            productInput.name = 'product_id';
            productInput.value = productId;
            
            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'add_to_cart';
            submitInput.value = '1';
            
            form.appendChild(csrfInput);
            form.appendChild(productInput);
            form.appendChild(submitInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>