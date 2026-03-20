document.addEventListener('DOMContentLoaded', () => {
    const messagesContainer = document.getElementById('chat-messages');
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const statusEl = document.getElementById('connection-status');
    const userSearch = document.getElementById('user-search');
    const userList = document.getElementById('user-list');
    const chatTitle = document.getElementById('chat-title');
    const emptyState = document.getElementById('empty-state');
    const chatInputArea = document.getElementById('chat-input-area');

    let ws;
    let username = typeof MY_USERNAME !== 'undefined' ? MY_USERNAME : "Anonymous";
    let targetUser = null;

    function connect() {
        // Automatically determine if we are running locally on Windows or Live on Render
        let wsUrl = '';
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            // Local XAMPP Testing
            wsUrl = 'ws://localhost:8080';
        } else {
            // Render / Cloud Deployment
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            wsUrl = protocol + '//' + window.location.host + '/ws';
        }
        
        ws = new WebSocket(wsUrl);

        ws.onopen = () => {
            statusEl.textContent = 'Connected';
            statusEl.className = 'status connected';
            // Register session with WebSocket server
            ws.send(JSON.stringify({ type: 'auth', username: username }));
            
            // Load some users initially
            searchUsers('');
        };

        ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                
                if (data.type === 'chat') {
                    // Only show message if we are actively chatting with the sender
                    if (data.sender === targetUser) {
                        addMessage(data.message, 'incoming', data.sender);
                    } else {
                        // Normally you'd want a notification badge here
                        console.log(`Unread message from ${data.sender}:`, data.message);
                    }
                }
            } catch (e) {
                console.error('Invalid message format:', event.data);
            }
        };

        ws.onclose = () => {
            statusEl.textContent = 'Disconnected';
            statusEl.className = 'status disconnected';
            setTimeout(connect, 3000);
        };

        ws.onerror = (err) => {
            console.error('WebSocket error:', err);
        };
    }

    function searchUsers(query) {
        fetch(`search_users.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(users => {
                userList.innerHTML = '';
                if (users.length === 0 && query !== '') {
                    userList.innerHTML = '<li style="color: var(--text-muted); text-align: center; cursor: default;">No users found</li>';
                    return;
                }
                
                users.forEach(u => {
                    const li = document.createElement('li');
                    li.textContent = u.username;
                    if (u.username === targetUser) {
                        li.classList.add('active');
                    }
                    li.addEventListener('click', () => selectUser(u.username, li));
                    userList.appendChild(li);
                });
            })
            .catch(err => console.error('Error fetching users:', err));
    }

    function selectUser(selectedUsername, liElement) {
        targetUser = selectedUsername;
        
        // Update UI selections
        document.querySelectorAll('.user-list li').forEach(el => el.classList.remove('active'));
        if (liElement) liElement.classList.add('active');
        
        chatTitle.textContent = selectedUsername;
        
        // Switch views
        emptyState.style.display = 'none';
        messagesContainer.style.display = 'flex';
        chatInputArea.style.display = 'flex';
        
        // Clear old messages (since they aren't persisted in DB right now)
        messagesContainer.innerHTML = '';
        addSystemMessage(`Started private chat with ${selectedUsername}`);
    }

    userSearch.addEventListener('input', (e) => {
        searchUsers(e.target.value.trim());
    });

    function sendMessage() {
        const msg = messageInput.value.trim();
        if (msg && targetUser && ws && ws.readyState === WebSocket.OPEN) {
            // Send payload for targeted routing
            const payload = JSON.stringify({ 
                type: 'chat', 
                target: targetUser, 
                message: msg 
            });
            ws.send(payload);
            
            // Display our outgoing message
            addMessage(msg, 'outgoing', username);
            messageInput.value = '';
        }
    }

    function addMessage(text, type, senderName = '') {
        const msgWrapper = document.createElement('div');
        msgWrapper.classList.add('message-wrapper', type);

        if (type === 'incoming' && senderName) {
            const nameEl = document.createElement('div');
            nameEl.classList.add('sender-name');
            nameEl.textContent = senderName;
            msgWrapper.appendChild(nameEl);
        }

        const msgEl = document.createElement('div');
        msgEl.classList.add('message');
        msgEl.textContent = text;
        
        msgWrapper.appendChild(msgEl);
        messagesContainer.appendChild(msgWrapper);
        scrollToBottom();
    }

    function addSystemMessage(text) {
        const msgEl = document.createElement('div');
        msgEl.classList.add('message-system');
        msgEl.textContent = text;
        messagesContainer.appendChild(msgEl);
        scrollToBottom();
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    sendBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    // Initial connection
    connect();
});
