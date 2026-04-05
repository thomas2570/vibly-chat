<?php
require 'auth.php';
require 'db.php';

$username = auth_user();
if (!$username) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$response = ['success' => false, 'error' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    
    $profileImage = null;
    if (isset($_POST['profile_image_base64']) && !empty($_POST['profile_image_base64'])) {
        $profileImage = $_POST['profile_image_base64'];
    }

    // Build the query
    try {
        if ($profileImage) {
            $stmt = $pdo->prepare("UPDATE chatbot SET full_name=?, email=?, gender=?, profile_image=? WHERE username=?");
            $result = $stmt->execute([$fullName, $email, $gender, $profileImage, $username]);
        } else {
            $stmt = $pdo->prepare("UPDATE chatbot SET full_name=?, email=?, gender=? WHERE username=?");
            $result = $stmt->execute([$fullName, $email, $gender, $username]);
        }

        if ($result) {
            $response['success'] = true;
            $response['error'] = '';
            if ($profileImage) {
                $response['profile_image'] = $profileImage;
            }
        } else {
            $response['error'] = 'Failed to update database (no rows affected or syntax error).';
        }
    } catch(PDOException $e) {
        $response['error'] = 'DB Error: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
