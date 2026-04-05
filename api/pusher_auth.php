<?php
/**
 * Pusher channel authentication endpoint.
 * Required for:
 *   - private-* channels (authenticate the user)
 *   - presence-* channels (authenticate + provide user info for presence tracking)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/pusher_init.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$socket_id     = $_POST['socket_id']     ?? '';
$channel_name  = $_POST['channel_name']  ?? '';

if (!$socket_id || !$channel_name) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing socket_id or channel_name']);
    exit;
}

// For presence channels, supply user info so Pusher can track members
if (strpos($channel_name, 'presence-') === 0) {
    $presence_data = [
        'user_id'   => $user,
        'user_info' => ['username' => $user]
    ];
    echo $pusher->authorizePresenceChannel($channel_name, $socket_id, $user, $presence_data['user_info']);
} else {
    // Private channel auth
    echo $pusher->authorizeChannel($channel_name, $socket_id);
}
?>
