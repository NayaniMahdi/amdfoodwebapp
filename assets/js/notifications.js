/**
 * Nouriq — Notification System
 */
document.addEventListener('DOMContentLoaded', () => {
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const markAllBtn = document.getElementById('markAllRead');
    
    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
            if (notifDropdown.classList.contains('active')) {
                loadNotifications();
            }
        });
        
        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && e.target !== notifBtn) {
                notifDropdown.classList.remove('active');
            }
        });
    }
    
    if (markAllBtn) {
        markAllBtn.addEventListener('click', async () => {
            const result = await NouriqAPI.post('/notifications.php', { action: 'mark_all_read' });
            if (result.success) {
                loadNotifications();
                const dot = document.querySelector('.notif-dot');
                if (dot) dot.remove();
                const navBadge = document.querySelector('.nav-item .nav-badge');
                if (navBadge) navBadge.remove();
            }
        });
    }
});

async function loadNotifications() {
    const list = document.getElementById('notifList');
    if (!list) return;
    
    list.innerHTML = '<div style="padding:24px;text-align:center"><div class="spinner spinner-sm" style="margin:0 auto"></div></div>';
    
    const result = await NouriqAPI.get('/notifications.php?limit=10');
    
    if (result.success && result.data && result.data.length > 0) {
        list.innerHTML = result.data.map(n => `
            <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" onclick="markNotificationRead(${n.id}, this)">
                <span class="notif-icon">${n.icon || '🔔'}</span>
                <div class="notif-text">
                    <div class="notif-title">${escapeHtml(n.title)}</div>
                    <div class="notif-msg">${escapeHtml(n.message)}</div>
                    <div class="notif-time">${formatDate(n.created_at)}</div>
                </div>
            </div>
        `).join('');
    } else {
        list.innerHTML = `
            <div class="empty-state" style="padding:32px">
                <span style="font-size:32px">🔔</span>
                <p class="text-sm text-secondary" style="margin-top:8px">No notifications yet</p>
            </div>
        `;
    }
}

async function markNotificationRead(id, element) {
    await NouriqAPI.post('/notifications.php', { action: 'mark_read', id: id });
    if (element) element.classList.remove('unread');
}
