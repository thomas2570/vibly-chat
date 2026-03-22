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
    
    let lastRenderedDate = null;
    
    function getJsTimestamp(timestampValue) {
        if (!timestampValue) return Date.now();
        if (!isNaN(timestampValue) && String(timestampValue).indexOf('-') === -1) {
            return parseInt(timestampValue) * 1000;
        }
        const isoString = String(timestampValue).replace(' ', 'T') + 'Z';
        return new Date(isoString).getTime();
    }

    function formatDateDivider(jsTimestamp) {
        const date = new Date(jsTimestamp);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        if (date.toDateString() === today.toDateString()) {
            return 'Today';
        } else if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        } else {
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }
    
    let editingMessageId = null;
    let editingMessageSpan = null;
    let editingMessageWrapper = null;

    const DEFAULT_AVATAR = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI1MCIgZmlsbD0iIzIyMiIvPjxjaXJjbGUgY3g9IjUwIiBjeT0iMzkiIHI9IjIyIiBmaWxsPSIjNTU1Ii8+PHBhdGggZD0iTTIyLDkwIGMyNSwtMzAgMzEsLTMwIDU2LDAiIGZpbGw9IiM1NTUiLz48L3N2Zz4=';
    
    function getAvatar(val) {
        if (!val || val === 'default.png' || val === 'null') return DEFAULT_AVATAR;
        if (val.startsWith('data:')) return val;
        return 'uploads/' + val;
    }

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
                        addMessage(data.message, 'incoming', data.sender, data.isImage, timestamp, data.is_read, data.id);
                        // Send read receipt actively
                        ws.send(JSON.stringify({ type: 'mark_read', target: data.sender }));
                    } else {
                        let badge = document.querySelector(`.unread-badge[data-user="${data.sender}"]`);
                        if (badge) {
                            badge.style.display = 'inline-block';
                            badge.textContent = parseInt(badge.textContent || '0') + 1;
                            badge.style.marginLeft = 'auto';
                        } else {
                            searchUsers(document.getElementById('user-search').value.trim()); 
                        }
                    }
                } else if (data.type === 'ack_message') {
                    if (data.tempId && data.id) {
                        const el = document.querySelector(`.message-wrapper[data-tempid="${data.tempId}"]`);
                        if (el) {
                            el.dataset.id = data.id;
                            el.removeAttribute('data-tempid');
                        }
                    }
                } else if (data.type === 'edit') {
                    const el = document.querySelector(`.message-wrapper[data-id="${data.id}"]`);
                    if (el) {
                        const msgSpan = el.querySelector('.msg-text');
                        if (msgSpan) {
                            msgSpan.textContent = data.message;
                            el.classList.add('is-edited');
                        }
                    }
                } else if (data.type === 'delete') {
                    const el = document.querySelector(`.message-wrapper[data-id="${data.id}"]`);
                    if (el) el.remove();
                } else if (data.type === 'read_receipt') {
                    if (data.target === targetUser) {
                        document.querySelectorAll('.message-wrapper.outgoing .receipt').forEach(el => {
                            el.textContent = '✔✔';
                            el.classList.add('seen');
                        });
                    }
                }
            } catch (e) {
                console.error('Invalid message format:', event.data, e);
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
                    li.style.display = 'flex';
                    li.style.alignItems = 'center';
                    li.style.gap = '10px';
                    
                    const avatarSrc = getAvatar(u.profile_image);
                    const isOnline = (onlineUsers.includes(u.username) && u.username !== username);
                    const dotHtml = isOnline ? `<span class="online-dot" style="position:static; margin-left:auto; transform:none;"></span>` : '';
                    
                    const unreadCount = parseInt(u.unread_count || '0');
                    const badgeHtml = `<span class="unread-badge" data-user="${u.username}" style="${unreadCount > 0 ? 'display:inline-block;' : 'display:none;'} margin-left: ${isOnline ? '5px' : 'auto'};">${unreadCount}</span>`;

                    li.innerHTML = `
                        <img src="${avatarSrc}" onerror="this.src='${DEFAULT_AVATAR}'" style="width:32px; height:32px; border-radius:50%; object-fit:cover; flex-shrink:0;">
                        <span style="flex-grow:1; overflow:hidden; text-overflow:ellipsis;">${u.username}</span>
                        ${dotHtml}
                        ${badgeHtml}
                    `;
                    
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
        if (typeof cancelEditMode === 'function') cancelEditMode();
        
        targetUser = selectedUsername;
        
        document.querySelectorAll('.user-list li').forEach(el => el.classList.remove('active'));
        if (liElement) liElement.classList.add('active');
        
        let badge = document.querySelector(`.unread-badge[data-user="${selectedUsername}"]`);
        if (badge) badge.style.display = 'none';
        
        chatTitle.textContent = selectedUsername;
        document.body.classList.add('chat-active');
        emptyState.style.display = 'none';
        messagesContainer.style.display = 'flex';
        chatInputArea.style.display = 'flex';
        
        lastRenderedDate = null;
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
                        addMessage(m.message, type, m.sender, isImage, m.unix_time || m.created_at, m.is_read || 0, m.id, null, parseInt(m.is_edited) === 1);
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

    function cancelEditMode() {
        editingMessageId = null;
        editingMessageSpan = null;
        editingMessageWrapper = null;
        messageInput.value = '';
        sendBtn.textContent = 'Send';
        sendBtn.style.background = 'var(--primary-gradient)';
        const btn = document.getElementById('cancel-edit-btn');
        if (btn) btn.style.display = 'none';
    }

    function sendMessage() {
        const msg = messageInput.value.trim();
        if (!msg) return;
        
        if (editingMessageId) {
            if (ws && ws.readyState === WebSocket.OPEN) {
                if (editingMessageSpan && msg === editingMessageSpan.textContent) {
                    cancelEditMode();
                    return;
                }
                ws.send(JSON.stringify({ type: 'edit', id: editingMessageId, message: msg, target: targetUser }));
                if (editingMessageSpan) editingMessageSpan.textContent = msg;
                if (editingMessageWrapper) editingMessageWrapper.classList.add('is-edited');
                cancelEditMode();
            } else {
                alert("Cannot connect to server to edit.");
            }
            return;
        }

        if (targetUser && ws && ws.readyState === WebSocket.OPEN) {
            const unixTime = Math.floor(Date.now() / 1000);
            const tempId = 'temp_' + Date.now() + '_' + Math.floor(Math.random()*1000);
            const payload = JSON.stringify({ 
                type: 'chat', 
                target: targetUser, 
                message: msg,
                unix_time: unixTime,
                tempId: tempId
            });
            ws.send(payload);
            
            addMessage(msg, 'outgoing', username, false, unixTime, 0, null, tempId);
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
        
        const jsTimestamp = getJsTimestamp(timestampValue);
        const d = new Date(jsTimestamp);
        let hours = d.getHours();
        let mins = d.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        mins = mins < 10 ? '0'+mins : mins;
        return `${hours}:${mins} ${ampm}`;
    }

    function addMessage(text, type, senderName = '', isImage = false, timestamp = null, isRead = 0, dbId = null, tempId = null, isEdited = false) {
        if (timestamp) {
            const jsTs = getJsTimestamp(timestamp);
            const dateStr = formatDateDivider(jsTs);
            if (dateStr !== lastRenderedDate) {
                const wrap = document.createElement('div');
                wrap.className = 'date-divider-wrapper';
                const div = document.createElement('div');
                div.className = 'date-divider';
                div.textContent = dateStr;
                wrap.appendChild(div);
                messagesContainer.appendChild(wrap);
                lastRenderedDate = dateStr;
            }
        }
        
        const msgWrapper = document.createElement('div');
        msgWrapper.classList.add('message-wrapper', type);
        if (dbId) msgWrapper.dataset.id = dbId;
        if (tempId) msgWrapper.dataset.tempid = tempId;
        if (isEdited) msgWrapper.classList.add('is-edited');

        // Container for Avatar + Message for incoming
        const innerFlex = document.createElement('div');
        innerFlex.style.display = 'flex';
        innerFlex.style.gap = '10px';
        innerFlex.style.alignItems = 'flex-end';
        
        if (type === 'incoming' && senderName) {
            const avatarImg = document.createElement('img');
            avatarImg.className = 'message-avatar';
            avatarImg.src = DEFAULT_AVATAR; // Fallback immediately
            
            // Load from cache or fetch
            if (profileCache[senderName]) {
                avatarImg.src = getAvatar(profileCache[senderName]);
            } else {
                fetch(`get_profile.php?user=${encodeURIComponent(senderName)}`)
                    .then(r => r.json())
                    .then(d => {
                        if (d.profile_image) {
                            profileCache[senderName] = d.profile_image;
                            avatarImg.src = getAvatar(d.profile_image);
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
            const txtNode = document.createElement('span');
            txtNode.className = 'msg-text';
            txtNode.textContent = text;
            contentWrapper.appendChild(txtNode);
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

        if (type === 'outgoing' && !isImage) {
            const actionsEl = document.createElement('div');
            actionsEl.className = 'msg-actions';
            
            let canEdit = true;
            if (timestamp) {
                const currentTime = Math.floor(Date.now() / 1000);
                if (currentTime - timestamp > 120) {
                    canEdit = false;
                }
            }

            if (canEdit) {
                const editBtn = document.createElement('button');
                editBtn.innerHTML = '✎';
                editBtn.title = 'Edit';
                editBtn.onclick = () => {
                    const actualId = msgWrapper.dataset.id;
                    if (!actualId) {
                        alert("Message is still sending, please wait a moment.");
                        return;
                    }
                    
                    const msgSpan = contentWrapper.querySelector('.msg-text');
                    const currentText = msgSpan ? msgSpan.textContent : '';
                    
                    messageInput.value = currentText;
                    messageInput.focus();
                    
                    editingMessageId = actualId;
                    editingMessageSpan = msgSpan;
                    editingMessageWrapper = msgWrapper;
                    
                    sendBtn.textContent = 'Save';
                    sendBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    
                    let cancelEditBtn = document.getElementById('cancel-edit-btn');
                    if (!cancelEditBtn) {
                        cancelEditBtn = document.createElement('button');
                        cancelEditBtn.id = 'cancel-edit-btn';
                        cancelEditBtn.innerHTML = '✕';
                        cancelEditBtn.title = 'Cancel Edit';
                        cancelEditBtn.style.background = 'rgba(239, 68, 68, 0.15)';
                        cancelEditBtn.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                        cancelEditBtn.style.color = '#fca5a5';
                        cancelEditBtn.style.padding = '0 15px';
                        cancelEditBtn.style.borderRadius = '28px';
                        cancelEditBtn.style.cursor = 'pointer';
                        cancelEditBtn.style.marginLeft = '5px';
                        cancelEditBtn.style.marginRight = '5px';
                        cancelEditBtn.onclick = cancelEditMode;
                        
                        const chatInputArea = document.getElementById('chat-input-area');
                        chatInputArea.insertBefore(cancelEditBtn, sendBtn);
                    }
                    cancelEditBtn.style.display = 'block';
                };
                actionsEl.appendChild(editBtn);
                
                if (timestamp) {
                    const timeRemaining = 120 - (Math.floor(Date.now() / 1000) - timestamp);
                    if (timeRemaining > 0) {
                        setTimeout(() => {
                            if (editBtn && editBtn.parentNode) editBtn.remove();
                        }, timeRemaining * 1000);
                    }
                }
            }
            
            const delBtn = document.createElement('button');
            delBtn.innerHTML = '✕';
            delBtn.title = 'Delete';
            delBtn.onclick = () => {
                if (confirm('Delete this message?')) {
                    const actualId = msgWrapper.dataset.id;
                    if (actualId && ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({ type: 'delete', id: actualId, target: targetUser }));
                        msgWrapper.remove();
                    } else if (!actualId) {
                        alert("Message is still sending, please wait a moment.");
                    }
                }
            };
            
            actionsEl.appendChild(delBtn);
            contentWrapper.appendChild(actionsEl);
        }
        
        msgContentCol.appendChild(contentWrapper);
        if (type === 'incoming') {
            innerFlex.appendChild(msgContentCol);
            msgWrapper.appendChild(innerFlex);
        } else {
            msgWrapper.appendChild(msgContentCol);
        }
        
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
    const modalDeleteBtn = document.getElementById('modal-delete-account-btn');
const deleteModal = document.getElementById('delete-modal');
const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
const deletePasswordInput = document.getElementById('delete-password-input');
const deleteErrorMsg = document.getElementById('delete-error-msg');

// Profile modal elements
const profileBtns = document.querySelectorAll('.trigger-profile-modal');
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
            myProfileImg.src = getAvatar(data.profile_image);
            previewProfileImg.src = getAvatar(data.profile_image);
        } else {
            myProfileImg.src = DEFAULT_AVATAR;
            previewProfileImg.src = DEFAULT_AVATAR;
        }
        if (data.full_name) document.getElementById('profile-fullname').value = data.full_name;
        if (data.email) document.getElementById('profile-email').value = data.email;
        if (data.gender) document.getElementById('profile-gender').value = data.gender;
    });

// Profile Modal Event Listeners
profileBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        profileModal.style.display = 'flex';
        profileStatusMsg.style.display = 'none';
    });
});

