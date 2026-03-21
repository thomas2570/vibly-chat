<?php
session_start();
// If user is logged in, grab username, else 'Guest'
$username = $_SESSION['username'] ?? 'Guest';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $problem = trim($_POST['problem'] ?? '');
    
    if (empty($problem)) {
        $error = "Please describe your problem.";
    } else {
        // Change this to the owner's actual email address if desired
        $to = "admin@example.com"; 
        $subject = "Vibly Problem Report from: $username";
        $message = "Username: $username\n\nProblem Recorded:\n$problem";
        $headers = "From: noreply@vibly-chat.onrender.com";
        
        // Use PHP's built-in mail. Note: requires Sendmail or SMTP on the server.
        if (@mail($to, $subject, $message, $headers)) {
            $success = "Your problem has been reported successfully. Thank you!";
        } else {
            // Fallback for servers without mail() configured
            $success = "Your problem was recorded. (Note: Email sending might require server SMTP config to arrive in inbox).";
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
