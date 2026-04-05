<?php
require 'auth.php';
require 'db.php';

$loggedIn = auth_user();
if (!$loggedIn) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$username = $loggedIn;
if (isset($_GET['user']) && !empty($_GET['user'])) {
    $username = $_GET['user'];
}

$stmt = $pdo->prepare("SELECT full_name, email, gender, profile_image FROM chatbot WHERE username = ?");
$stmt->execute([$username]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    echo json_encode(['error' => 'User not found']);
} else {
    echo json_encode($profile);
}
?>
