<?php
/*this file sheet is for chat/messaging system
    hadif hashim*/

require_once 'config.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = getUserId();
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$other_user_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : (isset($_GET['buyer_id']) ? (int)$_GET['buyer_id'] : 0);

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Invalid security token');
    }
    
    $message_text = trim(sanitizeInput($_POST['message']));
    $receiver_id = (int)$_POST['receiver_id'];
    $msg_product_id = (int)$_POST['product_id'];
    
    if (!empty($message_text)) {
        // Handle image upload if present
        $image_name = null;
        if (isset($_FILES['message_image']) && $_FILES['message_image']['error'] == 0) {
            $validation = validateImageUpload($_FILES['message_image']);
            if ($validation['success']) {
                $target_dir = "uploads/messages/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES["message_image"]["name"], PATHINFO_EXTENSION));
                $image_name = "msg_" . $user_id . "_" . time() . "." . $file_extension;
                $target_file = $target_dir . $image_name;
                
                move_uploaded_file($_FILES["message_image"]["tmp_name"], $target_file);
            }
        }
        
        // Insert message
        $insert_sql = "INSERT INTO messages (product_id, sender_id, receiver_id, message, image) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "iiiss", $msg_product_id, $user_id, $receiver_id, $message_text, $image_name);
        mysqli_stmt_execute($stmt);
        
        // Redirect to refresh
        redirect("chat.php?product_id=$msg_product_id&" . ($user_id == $receiver_id ? "buyer_id" : "seller_id") . "=$other_user_id");
    }
}

// Mark messages as read
if ($product_id > 0 && $other_user_id > 0) {
    $mark_read_sql = "UPDATE messages SET is_read = 1 WHERE product_id = ? AND receiver_id = ? AND sender_id = ?";
    $stmt = mysqli_prepare($conn, $mark_read_sql);
    mysqli_stmt_bind_param($stmt, "iii", $product_id, $user_id, $other_user_id);
    mysqli_stmt_execute($stmt);
}

