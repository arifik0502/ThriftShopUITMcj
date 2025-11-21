<?php
/*this file sheet is for fetching user conversations
    hadif hashim*/

require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = getUserId();
$current_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$current_other_user = isset($_GET['other_user_id']) ? (int)$_GET['other_user_id'] : 0;

// Get all conversations (both as sender and receiver)
$conversations_sql = "
    SELECT 
        m.product_id,
        p.title as product_title,
        p.image as product_image,
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END as other_user_id,
        u.username as other_user_name,
        u.avatar as other_user_avatar,
        MAX(m.created_at) as last_message_time,
        (SELECT COUNT(*) FROM messages 
         WHERE product_id = m.product_id 
         AND receiver_id = ? 
         AND sender_id = CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
         END
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
mysqli_stmt_bind_param($stmt, "iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$conversations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['is_active'] = ($row['product_id'] == $current_product_id && $row['other_user_id'] == $current_other_user);
    $conversations[] = $row;
}

echo json_encode(['conversations' => $conversations]);
?>