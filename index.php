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
        <!-- Global Profile Button (Top Right Corner) -->
        <button class="global-profile-btn trigger-profile-modal" title="Profile Settings">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </button>

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
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <img id="my-profile-img" src="uploads/default.png" alt="Profile" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; background-color: var(--border-color);">
                        <span class="logged-in-user">Me: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                    </div>
                    <div style="display: none; gap: 8px;">
                        <!-- Buttons moved to modal -->
                    </div>
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
                        <span id="connection-status" class="status disconnected" style="display: none;">Disconnected</span>
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

    <!-- Delete Account Modal -->
    <div id="delete-modal" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <h2>Delete Account</h2>
            <p><strong>Critical Action:</strong> Enter your password to completely delete your account. This will permanently erase you and your messages.</p>
            <input type="password" id="delete-password-input" placeholder="Enter your password">
            <div class="modal-actions">
                <button id="cancel-delete-btn" class="cancel-btn">Cancel</button>
                <button id="confirm-delete-btn" class="danger-btn">Delete Permanently</button>
            </div>
            <div id="delete-error-msg" class="error-msg" style="display: none; margin-top: 15px;"></div>
        </div>
    </div>

    <!-- Profile Settings Modal -->
    <div id="profile-modal" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <h2>Profile Settings</h2>
            <form id="profile-form" enctype="multipart/form-data">
                <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 1rem;">
                    <img id="preview-profile-img" src="uploads/default.png" alt="Profile" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 2px solid var(--primary-color);">
                    <input type="file" id="profile-image-input" name="profile_image" accept="image/*" style="font-size: 0.8rem;">
                </div>
                
                <label style="display: block; text-align: left; font-size: 0.85rem; margin-bottom: 4px; color: var(--text-muted);">Full Name</label>
                <input type="text" id="profile-fullname" name="full_name" placeholder="John Doe">
                
                <label style="display: block; text-align: left; font-size: 0.85rem; margin-bottom: 4px; color: var(--text-muted);">Email Address (For Password Reset)</label>
                <input type="email" id="profile-email" name="email" placeholder="john@example.com">
                
                <label style="display: block; text-align: left; font-size: 0.85rem; margin-bottom: 4px; color: var(--text-muted);">Gender</label>
                <select id="profile-gender" name="gender" style="width: 100%; padding: 12px; margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: rgba(0, 0, 0, 0.2); color: var(--text-light); font-family: 'Inter', sans-serif;">
                    <option value="" style="color: black;">Prefer not to say</option>
                    <option value="Male" style="color: black;">Male</option>
                    <option value="Female" style="color: black;">Female</option>
                    <option value="Other" style="color: black;">Other</option>
                </select>

                <div class="modal-actions">
                    <button type="button" id="cancel-profile-btn" class="cancel-btn">Cancel</button>
                    <button type="submit" id="save-profile-btn" style="background: var(--primary-color); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer;">Save Changes</button>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">
                <div style="display: flex; gap: 10px; justify-content: space-between;">
                    <button type="button" id="modal-delete-account-btn" style="background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 0.6rem; cursor: pointer; flex: 1; font-size: 0.85rem; transition: background 0.2s;">Delete Account</button>
                    <a href="logout.php" class="cancel-btn" style="text-decoration: none; text-align: center; flex: 1; font-size: 0.85rem; display: flex; align-items: center; justify-content: center;">Logout</a>
                </div>
            </form>
            <div id="profile-status-msg" style="display: none; margin-top: 15px; font-size: 0.9rem; text-align: center;"></div>
        </div>
    </div>

    <script>
        // Inject the PHP session username into a global JS variable securely
        const MY_USERNAME = <?php echo json_encode($_SESSION['username']); ?>;
    </script>
    <script src="app.js"></script>
</body>
</html>