// Get product details if product_id is provided
$product = null;
if ($product_id > 0) {
    $product_sql = "SELECT p.*, u.username as seller_name, u.id as seller_id 
                    FROM products p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE p.id = ?";
    $stmt = mysqli_prepare($conn, $product_sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $product_result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($product_result);
    
    // Determine the other user
    if ($other_user_id == 0) {
        $other_user_id = ($user_id == $product['seller_id']) ? 0 : $product['seller_id'];
    }
}

// Get chat messages
$messages = [];
if ($product_id > 0 && $other_user_id > 0) {
    $messages_sql = "SELECT m.*, u.username as sender_name 
                     FROM messages m
                     JOIN users u ON m.sender_id = u.id
                     WHERE m.product_id = ? 
                     AND ((m.sender_id = ? AND m.receiver_id = ?) 
                     OR (m.sender_id = ? AND m.receiver_id = ?))
                     ORDER BY m.created_at ASC";
    $stmt = mysqli_prepare($conn, $messages_sql);
    mysqli_stmt_bind_param($stmt, "iiiii", $product_id, $user_id, $other_user_id, $other_user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $messages_result = mysqli_stmt_get_result($stmt);
    while ($msg = mysqli_fetch_assoc($messages_result)) {
        $messages[] = $msg;
    }
}

// Get other user details
$other_user = null;
if ($other_user_id > 0) {
    $other_user_sql = "SELECT username, avatar FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $other_user_sql);
    mysqli_stmt_bind_param($stmt, "i", $other_user_id);
    mysqli_stmt_execute($stmt);
    $other_user_result = mysqli_stmt_get_result($stmt);
    $other_user = mysqli_fetch_assoc($other_user_result);
}

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
    <title>Messages | Thrift</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .chat-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: calc(100vh - 200px);
        }
        
        .conversations-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .conversation-item:hover {
            background: #f8f9fa;
        }
        
        .conversation-item.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #d4af37;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 20px;
        }
        
        .conversation-info {
            flex: 1;
            margin-left: 15px;
        }
        
        .conversation-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .conversation-product {
            font-size: 13px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-item.active .conversation-product {
            color: rgba(255,255,255,0.8);
        }
        
        .unread-badge {
            background: #ff6b6b;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .chat-container {
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-header-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }
        
        .chat-header-info h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .chat-header-info p {
            margin: 5px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 400px;
            max-height: 500px;
        }
        
        .message-wrapper {
            display: flex;
            margin-bottom: 15px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-wrapper.sent {
            justify-content: flex-end;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .message-wrapper.received .message-bubble {
            background: white;
            border: 1px solid #e0e0e0;
            border-bottom-left-radius: 4px;
        }
        
        .message-wrapper.sent .message-bubble {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message-sender {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .message-text {
            line-height: 1.5;
        }
        
        .message-image {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 8px;
            cursor: pointer;
        }
        
        .message-time {
            font-size: 11px;
            margin-top: 5px;
            opacity: 0.7;
        }
        
        .chat-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }
        
        .chat-input-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .input-wrapper {
            flex: 1;
            position: relative;
        }
        
        .chat-textarea {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            resize: none;
            font-family: inherit;
            font-size: 14px;
            max-height: 100px;
            transition: border-color 0.3s;
        }
        
        .chat-textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .attach-btn {
            position: absolute;
            right: 10px;
            bottom: 10px;
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 20px;
            padding: 5px;
            transition: color 0.3s;
        }
        
        .attach-btn:hover {
            color: #667eea;
        }
        
        .send-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .send-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }
        
        .empty-state i {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .welcome-message h4 {
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .welcome-message p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .chat-layout {
                grid-template-columns: 1fr;
            }
            
            .conversations-list {
                display: none;
            }
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
                </a>
            </div>
            <div class="userMenu">
                <div class="userAvatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
        </header>

        <div style="padding: 20px;">
            <h1 style="color: var(--light); margin-bottom: 20px;">
                <i class="fas fa-comments"></i> Messages
            </h1>
            
            <div class="chat-layout">
                <!-- Conversations List (Left Sidebar) -->
                <div class="conversations-list" id="conversationsList">
                    <!-- Will be populated by JavaScript -->
                </div>
                
                <!-- Chat Container (Right Side) -->
                <div class="chat-container">
                    <?php if ($product && $other_user): ?>
                        <div class="chat-header">
                            <div class="chat-header-avatar">
                                <?php echo strtoupper(substr($other_user['username'], 0, 1)); ?>
                            </div>
                            <div class="chat-header-info">
                                <h3><?php echo htmlspecialchars($other_user['username']); ?></h3>
                                <p><i class="fas fa-box"></i> <?php echo htmlspecialchars($product['title']); ?></p>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <?php if (empty($messages)): ?>
                                <div class="welcome-message">
                                    <h4><i class="fas fa-info-circle"></i> Start Your Conversation</h4>
                                    <p>Ask questions about this product, discuss details, or arrange a meeting with the seller!</p>
                                </div>
                                <div class="empty-state">
                                    <i class="fas fa-comment-dots"></i>
                                    <p>No messages yet. Be the first to say hi!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="message-wrapper <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                        <div class="message-bubble">
                                            <div class="message-sender">
                                                <?php echo htmlspecialchars($msg['sender_name']); ?>
                                            </div>
                                            <div class="message-text">
                                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                            </div>
                                            <?php if ($msg['image']): ?>
                                                <img src="uploads/messages/<?php echo htmlspecialchars($msg['image']); ?>" 
                                                     class="message-image" 
                                                     onclick="window.open(this.src, '_blank')">
                                            <?php endif; ?>
                                            <div class="message-time">
                                                <?php echo date('M d, Y g:i A', strtotime($msg['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="chat-input-container">
                            <form class="chat-input-form" method="POST" enctype="multipart/form-data">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="hidden" name="receiver_id" value="<?php echo $other_user_id; ?>">
                                <input type="file" name="message_image" id="messageImage" accept="image/*" style="display: none;">
                                
                                <div class="input-wrapper">
                                    <textarea name="message" class="chat-textarea" placeholder="Type your message..." rows="1" required></textarea>
                                    <button type="button" class="attach-btn" onclick="document.getElementById('messageImage').click()">
                                        <i class="fas fa-paperclip"></i>
                                    </button>
                                </div>
                                
                                <button type="submit" name="send_message" class="send-btn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="height: 100%;">
                            <i class="fas fa-inbox"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a conversation from the list to start messaging</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of messages
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Auto-resize textarea
        const textarea = document.querySelector('.chat-textarea');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });
        }
        
        // Load conversations list
        function loadConversations() {
            fetch('get-conversations.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('conversationsList');
                    if (data.conversations && data.conversations.length > 0) {
                        container.innerHTML = data.conversations.map(conv => `
                            <a href="chat.php?product_id=${conv.product_id}&${conv.is_seller ? 'buyer_id' : 'seller_id'}=${conv.other_user_id}" style="text-decoration: none; color: inherit;">
                                <div class="conversation-item ${conv.is_active ? 'active' : ''}" style="display: flex; align-items: center;">
                                    <div class="conversation-avatar">
                                        ${conv.other_user_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-name">${conv.other_user_name}</div>
                                        <div class="conversation-product">${conv.product_title}</div>
                                    </div>
                                    ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                                </div>
                            </a>
                        `).join('');
                    } else {
                        container.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">No conversations yet</div>';
                    }
                })
                .catch(error => console.error('Error loading conversations:', error));
        }
        
        // Load conversations on page load
        loadConversations();
        
        // Refresh conversations every 5 seconds for real-time updates
        setInterval(loadConversations, 5000);
        
        // Refresh messages every 3 seconds for real-time updates
        <?php if ($product && $other_user): ?>
        setInterval(function() {
            fetch('get-new-messages.php?product_id=<?php echo $product_id; ?>&other_user_id=<?php echo $other_user_id; ?>&last_message_id=' + getLastMessageId())
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        const chatMessages = document.getElementById('chatMessages');
                        data.messages.forEach(msg => {
                            const messageHtml = createMessageElement(msg);
                            chatMessages.insertAdjacentHTML('beforeend', messageHtml);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                })
                .catch(error => console.error('Error loading new messages:', error));
        }, 3000);
        
        function getLastMessageId() {
            const messages = document.querySelectorAll('.message-wrapper');
            if (messages.length > 0) {
                return messages[messages.length - 1].dataset.messageId || 0;
            }
            return <?php echo !empty($messages) ? max(array_column($messages, 'id')) : 0; ?>;
        }
        
        function createMessageElement(msg) {
            const isSent = msg.sender_id == <?php echo $user_id; ?>;
            return `
                <div class="message-wrapper ${isSent ? 'sent' : 'received'}" data-message-id="${msg.id}">
                    <div class="message-bubble">
                        <div class="message-sender">${msg.sender_name}</div>
                        <div class="message-text">${msg.message.replace(/\n/g, '<br>')}</div>
                        ${msg.image ? `<img src="uploads/messages/${msg.image}" class="message-image" onclick="window.open(this.src, '_blank')">` : ''}
                        <div class="message-time">${msg.created_at_formatted}</div>
                    </div>
                </div>
            `;
        }
        <?php endif; ?>
    </script>
</body>
</html>