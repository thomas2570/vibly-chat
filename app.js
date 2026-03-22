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
    const backBtn = document.getElementById('back-btn');
    const imageInput = document.getElementById('image-input');

    let ws;
    let username = typeof MY_USERNAME !== 'undefined' ? MY_USERNAME : "Anonymous";
    let targetUser = null;
    let onlineUsers = [];
    const profileCache = {}; // Cache for friend profile pictures

    function connect() {
        let wsUrl = '';
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            wsUrl = 'ws://localhost:8080';
        } else {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            wsUrl = protocol + '//' + window.location.host + '/ws';
        }
        
        ws = new WebSocket(wsUrl);

        ws.onopen = () => {
            statusEl.textContent = 'Connected';
            statusEl.className = 'status connected';
            ws.send(JSON.stringify({ type: 'auth', username: username }));
            searchUsers('');
        };

        ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                
                if (data.type === 'presence') {
                    onlineUsers = data.users;
                    updateUserListIndicators();
                } else if (data.type === 'chat') {
                    if (data.sender === targetUser) {
                        const timestamp = data.unix_time || data.created_at || Math.floor(Date.now() / 1000);
                        addMessage(data.message, 'incoming', data.sender, data.isImage, timestamp, data.is_read);
                        // Send read receipt actively
                        ws.send(JSON.stringify({ type: 'mark_read', target: data.sender }));
                    } else {
                        console.log(`Unread message from ${data.sender}:`, data.message);
                    }
                } else if (data.type === 'read_receipt') {
                    if (data.target === targetUser) {
                        document.querySelectorAll('.message-wrapper.outgoing .receipt').forEach(el => {
                            el.textContent = '✔✔';
                            el.classList.add('seen');
                        });
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
                    
                    if (onlineUsers.includes(u.username) && u.username !== username) {
                        const dot = document.createElement('span');
                        dot.className = 'online-dot';
                        li.appendChild(dot);
                    }

                    if (u.username === targetUser) {
                        li.classList.add('active');
                    }
                    li.addEventListener('click', () => selectUser(u.username, li));
                    userList.appendChild(li);
                });
            })
            .catch(err => console.error('Error fetching users:', err));
    }

    function updateUserListIndicators() {
        searchUsers(userSearch.value.trim());
    }

    function selectUser(selectedUsername, liElement) {
        targetUser = selectedUsername;
        
        document.querySelectorAll('.user-list li').forEach(el => el.classList.remove('active'));
        if (liElement) liElement.classList.add('active');
        
        chatTitle.textContent = selectedUsername;
        document.body.classList.add('chat-active');
        emptyState.style.display = 'none';
        messagesContainer.style.display = 'flex';
        chatInputArea.style.display = 'flex';
        
        messagesContainer.innerHTML = '';
        addSystemMessage(`Loading chat history with ${selectedUsername}...`);

        fetch('fetch_messages.php?target=' + encodeURIComponent(selectedUsername))
            .then(res => res.json())
            .then(messages => {
                messagesContainer.innerHTML = ''; 
                
                if (messages.length === 0) {
                    addSystemMessage(`Started private chat with ${selectedUsername}`);
                } else {
                    messages.forEach(m => {
                        const isOutgoing = (m.sender === username);
                        const type = isOutgoing ? 'outgoing' : 'incoming';
                        const isImage = parseInt(m.is_image) === 1;
                        addMessage(m.message, type, m.sender, isImage, m.unix_time || m.created_at, m.is_read || 0);
                    });
                }
                
                // Transmit read state immediately
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({ type: 'mark_read', target: selectedUsername }));
                }
            })
            .catch(err => {
                messagesContainer.innerHTML = '';
                addSystemMessage(`Started private chat with ${selectedUsername}`);
            });
    }

    if (backBtn) {
        backBtn.addEventListener('click', () => {
            document.body.classList.remove('chat-active');
            targetUser = null;
        });
    }

    userSearch.addEventListener('input', (e) => {
        searchUsers(e.target.value.trim());
    });

    function sendMessage() {
        const msg = messageInput.value.trim();
        if (msg && targetUser && ws && ws.readyState === WebSocket.OPEN) {
            const unixTime = Math.floor(Date.now() / 1000);
            const payload = JSON.stringify({ 
                type: 'chat', 
                target: targetUser, 
                message: msg,
                unix_time: unixTime 
            });
            ws.send(payload);
            
            addMessage(msg, 'outgoing', username, false, unixTime, 0);
            messageInput.value = '';
        }
    }

      // Image Upload Logic with HTML5 Canvas Compression
    imageInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            const rawImageBase64 = event.target.result;
            
            // Create an offscreen image to handle compression
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const MAX_WIDTH = 800; // Limit rendering width for massive speed improvement
                const scaleSize = MAX_WIDTH / img.width;
                canvas.width = MAX_WIDTH;
                canvas.height = img.height * scaleSize;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                
                // Compress out into a fast lightweight JPEG Base64 container
                const compressedBase64 = canvas.toDataURL('image/jpeg', 0.7);
                const unixTime = Math.floor(Date.now() / 1000);

                if (targetUser && ws && ws.readyState === WebSocket.OPEN) {
                    const payload = JSON.stringify({ 
                        type: 'chat', 
                        target: targetUser, 
                        message: compressedBase64,
                        isImage: true,
                        unix_time: unixTime
                    });
                    ws.send(payload);
                    addMessage(compressedBase64, 'outgoing', username, true, unixTime, 0);
                }
            };
            img.src = rawImageBase64;
            
            // Clear input so same image can be picked again
            imageInput.value = '';
        };
        reader.readAsDataURL(file);
    });

    function formatTime(timestampValue) {
        if (!timestampValue) return '';
        
        let jsTimestamp;
        // If it's a direct unix integer mathematical flag (like from TiDB or websocket)
        if (!isNaN(timestampValue) && String(timestampValue).indexOf('-') === -1) {
            jsTimestamp = parseInt(timestampValue) * 1000;
        } else {
            // Unlikely fallback if an old string timestamp comes through somehow
            const isoString = String(timestampValue).replace(' ', 'T') + 'Z';
            jsTimestamp = new Date(isoString).getTime();
        }
        
        const d = new Date(jsTimestamp);
        let hours = d.getHours();
        let mins = d.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        mins = mins < 10 ? '0'+mins : mins;
        return `${hours}:${mins} ${ampm}`;
    }

    function addMessage(text, type, senderName = '', isImage = false, timestamp = null, isRead = 0) {
        const msgWrapper = document.createElement('div');
        msgWrapper.classList.add('message-wrapper', type);

        // Container for Avatar + Message for incoming
        const innerFlex = document.createElement('div');
        innerFlex.style.display = 'flex';
        innerFlex.style.gap = '10px';
        innerFlex.style.alignItems = 'flex-end';
        
        if (type === 'incoming' && senderName) {
            const avatarImg = document.createElement('img');
            avatarImg.className = 'message-avatar';
            avatarImg.src = 'uploads/default.png'; // Fallback immediately
            
            // Load from cache or fetch
            if (profileCache[senderName]) {
                avatarImg.src = 'uploads/' + profileCache[senderName];
            } else {
                fetch(`get_profile.php?user=${encodeURIComponent(senderName)}`)
                    .then(r => r.json())
                    .then(d => {
                        if (d.profile_image) {
                            profileCache[senderName] = d.profile_image;
                            avatarImg.src = 'uploads/' + d.profile_image;
                        }
                    });
            }
            innerFlex.appendChild(avatarImg);
        }

        const msgContentCol = document.createElement('div');
        msgContentCol.style.display = 'flex';
        msgContentCol.style.flexDirection = 'column';

        if (type === 'incoming' && senderName) {
            const nameEl = document.createElement('div');
            nameEl.classList.add('sender-name');
            nameEl.textContent = senderName;
            msgContentCol.appendChild(nameEl);
        }

        const contentWrapper = document.createElement('div');
        
        if (isImage) {
            const imgEl = document.createElement('img');
            imgEl.src = text;
            imgEl.classList.add('message-image');
            contentWrapper.appendChild(imgEl);
        } else {
            contentWrapper.classList.add('message');
            contentWrapper.textContent = text;
        }

        // Add Timestamp and Checkmarks
        if (timestamp) {
            const timeStr = formatTime(timestamp);
            const metaEl = document.createElement('div');
            metaEl.classList.add('message-meta');
            
            let metaHtml = `<span class="message-time">${timeStr}</span>`;
            if (type === 'outgoing') {
                const tickMark = (parseInt(isRead) === 1) ? '✔✔' : '✔';
                const tickClass = (parseInt(isRead) === 1) ? 'receipt seen' : 'receipt';
                metaHtml += `<span class="${tickClass}">${tickMark}</span>`;
            }
            metaEl.innerHTML = metaHtml;
            contentWrapper.appendChild(metaEl);
        }
        
        msgContentCol.appendChild(contentWrapper);
        innerFlex.appendChild(msgContentCol);
        msgWrapper.appendChild(innerFlex);
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

    // Account Destruction Logic via Custom Modal
    const deleteBtn = document.getElementById('delete-account-btn');
const deleteModal = document.getElementById('delete-modal');
const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
const deletePasswordInput = document.getElementById('delete-password-input');
const deleteErrorMsg = document.getElementById('delete-error-msg');

// Profile modal elements
const profileBtn = document.getElementById('profile-settings-btn');
const profileModal = document.getElementById('profile-modal');
const cancelProfileBtn = document.getElementById('cancel-profile-btn');
const profileForm = document.getElementById('profile-form');
const profileStatusMsg = document.getElementById('profile-status-msg');
const myProfileImg = document.getElementById('my-profile-img');
const profileImageInput = document.getElementById('profile-image-input');
const previewProfileImg = document.getElementById('preview-profile-img');

// Load initial profile data
fetch('get_profile.php')
    .then(res => res.json())
    .then(data => {
        if (data.profile_image) {
            myProfileImg.src = 'uploads/' + data.profile_image;
            previewProfileImg.src = 'uploads/' + data.profile_image;
        }
        if (data.full_name) document.getElementById('profile-fullname').value = data.full_name;
        if (data.email) document.getElementById('profile-email').value = data.email;
        if (data.gender) document.getElementById('profile-gender').value = data.gender;
    });

// Profile Modal Event Listeners
profileBtn.addEventListener('click', () => {
    profileModal.style.display = 'flex';
    profileStatusMsg.style.display = 'none';
});

cancelProfileBtn.addEventListener('click', () => {
    profileModal.style.display = 'none';
});

profileImageInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewProfileImg.src = e.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

profileForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(profileForm);
    
    // Check if passwords are provided if we were doing password here, but we are not
    fetch('update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            profileStatusMsg.style.color = '#4ade80';
            profileStatusMsg.innerText = 'Profile updated successfully!';
            profileStatusMsg.style.display = 'block';
            if (data.profile_image) {
                myProfileImg.src = 'uploads/' + data.profile_image;
            }
            setTimeout(() => { profileModal.style.display = 'none'; }, 1500);
        } else {
            profileStatusMsg.style.color = '#ef4444';
            profileStatusMsg.innerText = data.error || 'Failed to update profile.';
            profileStatusMsg.style.display = 'block';
        }
    })
    .catch(err => {
        profileStatusMsg.style.color = '#ef4444';
        profileStatusMsg.innerText = 'Network error during save.';
        profileStatusMsg.style.display = 'block';
    });
});

    if (deleteBtn && deleteModal) {
        deleteBtn.addEventListener('click', () => {
            deleteModal.style.display = 'flex';
            deletePasswordInput.value = '';
            deleteErrorMsg.style.display = 'none';
            deletePasswordInput.focus();
        });

        cancelDeleteBtn.addEventListener('click', () => {
            deleteModal.style.display = 'none';
        });

        confirmDeleteBtn.addEventListener('click', () => {
            const pwd = deletePasswordInput.value.trim();
            if (pwd === '') {
                deleteErrorMsg.textContent = 'Password cannot be empty.';
                deleteErrorMsg.style.display = 'block';
                return;
            }
            
            confirmDeleteBtn.textContent = 'Deleting...';
            confirmDeleteBtn.disabled = true;

            fetch('delete_account.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password: pwd })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = 'login.php';
                } else {
                    deleteErrorMsg.textContent = data.message;
                    deleteErrorMsg.style.display = 'block';
                    confirmDeleteBtn.textContent = 'Delete Permanently';
                    confirmDeleteBtn.disabled = false;
                }
            })
            .catch(err => {
                deleteErrorMsg.textContent = 'Server network error occurred.';
                deleteErrorMsg.style.display = 'block';
                confirmDeleteBtn.textContent = 'Delete Permanently';
                confirmDeleteBtn.disabled = false;
            });
        });
    }

    connect();
});
