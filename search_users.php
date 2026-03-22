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

try {
    if (strlen($query) < 1) {
        $stmt = $pdo->prepare("
            SELECT recent_chats.contact_user AS username, c.profile_image,
                   (SELECT COUNT(*) FROM messages WHERE sender = recent_chats.contact_user AND receiver = ? AND is_read = 0) AS unread_count
            FROM (
                SELECT 
                    CASE 
                        WHEN sender = ? THEN receiver 
                        ELSE sender 
                    END AS contact_user,
                    MAX(created_at) as last_msg_time
                FROM messages 
                WHERE sender = ? OR receiver = ?
                GROUP BY contact_user
            ) AS recent_chats
            LEFT JOIN chatbot c ON recent_chats.contact_user = c.username
            ORDER BY last_msg_time DESC
            LIMIT 20
        ");
        $stmt->execute([$currentUser, $currentUser, $currentUser, $currentUser]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
        exit;
    }

    // Search for users other than the currently logged in user
    $stmt = $pdo->prepare("SELECT username, profile_image FROM chatbot WHERE username LIKE ? AND username != ? LIMIT 10");
    $stmt->execute(['%' . $query . '%', $_SESSION['username']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
