<?php
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    
    if (empty($user) || empty($pass)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM chatbot WHERE username = ?");
        $stmt->execute([$user]);
        if ($stmt->rowCount() > 0) {
            $error = "Username already exists.";
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO chatbot (username, password) VALUES (?, ?)");
            if ($stmt->execute([$user, $hashed])) {
                $_SESSION['username'] = $user;
                header("Location: index.php");
                exit;
            } else {
                $error = "Registration failed.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Vibly</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="chat-container" style="justify-content: center; padding: 40px; text-align: center;">
        <h1 style="margin-bottom: 20px;">Register</h1>
        <?php if($error): ?><p style="color: #f87171; margin-bottom: 15px; font-size: 0.9rem;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
            <input type="text" name="username" placeholder="Username" required style="padding: 15px; border-radius: 8px; border: 1px solid var(--border); background: #0f172a; color: white; font-family: 'Inter', sans-serif;">
            <input type="password" name="password" placeholder="Password" required style="padding: 15px; border-radius: 8px; border: 1px solid var(--border); background: #0f172a; color: white; font-family: 'Inter', sans-serif;">
            <button type="submit" id="send-btn" style="padding: 15px; border-radius: 8px; font-size: 1rem; width: 100%;">Create Account</button>
        </form>
        <p style="margin-top: 25px; font-size: 0.9rem; color: var(--text-muted);">Already have an account? <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">Login here</a></p>
    </div>
</body>
</html>
