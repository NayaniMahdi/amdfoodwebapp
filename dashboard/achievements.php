<?php
require_once __DIR__ . '/../includes/auth-check.php';
$pageTitle = 'Achievements';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">
        <!-- Points & Level Header -->
        <div class="glass-card-static" style="padding:var(--space-xl);margin-bottom:var(--space-xl);text-align:center">
            <div style="font-size:48px;margin-bottom:8px">⭐</div>
            <div class="font-mono" style="font-size:var(--text-4xl);font-weight:800" id="totalPoints">0</div>
            <div class="text-secondary mb-md">Total Points</div>
            <div class="flex items-center justify-center gap-xl">
                <div>
                    <span class="badge badge-accent" style="font-size:14px;padding:8px 16px" id="userLevel">Level 1</span>
                </div>
                <div class="text-sm">
                    <span id="earnedCount">0</span> / <span id="totalCount">0</span> achievements
                </div>
            </div>
            <div class="progress-bar" style="max-width:300px;margin:16px auto 0">
                <div class="progress-fill" id="levelProgress" style="width:0%"></div>
            </div>
            <div class="text-xs text-secondary mt-sm" id="nextLevelText"></div>
        </div>

        <!-- Streaks -->
        <h3 class="section-title" style="margin-bottom:var(--space-md)">🔥 Active Streaks</h3>
        <div class="grid grid-3" id="streaksGrid" style="margin-bottom:var(--space-xl)">
            <div class="skeleton skeleton-card" style="height:100px"></div>
            <div class="skeleton skeleton-card" style="height:100px"></div>
            <div class="skeleton skeleton-card" style="height:100px"></div>
        </div>

        <!-- Achievement Grid -->
        <h3 class="section-title" style="margin-bottom:var(--space-md)">🏆 All Achievements</h3>
        <div class="grid grid-4 stagger" id="achievementsGrid">
            <div class="skeleton skeleton-card" style="height:160px"></div>
            <div class="skeleton skeleton-card" style="height:160px"></div>
            <div class="skeleton skeleton-card" style="height:160px"></div>
            <div class="skeleton skeleton-card" style="height:160px"></div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', loadAchievements);

async function loadAchievements() {
    const result = await NouriqAPI.get('/achievements.php');
    if (!result.success) return;
    
    const d = result.data;
    
    // Points & Level
    animateValue(document.getElementById('totalPoints'), 0, d.points.total_points, 1200);
    document.getElementById('userLevel').textContent = 'Level ' + d.points.level;
    document.getElementById('earnedCount').textContent = d.total_earned;
    document.getElementById('totalCount').textContent = d.total_available;
    
    const pointsInLevel = d.points.total_points % 100;
    document.getElementById('levelProgress').style.width = pointsInLevel + '%';
    document.getElementById('nextLevelText').textContent = (100 - pointsInLevel) + ' points to Level ' + (d.points.level + 1);
    
    // Streaks
    const streakNames = {
        'logging': { icon: '📅', label: 'Logging Streak' },
        'no_junk': { icon: '🥬', label: 'No Junk Food' },
        'protein_target': { icon: '💪', label: 'Protein Target' },
        'calorie_target': { icon: '🎯', label: 'Calorie Target' }
    };
    
    const streaksGrid = document.getElementById('streaksGrid');
    const streakEntries = Object.entries(d.streaks);
    
    if (streakEntries.length > 0) {
        streaksGrid.innerHTML = streakEntries.map(([type, streak]) => {
            const info = streakNames[type] || { icon: '🔥', label: type };
            return `
                <div class="glass-card" style="padding:var(--space-lg);text-align:center">
                    <div style="font-size:32px;margin-bottom:8px">${info.icon}</div>
                    <div class="font-mono" style="font-size:var(--text-2xl);font-weight:700">${streak.current_count}</div>
                    <div class="text-sm text-secondary">${info.label}</div>
                    <div class="text-xs text-secondary mt-sm">Best: ${streak.best_count} days</div>
                </div>
            `;
        }).join('');
    } else {
        streaksGrid.innerHTML = '<div class="glass-card" style="padding:24px;text-align:center;grid-column:span 3"><p class="text-secondary">Start logging meals to build streaks!</p></div>';
    }
    
    // Achievements
    document.getElementById('achievementsGrid').innerHTML = d.achievements.map(ach => `
        <div class="glass-card achievement-card ${ach.earned ? 'earned' : 'locked'}">
            <div class="ach-icon">${ach.icon}</div>
            <div class="ach-name">${escapeHtml(ach.name)}</div>
            <div class="ach-desc">${escapeHtml(ach.description)}</div>
            <div class="ach-points">${ach.earned ? '✅ +' + ach.points + ' pts' : '🔒 ' + ach.points + ' pts'}</div>
            ${ach.earned_at ? '<div class="text-xs text-secondary mt-sm">Earned ' + formatDate(ach.earned_at) + '</div>' : ''}
        </div>
    `).join('');
}
</script>
</body>
</html>
