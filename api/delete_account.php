<?php
require 'auth.php';
require 'db.php';

$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized entry detected.']);
    exit;
}

// Intercept JSON body payloads to support React API fetch structures reliably
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);
$password = $input['password'] ?? $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password is unconditionally required to permanently delete the account.']);
    exit;
}


try {
    // 1. Pass the execution password securely through BCRYPT validation against the chatbot table architecture
    $stmt = $pdo->prepare("SELECT password FROM chatbot WHERE username = ?");
    $stmt->execute([$user]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account || !password_verify($password, $account['password'])) {
         echo json_encode(['status' => 'error', 'message' => 'Incorrect password entry. Account deletion aborted.']);
         exit;
    }

    // 2. Cascade hard delete onto the master authentication dictionary
    $stmt = $pdo->prepare("DELETE FROM chatbot WHERE username = ?");
    $stmt->execute([$user]);
    
    // 3. Drop every specific conversational fragment mapped to this specific user footprint unconditionally
    $stmtMsg = $pdo->prepare("DELETE FROM messages WHERE sender = ? OR receiver = ?");
    $stmtMsg->execute([$user, $user]);

    // Break temporal cache mappings securely
    auth_logout();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
