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
    <div class="auth-container">
        <div class="auth-box">
            <h1>Register</h1>
            <p>Create a new Vibly account</p>
            <?php if($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST" class="auth-form">
                <input type="text" name="username" placeholder="Username" required autocomplete="off">
                <div style="position: relative; width: 100%; margin-bottom: 1rem;">
                    <input type="password" id="register-password" name="password" placeholder="Password" required style="width: 100%; margin-bottom: 0; padding-right: 40px;">
                    <span id="toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; opacity: 0.6; user-select: none;">👁️</span>
                </div>
                <button type="submit">Create Account</button>
            </form>
            <script>
                document.getElementById('toggle-password').addEventListener('click', function() {
                    const pwd = document.getElementById('register-password');
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
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
        
        <!-- Copyright Footer -->
        <div style="text-align: center; margin-top: 2rem; color: var(--text-muted); font-size: 0.85rem;">
            Copyright &copy; 2026 Vibly | <a href="contact.php" style="color: inherit; text-decoration: underline;">Contact Support</a>
        </div>
    </div>
</body>
</html>
