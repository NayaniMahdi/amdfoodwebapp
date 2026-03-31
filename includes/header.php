<?php
/**
 * Nouriq — Shared Header (Sidebar + Top Bar)
 * Include this in all dashboard pages
 */
$currentUser = getCurrentUser();
$currentProfile = getUserProfile($currentUser['id']);
$displayName = getDisplayName($currentUser);
$initials = strtoupper(substr($currentUser['first_name'] ?? $currentUser['username'], 0, 1));
$notifCount = getUnreadNotificationCount($currentUser['id']);
$userPoints = getUserPoints($currentUser['id']);

// Determine current page for nav active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Loading Screen -->
<div class="loading-screen" id="loadingScreen">
    <div class="loading-logo">Nouriq</div>
    <div class="loading-bar"><div class="loading-bar-fill"></div></div>
</div>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-icon">🧬</div>
        <span class="sidebar-logo-text">Nouriq</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="<?php echo APP_URL; ?>/dashboard/" class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="<?php echo APP_URL; ?>/dashboard/food-log.php" class="nav-item <?php echo $currentPage === 'food-log' ? 'active' : ''; ?>">
                <span class="nav-icon">🍱</span> Food Log
            </a>
            <a href="<?php echo APP_URL; ?>/dashboard/recommendations.php" class="nav-item <?php echo $currentPage === 'recommendations' ? 'active' : ''; ?>">
                <span class="nav-icon">🧠</span> Recommendations
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Analysis</div>
            <a href="<?php echo APP_URL; ?>/dashboard/habits.php" class="nav-item <?php echo $currentPage === 'habits' ? 'active' : ''; ?>">
                <span class="nav-icon">📈</span> Habits & Insights
            </a>
            <a href="<?php echo APP_URL; ?>/dashboard/calculators.php" class="nav-item <?php echo $currentPage === 'calculators' ? 'active' : ''; ?>">
                <span class="nav-icon">🧮</span> Calculators
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Rewards</div>
            <a href="<?php echo APP_URL; ?>/dashboard/achievements.php" class="nav-item <?php echo $currentPage === 'achievements' ? 'active' : ''; ?>">
                <span class="nav-icon">🏆</span> Achievements
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="<?php echo APP_URL; ?>/dashboard/profile.php" class="nav-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                <span class="nav-icon">👤</span> Profile
            </a>
            <a href="<?php echo APP_URL; ?>/dashboard/notifications.php" class="nav-item <?php echo $currentPage === 'notifications' ? 'active' : ''; ?>">
                <span class="nav-icon">🔔</span> Notifications
                <?php if ($notifCount > 0): ?>
                <span class="nav-badge"><?php echo $notifCount > 99 ? '99+' : $notifCount; ?></span>
                <?php endif; ?>
            </a>
            <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="<?php echo APP_URL; ?>/admin/" class="nav-item">
                <span class="nav-icon">⚙️</span> Admin Panel
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo APP_URL; ?>/dashboard/profile.php" class="sidebar-user" style="text-decoration:none">
            <div class="sidebar-avatar"><?php echo $initials; ?></div>
            <div class="sidebar-user-info">
                <div class="user-name"><?php echo sanitize($displayName); ?></div>
                <div class="user-level">Level <?php echo $userPoints['level']; ?> • <?php echo number_format($userPoints['total_points']); ?> pts</div>
            </div>
        </a>
    </div>
</aside>

<!-- Main Content Wrapper -->
<div class="main-content">
    <!-- Top Bar -->
    <header class="top-bar">
        <div class="top-bar-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle menu">☰</button>
            <h1 class="page-title" id="pageTitle"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
        </div>
        <div class="top-bar-right">
            <button class="top-bar-btn" id="themeToggle" title="Toggle theme">🌙</button>
            <div style="position:relative">
                <button class="top-bar-btn" id="notifBtn" title="Notifications">
                    🔔
                    <?php if ($notifCount > 0): ?>
                    <span class="notif-dot"></span>
                    <?php endif; ?>
                </button>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-dropdown-header">
                        <h4>Notifications</h4>
                        <button class="btn btn-ghost btn-sm" id="markAllRead">Mark all read</button>
                    </div>
                    <div id="notifList">
                        <div class="empty-state" style="padding:32px">
                            <span style="font-size:32px">🔔</span>
                            <p class="text-sm text-secondary" style="margin-top:8px">No notifications</p>
                        </div>
                    </div>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>/auth/logout.php" class="top-bar-btn" title="Sign Out">🚪</a>
        </div>
    </header>
