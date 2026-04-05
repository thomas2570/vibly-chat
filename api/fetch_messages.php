<?php
require 'auth.php';
require 'db.php';

header('Content-Type: application/json');

$user1 = auth_user();
if (!$user1 || !isset($_GET['target'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$user2 = $_GET['target'];

try {
    // Select all messages explicitly bridging these two users, ordered chronologically
    $stmt = $pdo->prepare("
        SELECT *, UNIX_TIMESTAMP(created_at) as unix_time FROM messages 
        WHERE (sender = ? AND receiver = ?) 
           OR (sender = ? AND receiver = ?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$user1, $user2, $user2, $user1]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($messages);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
