<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pusher_init.php';

$sender = auth_user();
if (!$sender) {
    die(json_encode(['error' => 'Unauthorized']));
}

$msgId = $_POST['id'] ?? 0;
$target = $_POST['target'] ?? '';

if (!$msgId) {
    die(json_encode(['error' => 'Missing message ID']));
}

try {
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND sender = ?");
    $stmt->execute([$msgId, $sender]);

    $data = [
        'type' => 'delete',
        'id' => $msgId,
        'target' => $target
    ];

    $pusher->trigger('private-user-' . $target, 'delete-event', $data);
    $pusher->trigger('private-user-' . $sender, 'delete-event', $data);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
