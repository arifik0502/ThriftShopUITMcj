<?php
/*this file sheet is for php cart
    hadif hashim*/

require_once 'config.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = getUserId();

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Invalid security token');
    }
    
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        // Check stock availability
        $check_sql = "SELECT p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $cart_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if ($row = mysqli_fetch_assoc($check_result)) {
            if ($quantity <= $row['stock']) {
                $sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $user_id);
                mysqli_stmt_execute($stmt);
            }
        }
    }
}

// Handle item removal
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($stmt);
    redirect('cart.php');
}

// Get cart items
$cart_sql = "SELECT c.*, p.title, p.price, p.image, p.stock 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | Thrift</title>
    <link rel="stylesheet" href="item.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 20px;
        }
        .cart-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .quantity-input {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .cart-summary {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-top: 30px;
        }
        .remove-btn {
            background: #ff6b6b;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <header style="position: fixed; top: 0; width: 100%; background: var(--secondary); z-index: 1000;">
            <div class="logo">Thrift</div>
            <div class="search-bar">
                <input type="text" placeholder="Search for products...">
            </div>
            <div class="user-actions">
                <a href="profile.php">Account</a>
                <a href="item.php">Continue Shopping</a>
                <a href="cart.php">Cart</a>
            </div>
        </header>

        <div class="cart-container">
            <h1 style="color: var(--light); margin-bottom: 30px;">Shopping Cart</h1>

            <?php if (mysqli_num_rows($cart_result) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($cart_result)): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                <div class="cart-item">
                    <img src="<?php echo htmlspecialchars(getProductImage($item['image'])); ?>" 
                         alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <div class="cart-item-info">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                        <p>Stock: <?php echo $item['stock']; ?> available</p>
                    </div>
                    <div class="cart-item-actions">
                        <form method="POST" action="" style="display: inline;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <input type="number" name="quantity" class="quantity-input" 
                                   value="<?php echo $item['quantity']; ?>" 
                                   min="1" max="<?php echo $item['stock']; ?>"
                                   onchange="this.form.submit()">
                            <input type="hidden" name="update_quantity" value="1">
                        </form>
                        <strong>$<?php echo number_format($subtotal, 2); ?></strong>
                        <a href="cart.php?remove=<?php echo $item['id']; ?>" 
                           onclick="return confirm('Remove this item?')" 
                           class="remove-btn">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>

                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div style="display: flex; justify-content: space-between; margin: 20px 0; padding: 15px 0; border-top: 2px solid #ddd; border-bottom: 2px solid #ddd;">
                        <strong style="font-size: 20px;">Total:</strong>
                        <strong style="font-size: 20px; color: var(--accent);">$<?php echo number_format($total, 2); ?></strong>
                    </div>
                    <button class="add-to-cart" style="width: 100%; padding: 15px; font-size: 18px;" 
                            onclick="alert('Checkout feature coming soon!')">
                        Proceed to Checkout
                    </button>
                </div>

            <?php else: ?>
                <div style="background: white; border-radius: 8px; padding: 50px; text-align: center;">
                    <i class="fas fa-shopping-cart" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>
                    <h2>Your cart is empty</h2>
                    <p style="color: #666; margin: 20px 0;">Start adding some products to your cart!</p>
                    <a href="item.php" class="add-to-cart" style="display: inline-block; text-decoration: none;">
                        Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
