/**
 * Nouriq — Theme Toggle (Dark/Light)
 */
(function() {
    const STORAGE_KEY = 'nouriq_theme';
    
    function getPreferredTheme() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) return stored;
        return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    }
    
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        
        const btn = document.getElementById('themeToggle');
        if (btn) btn.textContent = theme === 'dark' ? '🌙' : '☀️';
    }
    
    // Apply theme immediately
    setTheme(getPreferredTheme());
    
    // Toggle button
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('themeToggle');
        if (btn) {
            btn.textContent = getPreferredTheme() === 'dark' ? '🌙' : '☀️';
            btn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme');
                setTheme(current === 'dark' ? 'light' : 'dark');
            });
        }
    });
})();
