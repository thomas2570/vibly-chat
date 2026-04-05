<?php
require 'auth.php';
$username = auth_user() ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - Vibly</title>
    <link rel="stylesheet" href="/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container" style="max-width: 500px;">
        <div class="auth-box">
            <h1 style="font-size: 2rem;">Contact Support</h1>
            <p style="margin-bottom: 1.5rem;">Report a problem to the admin</p>
            
            <div id="error-message" class="error-msg" style="display: none; margin-bottom: 1.5rem;"></div>
            <div id="success-message" class="error-msg" style="display: none; color:#10b981; background:rgba(16,185,129,0.15); border-color:rgba(16,185,129,0.3); margin-bottom: 1.5rem;">
                Your problem has been securely reported direct to the admin. Thank you!
            </div>
            
            <form id="contact-form" class="auth-form" style="text-align: left; gap: 1rem;">
                <label style="color:var(--text-muted); font-size:0.9rem; margin-bottom:-0.5rem; display:block;">Reporting as: <strong><?= htmlspecialchars($username) ?></strong></label>
                
                <!-- Automatically injected payload data -->
                <input type="hidden" name="Reporter Username" value="<?= htmlspecialchars($username) ?>">
                <input type="hidden" name="_subject" value="New Vibly Problem Report">
                
                <textarea name="problem" id="problem-input" rows="5" placeholder="Describe the problem you are facing..." required style="padding: 1rem; border-radius: var(--radius-md); background: rgba(0,0,0,0.2); border: 1px solid var(--glass-border); color: var(--text-main); font-family: inherit; resize: vertical; width: 100%; box-sizing: border-box; font-size: 0.95rem;"></textarea>
                <button type="submit" id="submit-btn" style="margin-top: 0;">Send Report</button>
            </form>
            <div class="auth-links" style="margin-top: 1.5rem;">
                <a href="/index">← Back to Chat</a>
            </div>
        </div>
        
        <!-- Copyright Footer -->
        <div style="text-align: center; margin-top: 2rem; color: var(--text-muted); font-size: 0.85rem;">
            Copyright &copy; 2026 Vibly
        </div>
    </div>

    <script>
        document.getElementById('contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submit-btn');
            const errorBox = document.getElementById('error-message');
            const successBox = document.getElementById('success-message');
            
            // UI Feedback
            btn.textContent = 'Sending securely...';
            btn.style.opacity = '0.7';
            btn.disabled = true;
            
            errorBox.style.display = 'none';
            successBox.style.display = 'none';

            const formData = new FormData(this);

            // Execute completely invisible stealth submission over AJAX API
            fetch('https://formspree.io/f/xwvrjejb', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                btn.textContent = 'Send Report';
                btn.style.opacity = '1';
                btn.disabled = false;
                
                if (response.ok) {
                    successBox.style.display = 'block';
                    document.getElementById('problem-input').value = '';
                } else {
                    response.json().then(data => {
                        if (Object.hasOwn(data, 'errors')) {
                            errorBox.textContent = data.errors.map(err => err.message).join(", ");
                        } else {
                            errorBox.textContent = "Oops! An error occurred submitting the report. Please try again.";
                        }
                        errorBox.style.display = 'block';
                    });
                }
            })
            .catch(error => {
                btn.textContent = 'Send Report';
                btn.style.opacity = '1';
                btn.disabled = false;
                errorBox.textContent = "Oops! A network error occurred.";
                errorBox.style.display = 'block';
            });
        });
    </script>
</body>
</html>
