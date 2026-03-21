<?php
session_start();
$username = $_SESSION['username'] ?? 'Guest';

// Build the dynamic return URL for Formspree to seamlessly redirect back here
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$current_url = $protocol . $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'], 2)[0];
$next_url = $current_url . '?success=1';

$success_msg = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_msg = "Your problem has been reported successfully. Thank you!";
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
            
            <?php if($success_msg): ?>
                <div class="error-msg" style="color:#10b981; background:rgba(16,185,129,0.15); border-color:rgba(16,185,129,0.3); margin-bottom: 1.5rem;">
                    <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>
            
            <form action="https://formspree.io/f/xwvrjejb" method="POST" class="auth-form" style="text-align: left; gap: 1rem;">
                <label style="color:var(--text-muted); font-size:0.9rem; margin-bottom:-0.5rem; display:block;">Reporting as: <strong><?= htmlspecialchars($username) ?></strong></label>
                
                <!-- Visually hidden but passed directly to Formspree -->
                <input type="hidden" name="Reporter Username" value="<?= htmlspecialchars($username) ?>">
                <input type="hidden" name="_subject" value="New Vibly Problem Report">
                
                <!-- Redirects seamlessly back to our site after submit to hide Formspree -->
                <input type="hidden" name="_next" value="<?= htmlspecialchars($next_url) ?>">
                
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
