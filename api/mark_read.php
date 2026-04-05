<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pusher_init.php';

$receiverUser = auth_user();
if (!$receiverUser) {
    die(json_encode(['error' => 'Unauthorized']));
}

$senderUser = $_POST['target'] ?? '';

if (!$senderUser) {
    die(json_encode(['error' => 'Missing target']));
}

try {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender = ? AND receiver = ? AND is_read = 0");
    $stmt->execute([$senderUser, $receiverUser]);

    $data = [
        'type'   => 'read_receipt',
        'target' => $receiverUser
    ];

    $pusher->trigger('private-user-' . $senderUser, 'read-receipt-event', $data);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
