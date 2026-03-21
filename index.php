<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vibly</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand">
                    <h2>Vibly</h2>
                </div>
                <div class="search-box-container">
                    <input type="text" id="user-search" placeholder="Search for users..." autocomplete="off">
                </div>
            </div>
            <ul id="user-list" class="user-list">
                <!-- Search results will populate here -->
            </ul>
            <div class="sidebar-footer" style="flex-direction: column; gap: 0.75rem; align-items: flex-start;">
                <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                    <span class="logged-in-user">Me: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                    <a href="logout.php" class="logout-link">Logout</a>
                </div>
                <div style="width: 100%; text-align: center; font-size: 0.75rem; color: var(--text-muted);">
                    Copyright &copy; 2026 Vibly | <a href="contact.php" style="color: inherit; text-decoration: underline;">Report a Problem</a>
                </div>
            </div>
        </aside>

        <!-- Main Chat Area -->
        <div class="main-chat">
            <header class="chat-header">
                <div class="header-info">
                    <button id="back-btn" class="mobile-only" style="display: none;">⬅️</button>
                    <div>
                        <h1 id="chat-title">Select a conversation</h1>
                        <span id="connection-status" class="status disconnected">Disconnected</span>
                    </div>
                </div>
            </header>

            <!-- Shown when chatting -->
            <div class="chat-messages" id="chat-messages" style="display: none;">
                <!-- Messages will appear here -->
            </div>
            <div class="chat-input-area" id="chat-input-area" style="display: none;">
                <label for="image-input" class="image-upload-btn">📎</label>
                <input type="file" id="image-input" accept="image/*" style="display: none;">
                <input type="text" id="message-input" placeholder="Type a message..." autocomplete="off">
                <button id="send-btn">Send</button>
            </div>

            <!-- Shown when NO chat is selected -->
            <div class="empty-state" id="empty-state">
                <p>Search for a user on the left to start private chatting!</p>
            </div>
        </div>
    </div>

    <script>
        // Inject the PHP session username into a global JS variable securely
        const MY_USERNAME = <?php echo json_encode($_SESSION['username']); ?>;
    </script>
    <script src="app.js"></script>
</body>
</html>
