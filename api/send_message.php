<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pusher_init.php';

$sender = auth_user();
if (!$sender) {
    die(json_encode(['error' => 'Unauthorized']));
}

$target = $_POST['target'] ?? '';
$message = $_POST['message'] ?? '';
$isImage = isset($_POST['isImage']) && $_POST['isImage'] === 'true' ? 1 : 0;
$unixTime = $_POST['unix_time'] ?? time();

if (!$target || !$message) {
    die(json_encode(['error' => 'Missing fields']));
}

try {
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO messages (sender, receiver, message, is_image, created_at) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))");
    $stmt->execute([$sender, $target, $message, $isImage, $unixTime]);
    $messageId = $pdo->lastInsertId();

    // Trigger Pusher event
    $data = [
        'type'    => 'chat',
        'sender'  => $sender,
        'message' => $message,
        'isImage' => $isImage === 1,
        'id'      => $messageId,
        'unix_time' => (int)$unixTime
    ];

    // Trigger to the target user's private channel
    $pusher->trigger('private-user-' . $target, 'chat-event', $data);

    echo json_encode(['status' => 'success', 'id' => $messageId]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
