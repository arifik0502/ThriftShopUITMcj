<?php
/*this file sheet is for inbox/messages overview
    hadif hashim*/

require_once 'config.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = getUserId();

// Get all conversations with unread count
$conversations_sql = "
    SELECT 
        m.product_id,
        p.title as product_title,
        p.image as product_image,
        p.price as product_price,
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END as other_user_id,
        u.username as other_user_name,
        u.avatar as other_user_avatar,
        (SELECT message FROM messages 
         WHERE product_id = m.product_id 
         AND ((sender_id = ? AND receiver_id = other_user_id) OR (sender_id = other_user_id AND receiver_id = ?))
         ORDER BY created_at DESC LIMIT 1) as last_message,
        MAX(m.created_at) as last_message_time,
        (SELECT COUNT(*) FROM messages 
         WHERE product_id = m.product_id 
         AND receiver_id = ? 
         AND sender_id = other_user_id
         AND is_read = 0) as unread_count,
        p.user_id = ? as is_seller
    FROM messages m
    JOIN products p ON m.product_id = p.id
    JOIN users u ON u.id = CASE 
        WHEN m.sender_id = ? THEN m.receiver_id
        ELSE m.sender_id
    END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY m.product_id, other_user_id
    ORDER BY last_message_time DESC
";

$stmt = mysqli_prepare($conn, $conversations_sql);
mysqli_stmt_bind_param($stmt, "iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$conversations_result = mysqli_stmt_get_result($stmt);

// Get total unread count
$unread_count_sql = "SELECT COUNT(*) as total_unread FROM messages WHERE receiver_id = ? AND is_read = 0";
$stmt = mysqli_prepare($conn, $unread_count_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$unread_result = mysqli_stmt_get_result($stmt);
$total_unread = mysqli_fetch_assoc($unread_result)['total_unread'];

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
    <title>Inbox | Thrift</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .inbox-container {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .inbox-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .inbox-title {
            font-size: 28px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .unread-badge-header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .conversation-card {
            display: flex;
            gap: 20px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .conversation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .conversation-card.unread {
            background: linear-gradient(to right, rgba(102, 126, 234, 0.05), transparent);
            border-left: 4px solid #667eea;
        }
        
        .conversation-avatar-large {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .conversation-details {
            flex: 1;
        }
        
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .conversation-user {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .conversation-time {
            font-size: 13px;
            color: #999;
        }
        
        .conversation-product-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .product-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .product-name {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .product-price {
            font-size: 14px;
            color: #d4af37;
            font-weight: 600;
        }
        
        .conversation-preview {
            color: #666;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .conversation-card.unread .conversation-preview {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .conversation-badge {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-badge.seller {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .role-badge.buyer {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .unread-badge-card {
            background: #ff6b6b;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .empty-inbox {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }
        
        .empty-inbox i {
            font-size: 100px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-inbox h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .empty-inbox p {
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .browse-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .browse-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
                <a href="inbox.php" style="color: var(--accent); text-decoration: none;">
                    <i class="fas fa-envelope"></i> Messages
                    <?php if ($total_unread > 0): ?>
                        <span style="background: #ff6b6b; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                            <?php echo $total_unread; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="userMenu">
                <div class="userAvatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
        </header>

        <div class="inbox-container">
            <div class="inbox-header">
                <h1 class="inbox-title">
                    <i class="fas fa-inbox"></i> 
                    Your Messages
                </h1>
                <?php if ($total_unread > 0): ?>
                    <span class="unread-badge-header">
                        <?php echo $total_unread; ?> Unread
                    </span>
                <?php endif; ?>
            </div>

            <?php if (mysqli_num_rows($conversations_result) > 0): ?>
                <?php while ($conv = mysqli_fetch_assoc($conversations_result)): ?>
                    <a href="chat.php?product_id=<?php echo $conv['product_id']; ?>&<?php echo $conv['is_seller'] ? 'buyer_id' : 'seller_id'; ?>=<?php echo $conv['other_user_id']; ?>" 
                       class="conversation-card <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>">
                        <div class="conversation-avatar-large">
                            <?php echo strtoupper(substr($conv['other_user_name'], 0, 1)); ?>
                        </div>
                        <div class="conversation-details">
                            <div class="conversation-header">
                                <span class="conversation-user">
                                    <?php echo htmlspecialchars($conv['other_user_name']); ?>
                                </span>
                                <span class="conversation-time">
                                    <?php 
                                    $time_diff = time() - strtotime($conv['last_message_time']);
                                    if ($time_diff < 3600) {
                                        echo floor($time_diff / 60) . ' min ago';
                                    } elseif ($time_diff < 86400) {
                                        echo floor($time_diff / 3600) . ' hours ago';
                                    } else {
                                        echo date('M d, Y', strtotime($conv['last_message_time']));
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="conversation-product-info">
                                <img src="<?php echo htmlspecialchars(getProductImage($conv['product_image'])); ?>" 
                                     class="product-thumb" alt="Product">
                                <div style="flex: 1;">
                                    <div class="product-name">
                                        <?php echo htmlspecialchars($conv['product_title']); ?>
                                    </div>
                                    <div class="product-price">
                                        $<?php echo number_format($conv['product_price'], 2); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="conversation-preview">
                                <?php echo htmlspecialchars(substr($conv['last_message'], 0, 100)); ?>
                                <?php echo strlen($conv['last_message']) > 100 ? '...' : ''; ?>
                            </div>
                            <div class="conversation-badge">
                                <span class="role-badge <?php echo $conv['is_seller'] ? 'seller' : 'buyer'; ?>">
                                    <?php echo $conv['is_seller'] ? 'As Seller' : 'As Buyer'; ?>
                                </span>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="unread-badge-card">
                                        <?php echo $conv['unread_count']; ?> new
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-inbox">
                    <i class="fas fa-envelope-open"></i>
                    <h3>No Messages Yet</h3>
                    <p>Start shopping and connect with sellers to begin messaging!</p>
                    <a href="item.php" class="browse-btn">
                        <i class="fas fa-shopping-bag"></i> Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh inbox every 10 seconds
        setInterval(function() {
            location.reload();
        }, 10000);
    </script>
</body>
</html>