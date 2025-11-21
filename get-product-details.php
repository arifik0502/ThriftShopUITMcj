<?php
/*this file sheet is for fetching product details with seller reviews
    hadif hashim*/

require_once 'config.php';

if (!isset($_GET['id'])) {
    die('Invalid product ID');
}

$product_id = (int)$_GET['id'];

// Get product details with seller info
$product_sql = "SELECT p.*, c.name as category_name, u.username as seller_name, u.rating as seller_rating, 
                u.reviews_count, u.id as seller_id, u.created_at as seller_since
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                JOIN users u ON p.user_id = u.id
                WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $product_sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($product_result) == 0) {
    die('Product not found');
}

$product = mysqli_fetch_assoc($product_result);

// Get seller reviews
$reviews_sql = "SELECT sr.*, u.username as reviewer_name 
                FROM seller_reviews sr
                JOIN users u ON sr.reviewer_id = u.id
                WHERE sr.seller_id = ?
                ORDER BY sr.created_at DESC";
$stmt = mysqli_prepare($conn, $reviews_sql);
mysqli_stmt_bind_param($stmt, "i", $product['seller_id']);
mysqli_stmt_execute($stmt);
$reviews_result = mysqli_stmt_get_result($stmt);

// Check if current user can review (has purchased)
$can_review = false;
$has_reviewed = false;
$can_message = false;
$is_own_product = false;

if (isLoggedIn()) {
    $user_id = getUserId();
    
    // Check if this is the user's own product
    $is_own_product = ($user_id == $product['seller_id']);
    
    // Can message if logged in and not own product
    $can_message = !$is_own_product;
    
    // Check if purchased (for reviews)
    $purchase_check_sql = "SELECT id FROM orders WHERE user_id = ? AND seller_id = ? AND product_id = ? AND status = 'completed'";
    $stmt = mysqli_prepare($conn, $purchase_check_sql);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $product['seller_id'], $product_id);
    mysqli_stmt_execute($stmt);
    $can_review = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    
    // Check if already reviewed
    $review_check_sql = "SELECT id FROM seller_reviews WHERE reviewer_id = ? AND seller_id = ? AND product_id = ?";
    $stmt = mysqli_prepare($conn, $review_check_sql);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $product['seller_id'], $product_id);
    mysqli_stmt_execute($stmt);
    $has_reviewed = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
}
?>

