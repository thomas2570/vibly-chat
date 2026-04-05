"""Script to update the user list rendering in app.js to use indicators-wrap layout."""
import re

with open('app.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Normalize line endings
content = content.replace('\r\n', '\n').replace('\r', '\n')

# Find and replace the forEach block using a regex
old_pattern = re.compile(
    r"users\.forEach\(u => \{.*?userList\.appendChild\(li\);\s*\}\);",
    re.DOTALL
)

new_block = """users.forEach(u => {
                    const li = document.createElement('li');
                    li.dataset.user = u.username;

                    const avatarSrc = getAvatar(u.profile_image);
                    const isOnline = (onlineUsers.includes(u.username) && u.username !== username);
                    const unreadCount = parseInt(u.unread_count || '0');

                    const dotHtml = isOnline
                        ? '<span class="online-dot" style="position:static;transform:none;flex-shrink:0;"></span>'
                        : '';
                    const badgeStr = unreadCount > 99 ? '99+' : String(unreadCount);
                    const badgeHtml = '<span class="unread-badge" data-user="' + u.username + '" style="display:' + (unreadCount > 0 ? 'inline-flex' : 'none') + ';">' + badgeStr + '</span>';

                    li.innerHTML =
                        '<img src="' + avatarSrc + '" onerror="this.src=\\''+DEFAULT_AVATAR+'\\'" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">' +
                        '<span class="user-name-text" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + u.username + '</span>' +
                        '<span class="indicators-wrap" style="display:flex;align-items:center;gap:6px;flex-shrink:0;">' + dotHtml + badgeHtml + '</span>';

                    if (u.username === targetUser) li.classList.add('active');
                    li.addEventListener('click', () => selectUser(u.username, li));
                    userList.appendChild(li);
                });"""

match = old_pattern.search(content)
if match:
    content_new = content[:match.start()] + new_block + content[match.end():]
    with open('app.js', 'w', encoding='utf-8', newline='\n') as f:
        f.write(content_new)
    print('SUCCESS: forEach block replaced')
else:
    print('FAIL: forEach block not found')
    idx = content.find('users.forEach')
    print(f'forEach found at: {idx}')
    if idx >= 0:
        print(repr(content[idx:idx+300]))
