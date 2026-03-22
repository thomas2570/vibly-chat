<?php
require 'db.php';
try {
    $currentUser = 'thomas01';
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
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
?>
