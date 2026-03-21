<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['username'];

try {
    // 1. Delete user footprint from authentication module
    $stmt = $pdo->prepare("DELETE FROM chatbot WHERE username = ?");
    $stmt->execute([$user]);
    
    // 2. Erase full conversation trace logically from the messaging matrix
    $stmtMsg = $pdo->prepare("DELETE FROM messages WHERE sender = ? OR receiver = ?");
    $stmtMsg->execute([$user, $user]);

    // Wipe session cleanly
    session_destroy();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
