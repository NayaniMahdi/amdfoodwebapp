<?php
require_once __DIR__ . '/../includes/auth-check.php';
$pageTitle = 'Notifications';
$userId = getCurrentUserId();
$db = getDB();

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    header('Location: notifications.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">
        <div class="section-header">
            <h2 class="section-title">🔔 Notifications</h2>
            <?php if (count($notifications) > 0): ?>
            <a href="?mark_read=1" class="btn btn-secondary btn-sm">Mark all read</a>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
        <div class="glass-card empty-state" style="padding:64px">
            <span class="empty-icon">🔔</span>
            <h3>No Notifications</h3>
            <p>Start using Nouriq to receive personalized alerts and tips!</p>
        </div>
        <?php else: ?>
        <div class="stagger">
            <?php foreach ($notifications as $n): ?>
            <div class="glass-card notif-item <?php echo !$n['is_read'] ? 'unread' : ''; ?>" style="display:flex;gap:16px;padding:var(--space-lg);margin-bottom:8px;border-radius:var(--radius-md);cursor:default">
                <span style="font-size:28px;flex-shrink:0"><?php echo $n['icon']; ?></span>
                <div style="flex:1">
                    <div style="font-weight:600;margin-bottom:2px"><?php echo sanitize($n['title']); ?></div>
                    <div class="text-sm text-secondary"><?php echo sanitize($n['message']); ?></div>
                    <div class="text-xs text-secondary" style="margin-top:4px">
                        <span class="badge <?php echo $n['type'] === 'achievement' ? 'badge-accent' : ($n['type'] === 'behavior_alert' ? 'badge-warning' : 'badge-info'); ?>"><?php echo str_replace('_', ' ', $n['type']); ?></span>
                        &nbsp;<?php echo timeAgo($n['created_at']); ?>
                    </div>
                </div>
                <?php if (!$n['is_read']): ?>
                <span style="width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0;margin-top:8px"></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>
</body>
</html>
