/**
 * Nouriq — Core App JavaScript
 * Utilities, API helpers, Toast system, Modal system
 */

const APP_URL = window.location.origin + '/amdfoodapp';
const API_URL = APP_URL + '/api';

// ============================================================
// API HELPER
// ============================================================
class NouriqAPI {
    static async get(endpoint) {
        try {
            const response = await fetch(API_URL + endpoint);
            return await response.json();
        } catch (error) {
            console.error('API GET Error:', error);
            return { success: false, message: 'Network error' };
        }
    }

    static async post(endpoint, data = {}) {
        try {
            const formData = new FormData();
            for (const [key, value] of Object.entries(data)) {
                formData.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
            }
            const response = await fetch(API_URL + endpoint, {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('API POST Error:', error);
            return { success: false, message: 'Network error' };
        }
    }
}

// ============================================================
// TOAST NOTIFICATION SYSTEM
// ============================================================
class Toast {
    static show(title, message = '', type = 'info', duration = 4000) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || '🔔'}</span>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                ${message ? `<div class="toast-message">${message}</div>` : ''}
            </div>
            <button class="toast-close" onclick="this.closest('.toast').remove()">✕</button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    static success(title, message = '') { this.show(title, message, 'success'); }
    static error(title, message = '') { this.show(title, message, 'error'); }
    static warning(title, message = '') { this.show(title, message, 'warning'); }
    static info(title, message = '') { this.show(title, message, 'info'); }
}

// ============================================================
// MODAL SYSTEM
// ============================================================
class Modal {
    static open(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    static close(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
}

// Close modals on overlay click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// ============================================================
// SIDEBAR TOGGLE (Mobile)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuBtn = document.getElementById('mobileMenuBtn');

    if (menuBtn) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // Loading screen
    const loadingScreen = document.getElementById('loadingScreen');
    if (loadingScreen) {
        setTimeout(() => {
            loadingScreen.classList.add('fade-out');
            setTimeout(() => loadingScreen.remove(), 500);
        }, 800);
    }
});

// ============================================================
// UTILITY FUNCTIONS
// ============================================================
function formatNumber(num) {
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return Math.round(num).toLocaleString();
}

function formatCalories(cal) {
    return Math.round(cal).toLocaleString();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function animateValue(element, start, end, duration = 1000) {
    const range = end - start;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        const current = Math.round(start + range * eased);
        element.textContent = current.toLocaleString();
        if (progress < 1) requestAnimationFrame(update);
    }
    
    requestAnimationFrame(update);
}

// Skeleton loader helper
function showSkeleton(container, count = 3) {
    let html = '';
    for (let i = 0; i < count; i++) {
        html += '<div class="skeleton skeleton-card" style="height:80px;margin-bottom:8px;"></div>';
    }
    container.innerHTML = html;
}

// Date formatting
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    if (diff < 604800000) return Math.floor(diff / 86400000) + 'd ago';
    
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// Escape HTML
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
