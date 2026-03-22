<?php
session_start();
require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT username FROM chatbot WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
        
        $stmt = $pdo->prepare("UPDATE chatbot SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);
        
        // Use mailer.php to send email
        require_once 'mailer.php';
        
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
        $subject = "Vibly Password Reset";
        $body = "Hi " . htmlspecialchars($user['username']) . ",<br><br>";
        $body .= "We received a request to reset your password. Click the link below to set a new password:<br><br>";
        $body .= "<a href='$resetLink'>$resetLink</a><br><br>";
        $body .= "If you did not request this, please ignore this email.<br>";
        $body .= "This link will expire in 1 hour.";
        
        if (sendEmail($email, $subject, $body)) {
            $success = "A password reset link has been sent to your email address.";
        } else {
            $error = "Failed to send the email. Please contact support or ensure SMTP is correctly configured with OpenSSL enabled in PHP.";
        }
    } else {
        $error = "If this email exists in our system, a reset link will be sent."; // Vague for security
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Vibly</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Forgot Password</h1>
            <p>Enter your email to receive a reset link.</p>
            <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div style="color: #4ade80; background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.2); padding: 10px; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; text-align: center;"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <form method="POST" class="auth-form">
                <input type="email" name="email" placeholder="Your Email Address" required>
                <button type="submit">Send Reset Link</button>
            </form>
            <div class="auth-links">
                Remember your password? <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
