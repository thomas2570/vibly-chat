<?php
session_start();
require 'db.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$response = ['success' => false, 'error' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'];
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    
    $profileImage = null;
    $uploadDir = __DIR__ . '/uploads/';

    // Ensure uploads directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileSize = $_FILES['profile_image']['size'];
        $fileType = $_FILES['profile_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExts)) {
            $newFileName = md5(time() . $username) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $profileImage = $newFileName;
            } else {
                $response['error'] = 'Error moving uploaded file.';
                echo json_encode($response);
                exit;
            }
        } else {
            $response['error'] = 'Invalid file type. Only JPG, PNG, and GIF allowed.';
            echo json_encode($response);
            exit;
        }
    }

    // Build the query
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
        $response['error'] = 'Failed to update database.';
    }
}

echo json_encode($response);
?>
