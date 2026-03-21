<?php
session_start();
// Include Composer's autoloader for PHPMailer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If user is logged in, grab username, else 'Guest'
$username = $_SESSION['username'] ?? 'Guest';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $problem = trim($_POST['problem'] ?? '');
    
    if (empty($problem)) {
        $error = "Please describe your problem.";
    } else {
        $mail = new PHPMailer(true);
        try {
            // Server settings for Gmail
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'thomasramesh449@gmail.com';
            
            // IMPORTANT: You MUST generate a 16-letter Google App Password!
            // Do not use your normal Gmail password here.
            $mail->Password   = 'sficvuiolqthlqmc'; 
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('thomasramesh449@gmail.com', 'Vibly Support Form');
            $mail->addAddress('thomasramesh449@gmail.com');

            // Content
            $mail->isHTML(false);
            $mail->Subject = "Vibly Problem Report from: $username";
            $mail->Body    = "Username: $username\n\nProblem Recorded:\n$problem";

            $mail->send();
            $success = "Your problem has been reported successfully. Thank you!";
        } catch (Exception $e) {
            $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - Vibly</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container" style="max-width: 500px;">
        <div class="auth-box">
            <h1 style="font-size: 2rem;">Contact Support</h1>
            <p style="margin-bottom: 1.5rem;">Report a problem to the admin</p>
            
            <?php if($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if($success): ?><div class="error-msg" style="color:#10b981; background:rgba(16,185,129,0.15); border-color:rgba(16,185,129,0.3);"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <form method="POST" class="auth-form" style="text-align: left; gap: 1rem;">
                <label style="color:var(--text-muted); font-size:0.9rem; margin-bottom:-0.5rem; display:block;">Reporting as: <strong><?= htmlspecialchars($username) ?></strong></label>
                <textarea name="problem" rows="5" placeholder="Describe the problem you are facing..." required style="padding: 1rem; border-radius: var(--radius-md); background: rgba(0,0,0,0.2); border: 1px solid var(--glass-border); color: var(--text-main); font-family: inherit; resize: vertical; width: 100%; box-sizing: border-box; font-size: 0.95rem;"></textarea>
                <button type="submit" style="margin-top: 0;">Send Report</button>
            </form>
            <div class="auth-links" style="margin-top: 1.5rem;">
                <a href="index.php">← Back to Chat</a>
            </div>
        </div>
        
        <!-- Copyright Footer -->
        <div style="text-align: center; margin-top: 2rem; color: var(--text-muted); font-size: 0.85rem;">
            Copyright &copy; 2026 Vibly
        </div>
    </div>
</body>
</html>
