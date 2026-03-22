<?php
session_start();
require 'db.php';

$error = '';
$success = '';

if (!isset($_GET['token']) && !isset($_POST['token'])) {
    die("Invalid request. Missing token.");
}

$token = $_GET['token'] ?? $_POST['token'];

// Check token validity
$stmt = $pdo->prepare("SELECT username FROM chatbot WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $error = "This password reset link is invalid or has expired.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if (empty($password) || strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE chatbot SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?");
        if ($stmt->execute([$hashed, $token])) {
            $success = "Your password has been reset successfully. You can now <a href='login.php' style='color: var(--primary-color);'>login</a>.";
        } else {
            $error = "An error occurred while resetting your password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Vibly</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Reset Password</h1>
            <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?>
                <div style="color: #4ade80; background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; text-align: center;">
                    <?= $success ?>
                </div>
            <?php elseif ($user): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="password" name="password" placeholder="New Password (min 6 chars)" required>
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                    <button type="submit">Complete Reset</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
