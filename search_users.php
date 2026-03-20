<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

try {
    // Search for users other than the currently logged in user
    $stmt = $pdo->prepare("SELECT username FROM chatbot WHERE username LIKE ? AND username != ? LIMIT 10");
    $stmt->execute(['%' . $query . '%', $_SESSION['username']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
