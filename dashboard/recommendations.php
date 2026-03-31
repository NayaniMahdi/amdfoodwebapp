<?php
require_once __DIR__ . '/../includes/auth-check.php';
$pageTitle = 'Recommendations';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommendations — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">
        <div class="section-header">
            <h2 class="section-title">🧠 Smart Recommendations</h2>
            <button class="btn btn-secondary btn-sm" onclick="loadRecs()">🔄 Refresh</button>
        </div>
        <p class="text-secondary mb-lg">Personalized insights based on your eating behavior and health goals.</p>
        <div id="recsContainer">
            <div class="skeleton skeleton-card" style="height:100px;margin-bottom:12px"></div>
            <div class="skeleton skeleton-card" style="height:100px;margin-bottom:12px"></div>
            <div class="skeleton skeleton-card" style="height:100px;margin-bottom:12px"></div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', loadRecs);

async function loadRecs() {
    const container = document.getElementById('recsContainer');
    const result = await NouriqAPI.get('/get-recommendations.php');
    
    if (result.success && result.data && result.data.length > 0) {
        container.innerHTML = result.data.map((rec, i) => `
            <div class="glass-card rec-card ${rec.class || ''}" style="animation-delay:${i * 0.05}s" >
                <span class="rec-icon">${rec.icon}</span>
                <div class="rec-content">
                    <div class="flex items-center gap-sm" style="margin-bottom:4px">
                        <div class="rec-title">${escapeHtml(rec.title)}</div>
                        <span class="badge ${rec.priority === 'high' ? 'badge-danger' : rec.priority === 'medium' ? 'badge-warning' : 'badge-info'}">${rec.priority}</span>
                    </div>
                    <div class="rec-message">${escapeHtml(rec.message)}</div>
                </div>
            </div>
        `).join('');
    } else {
        container.innerHTML = `
            <div class="glass-card empty-state" style="padding:48px">
                <span class="empty-icon">✨</span>
                <h3>All caught up!</h3>
                <p>Log some meals to get personalized recommendations based on your eating patterns.</p>
            </div>`;
    }
}
</script>
</body>
</html>