<div class="popup-body">
    <div>
        <img src="<?php echo htmlspecialchars(getProductImage($product['image'])); ?>" 
             alt="<?php echo htmlspecialchars($product['title']); ?>" 
             class="popup-image">
    </div>
    
    <div class="popup-details">
        <h2 class="product-title-popup"><?php echo htmlspecialchars($product['title']); ?></h2>
        <div class="product-price-popup">$<?php echo number_format($product['price'], 2); ?></div>
        
        <div class="product-meta">
            <div class="meta-item">
                <i class="fas fa-tag"></i>
                <span>Category: <?php echo htmlspecialchars($product['category_name']); ?></span>
            </div>
            <div class="meta-item">
                <i class="fas fa-box"></i>
                <span>Stock: <?php echo $product['stock']; ?> available</span>
            </div>
            <div class="meta-item">
                <i class="fas fa-calendar"></i>
                <span>Listed: <?php echo date('M d, Y', strtotime($product['created_at'])); ?></span>
            </div>
        </div>
        
        <div class="seller-info">
            <h4 style="margin-bottom: 10px; color: #2c3e50;">
                <i class="fas fa-user"></i> Seller Information
            </h4>
            <p style="font-weight: 600; margin-bottom: 5px;">
                <?php echo htmlspecialchars($product['seller_name']); ?>
            </p>
            <p class="seller-rating">
                <?php 
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $product['seller_rating']) {
                        echo '★';
                    } elseif ($i - 0.5 <= $product['seller_rating']) {
                        echo '⯨';
                    } else {
                        echo '☆';
                    }
                }
                ?>
                <?php echo number_format($product['seller_rating'], 1); ?> 
                (<?php echo $product['reviews_count']; ?> reviews)
            </p>
            <p style="font-size: 13px; color: #666; margin-top: 5px;">
                Member since <?php echo date('F Y', strtotime($product['seller_since'])); ?>
            </p>
        </div>
        
        <div>
            <h4 style="margin-bottom: 10px; color: #2c3e50;">Description</h4>
            <p style="color: #666; line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </p>
        </div>
        
        <div class="popup-actions">
            <?php if (!$is_own_product): ?>
                <button class="btn-add-cart" onclick="addToCartFromPopup(<?php echo $product['id']; ?>)">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
                
                <?php if ($can_message): ?>
                    <button class="btn-message-seller" onclick="window.location.href='chat.php?product_id=<?php echo $product_id; ?>&seller_id=<?php echo $product['seller_id']; ?>'">
                        <i class="fas fa-comments"></i> Message Seller
                    </button>
                <?php elseif (!isLoggedIn()): ?>
                    <button class="btn-message-seller" onclick="alert('Please login to message the seller'); window.location.href='login.php'">
                        <i class="fas fa-comments"></i> Message Seller
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
                    <i class="fas fa-info-circle"></i> This is your own product
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reviews Section -->
<div class="reviews-section">
    <div class="reviews-header">
        <h3 class="reviews-title">
            <i class="fas fa-star"></i> Seller Reviews
        </h3>
    </div>
    
    <?php if ($can_review && !$has_reviewed && isLoggedIn() && !$is_own_product): ?>
    <div class="review-form">
        <h4 style="margin-bottom: 15px; color: #2c3e50;">Write a Review</h4>
        <form method="POST" action="item.php">
            <?php echo csrfField(); ?>
            <input type="hidden" name="seller_id" value="<?php echo $product['seller_id']; ?>">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Rating:</label>
                <div class="rating-input">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5">★</label>
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4">★</label>
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3">★</label>
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2">★</label>
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1">★</label>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Your Review:</label>
                <textarea name="comment" class="review-comment" placeholder="Share your experience with this seller..." required></textarea>
            </div>
            
            <button type="submit" name="submit_review" class="review-submit">
                <i class="fas fa-paper-plane"></i> Submit Review
            </button>
        </form>
    </div>
    <?php elseif (!isLoggedIn()): ?>
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        <p style="color: #666;">Please <a href="login.php" style="color: #d4af37; font-weight: 600;">login</a> to write a review</p>
    </div>
    <?php elseif ($has_reviewed): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> You have already reviewed this seller for this product
    </div>
    <?php elseif (!$can_review && !$is_own_product): ?>
    <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <i class="fas fa-info-circle"></i> You can only review sellers after purchasing from them
    </div>
    <?php endif; ?>
    
    <h4 style="margin: 20px 0; color: #2c3e50;">All Reviews (<?php echo mysqli_num_rows($reviews_result); ?>)</h4>
    
    <div class="reviews-list">
        <?php if (mysqli_num_rows($reviews_result) > 0): ?>
            <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
            <div class="review-item">
                <div class="review-header">
                    <div>
                        <div class="reviewer-name">
                            <i class="fas fa-user-circle"></i> 
                            <?php echo htmlspecialchars($review['reviewer_name']); ?>
                        </div>
                        <div class="review-rating">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="review-date">
                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                    </div>
                </div>
                <div class="review-comment-text">
                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="no-reviews">
            <i class="fas fa-comments" style="font-size: 48px; color: #ddd; margin-bottom: 10px;"></i>
            <p>No reviews yet. Be the first to review this seller!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.btn-message-seller {
    flex: 1;
    padding: 15px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-message-seller:hover {
    background: linear-gradient(45deg, #764ba2, #667eea);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-message-seller i {
    font-size: 18px;
}
</style>