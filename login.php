<?php
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM chatbot WHERE username = ?");
    $stmt->execute([$user]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($account && password_verify($pass, $account['password'])) {
        $_SESSION['username'] = $account['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vibly</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Welcome to Vibly</h1>
            <p>Please log in to your account</p>
            <?php if($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST" class="auth-form">
                <input type="text" name="username" placeholder="Username" required autocomplete="off">
                <div style="position: relative; width: 100%; margin-bottom: 1rem;">
                    <input type="password" id="login-password" name="password" placeholder="Password" required style="width: 100%; margin-bottom: 0; padding-right: 40px;">
                    <span id="toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; opacity: 0.6; user-select: none;">👁️</span>
                </div>
                <button type="submit">Sign In</button>
            </form>
            <script>
                document.getElementById('toggle-password').addEventListener('click', function() {
                    const pwd = document.getElementById('login-password');
                    if (pwd.type === 'password') {
                        pwd.type = 'text';
                        this.textContent = '🙈';
                        this.style.opacity = '1';
                    } else {
                        pwd.type = 'password';
                        this.textContent = '👁️';
                        this.style.opacity = '0.6';
                    }
                });
            </script>
            <div class="auth-links">
                Don't have an account? <a href="register.php">Create one</a>
            </div>
        </div>
        
        <!-- Copyright Footer -->
        <div style="text-align: center; margin-top: 2rem; color: var(--text-muted); font-size: 0.85rem;">
            Copyright &copy; 2026 Vibly | <a href="contact.php" style="color: inherit; text-decoration: underline;">Contact Support</a>
        </div>
    </div>
</body>
</html>
