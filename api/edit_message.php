<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pusher_init.php';

$sender = auth_user();
if (!$sender) {
    die(json_encode(['error' => 'Unauthorized']));
}

$msgId = $_POST['id'] ?? 0;
$newMessage = $_POST['message'] ?? '';
$target = $_POST['target'] ?? '';

if (!$msgId || !$newMessage) {
    die(json_encode(['error' => 'Missing fields']));
}

try {
    // Verify 2-minute time limit
    $stmt = $pdo->prepare("SELECT created_at FROM messages WHERE id = ? AND sender = ?");
    $stmt->execute([$msgId, $sender]);
    $msgRow = $stmt->fetch();
    
    if (!$msgRow) {
        die(json_encode(['error' => 'Message not found']));
    }

    $createdAt = strtotime($msgRow['created_at']);
    if (time() - $createdAt > 120) {
        die(json_encode(['error' => 'Edit time limit (2 mins) expired']));
    }

    $stmt = $pdo->prepare("UPDATE messages SET message = ?, is_edited = 1 WHERE id = ? AND sender = ?");
    $stmt->execute([$newMessage, $msgId, $sender]);

    $data = [
        'type' => 'edit',
        'id' => $msgId,
        'message' => $newMessage,
        'target' => $target
    ];

    $pusher->trigger('private-user-' . $target, 'edit-event', $data);
    $pusher->trigger('private-user-' . $sender, 'edit-event', $data);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
