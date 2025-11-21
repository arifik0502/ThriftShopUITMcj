<?php
/*this file sheet is for fetching new messages for real-time updates
    hadif hashim*/

require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = getUserId();
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$other_user_id = isset($_GET['other_user_id']) ? (int)$_GET['other_user_id'] : 0;
$last_message_id = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;

if ($product_id == 0 || $other_user_id == 0) {
    echo json_encode(['messages' => []]);
    exit;
}

// Get new messages since last_message_id
$messages_sql = "SELECT m.*, u.username as sender_name 
                 FROM messages m
                 JOIN users u ON m.sender_id = u.id
                 WHERE m.product_id = ? 
                 AND m.id > ?
                 AND ((m.sender_id = ? AND m.receiver_id = ?) 
                 OR (m.sender_id = ? AND m.receiver_id = ?))
                 ORDER BY m.created_at ASC";

$stmt = mysqli_prepare($conn, $messages_sql);
mysqli_stmt_bind_param($stmt, "iiiiii", $product_id, $last_message_id, $user_id, $other_user_id, $other_user_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$messages = [];
while ($msg = mysqli_fetch_assoc($result)) {
    $msg['created_at_formatted'] = date('M d, Y g:i A', strtotime($msg['created_at']));
    $msg['sender_id'] = (int)$msg['sender_id'];
    $messages[] = $msg;
}

// Mark new messages as read
if (!empty($messages)) {
    $mark_read_sql = "UPDATE messages SET is_read = 1 
                      WHERE product_id = ? AND receiver_id = ? AND sender_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $mark_read_sql);
    mysqli_stmt_bind_param($stmt, "iii", $product_id, $user_id, $other_user_id);
    mysqli_stmt_execute($stmt);
}

echo json_encode(['messages' => $messages]);
?>