<?php
/**
 * Nouriq — Admin Panel
 */
require_once __DIR__ . '/../includes/admin-check.php';
$pageTitle = 'Admin Panel';

$db = getDB();

// Stats
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLogs = $db->query("SELECT COUNT(*) FROM food_logs")->fetchColumn();
$totalFoods = $db->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
$todayLogs = $db->prepare("SELECT COUNT(*) FROM food_logs WHERE DATE(logged_at) = CURDATE()");
$todayLogs->execute();
$todayLogs = $todayLogs->fetchColumn();

// Recent users
$recentUsers = $db->query("SELECT u.*, p.first_name, p.last_name FROM users u LEFT JOIN profiles p ON u.id = p.user_id ORDER BY u.created_at DESC LIMIT 10")->fetchAll();

// Top foods
$topFoods = $db->query("SELECT fi.name, fi.category, COUNT(fl.id) as log_count FROM food_items fi LEFT JOIN food_logs fl ON fi.id = fl.food_item_id GROUP BY fi.id ORDER BY log_count DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">
        <h2 class="section-title" style="margin-bottom:var(--space-xl)">⚙️ Admin Dashboard</h2>

        <!-- Admin Stats -->
        <div class="stats-grid stagger" style="margin-bottom:var(--space-xl)">
            <div class="glass-card stat-card">
                <div class="stat-icon" style="background:rgba(108,92,231,0.15)">👥</div>
                <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon" style="background:rgba(0,206,201,0.15)">📝</div>
                <div class="stat-value"><?php echo number_format($totalLogs); ?></div>
                <div class="stat-label">Total Food Logs</div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon" style="background:rgba(253,203,110,0.15)">🍽️</div>
                <div class="stat-value"><?php echo number_format($totalFoods); ?></div>
                <div class="stat-label">Food Items</div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon" style="background:rgba(255,107,107,0.15)">📊</div>
                <div class="stat-value"><?php echo number_format($todayLogs); ?></div>
                <div class="stat-label">Logs Today</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-xl)">
            <!-- Recent Users -->
            <div class="glass-card-static" style="padding:0;overflow:hidden">
                <div style="padding:var(--space-lg);border-bottom:1px solid var(--border)">
                    <h3 style="font-weight:600">Recent Users</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-sm">
                                    <div style="width:32px;height:32px;border-radius:var(--radius-sm);background:linear-gradient(135deg,var(--accent),var(--success));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white">
                                        <?php echo strtoupper(substr($u['first_name'] ?: $u['username'], 0, 1)); ?>
                                    </div>
                                    <span class="font-semibold"><?php echo sanitize($u['first_name'] ? $u['first_name'] . ' ' . $u['last_name'] : $u['username']); ?></span>
                                </div>
                            </td>
                            <td class="text-secondary"><?php echo sanitize($u['email']); ?></td>
                            <td><span class="badge <?php echo $u['role'] === 'admin' ? 'badge-accent' : 'badge-info'; ?>"><?php echo $u['role']; ?></span></td>
                            <td class="text-secondary font-mono text-xs"><?php echo timeAgo($u['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Foods -->
            <div class="glass-card-static" style="padding:0;overflow:hidden">
                <div style="padding:var(--space-lg);border-bottom:1px solid var(--border)">
                    <h3 style="font-weight:600">Most Logged Foods</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Food</th>
                            <th>Category</th>
                            <th>Logs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $catIcons = FOOD_CATEGORIES;
                        foreach ($topFoods as $f): 
                        ?>
                        <tr>
                            <td class="font-semibold"><?php echo sanitize($f['name']); ?></td>
                            <td>
                                <span class="tag">
                                    <?php echo ($catIcons[$f['category']]['icon'] ?? '🍽️') . ' ' . ($catIcons[$f['category']]['label'] ?? $f['category']); ?>
                                </span>
                            </td>
                            <td class="font-mono"><?php echo $f['log_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>
</body>
</html>