cancelProfileBtn.addEventListener('click', () => {
    profileModal.style.display = 'none';
});

let currentProfileBase64 = null;
profileImageInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const MAX_WIDTH = 250;
                const scaleSize = MAX_WIDTH / img.width;
                canvas.width = MAX_WIDTH;
                canvas.height = img.height * scaleSize;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                currentProfileBase64 = canvas.toDataURL('image/jpeg', 0.85);
                previewProfileImg.src = currentProfileBase64;
            };
            img.src = e.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

profileForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(profileForm);
    formData.delete('profile_image'); // Remove the raw file payload
    if (currentProfileBase64) {
        formData.append('profile_image_base64', currentProfileBase64);
    }
    
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
                myProfileImg.src = getAvatar(data.profile_image);
            } else if (currentProfileBase64) {
                myProfileImg.src = currentProfileBase64;
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

    const triggerDeleteModal = () => {
        deleteModal.style.display = 'flex';
        deletePasswordInput.value = '';
        deleteErrorMsg.style.display = 'none';
        deletePasswordInput.focus();
        if (profileModal) profileModal.style.display = 'none'; // Ensure profile modal turns off if moving to delete
    };

    if (deleteBtn && deleteModal) {
        deleteBtn.addEventListener('click', triggerDeleteModal);
    }
    if (modalDeleteBtn && deleteModal) {
        modalDeleteBtn.addEventListener('click', triggerDeleteModal);
    }
    
    if (cancelDeleteBtn && deleteModal) {
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
