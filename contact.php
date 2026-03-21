<?php
session_start();
$username = $_SESSION['username'] ?? 'Guest';
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
            
            <form action="https://formspree.io/f/xwvrjejb" method="POST" class="auth-form" style="text-align: left; gap: 1rem;">
                <label style="color:var(--text-muted); font-size:0.9rem; margin-bottom:-0.5rem; display:block;">Reporting as: <strong><?= htmlspecialchars($username) ?></strong></label>
                
                <!-- Visually hidden but passed directly to Formspree -->
                <input type="hidden" name="Reporter Username" value="<?= htmlspecialchars($username) ?>">
                <input type="hidden" name="_subject" value="New Vibly Problem Report">
                
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
