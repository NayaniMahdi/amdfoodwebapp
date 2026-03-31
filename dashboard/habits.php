<?php
require_once __DIR__ . '/../includes/auth-check.php';
$pageTitle = 'Habits & Insights';
$userId = getCurrentUserId();
$db = getDB();
$profile = getUserProfile($userId);

// Get habit data
$stmt = $db->prepare("
    SELECT DATE(logged_at) as log_date, 
           COUNT(*) as entries,
           SUM(calories) as total_cal,
           SUM(protein_g) as total_pro,
           GROUP_CONCAT(DISTINCT meal_type) as meal_types,
           MAX(HOUR(logged_at)) as latest_hour,
           MIN(HOUR(logged_at)) as earliest_hour
    FROM food_logs WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(logged_at) ORDER BY log_date DESC
");
$stmt->execute([$userId]);
$dailyData = $stmt->fetchAll();

// Late night eating count
$stmt = $db->prepare("SELECT COUNT(DISTINCT DATE(logged_at)) as cnt FROM food_logs WHERE user_id = ? AND HOUR(logged_at) >= 21 AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute([$userId]);
$lateNightDays = $stmt->fetch()['cnt'];

// Healthy vs junk ratio
$stmt = $db->prepare("
    SELECT fi.is_healthy, COUNT(*) as cnt FROM food_logs fl 
    JOIN food_items fi ON fl.food_item_id = fi.id 
    WHERE fl.user_id = ? AND fl.logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY fi.is_healthy
");
$stmt->execute([$userId]);
$healthRatio = [];
while ($r = $stmt->fetch()) $healthRatio[$r['is_healthy']] = $r['cnt'];
$healthyCount = $healthRatio[1] ?? 0;
$junkCount = $healthRatio[0] ?? 0;
$totalFoods = $healthyCount + $junkCount;
$healthyPct = $totalFoods > 0 ? round(($healthyCount / $totalFoods) * 100) : 0;

// Breakfast skip rate
$breakfastSkipCount = 0;
$totalDays = count($dailyData);
foreach ($dailyData as $d) {
    if (strpos($d['meal_types'], 'breakfast') === false) $breakfastSkipCount++;
}
$breakfastSkipRate = $totalDays > 0 ? round(($breakfastSkipCount / $totalDays) * 100) : 0;

// Avg daily calories
$avgCal = $totalDays > 0 ? round(array_sum(array_column($dailyData, 'total_cal')) / $totalDays) : 0;
$target = $profile['daily_calorie_target'] ?? DEFAULT_CALORIE_TARGET;

// Overeating days
$overeatDays = 0;
foreach ($dailyData as $d) { if ($d['total_cal'] > $target * 1.1) $overeatDays++; }
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habits & Insights — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="page-content">
        <h2 class="section-title" style="margin-bottom:var(--space-lg)">📈 Habits & Behavior Analysis</h2>
        <p class="text-secondary mb-lg">Insights from your last 30 days of nutrition data.</p>

        <!-- Insight Cards -->
        <div class="grid grid-2 stagger" style="margin-bottom:var(--space-xl)">
            <div class="glass-card insight-card">
                <div class="insight-icon" style="background:var(--success-bg)"><?php echo $healthyPct >= 70 ? '✅' : '⚠️'; ?></div>
                <div class="insight-content">
                    <div class="insight-title">Healthy Food Ratio</div>
                    <div class="insight-text"><?php echo $healthyPct; ?>% of your foods are healthy (<?php echo $healthyCount; ?> healthy vs <?php echo $junkCount; ?> unhealthy)</div>
                </div>
                <div class="font-mono font-bold" style="color:<?php echo $healthyPct >= 70 ? 'var(--success)' : 'var(--warning)'; ?>"><?php echo $healthyPct; ?>%</div>
            </div>

            <div class="glass-card insight-card">
                <div class="insight-icon" style="background:var(--warning-bg)">🌙</div>
                <div class="insight-content">
                    <div class="insight-title">Late Night Eating</div>
                    <div class="insight-text"><?php echo $lateNightDays > 0 ? "You ate late {$lateNightDays} out of {$totalDays} days. Try to eat earlier." : 'Great! No late-night eating detected.'; ?></div>
                </div>
                <div class="font-mono font-bold"><?php echo $lateNightDays; ?> days</div>
            </div>

            <div class="glass-card insight-card">
                <div class="insight-icon" style="background:var(--info-bg)">🌅</div>
                <div class="insight-content">
                    <div class="insight-title">Breakfast Consistency</div>
                    <div class="insight-text"><?php echo $breakfastSkipRate > 30 ? "You skip breakfast {$breakfastSkipRate}% of the time. Breakfast helps maintain energy." : "Great breakfast consistency! You rarely skip it."; ?></div>
                </div>
                <div class="font-mono font-bold"><?php echo 100 - $breakfastSkipRate; ?>%</div>
            </div>

            <div class="glass-card insight-card">
                <div class="insight-icon" style="background:<?php echo $overeatDays > 7 ? 'var(--danger-bg)' : 'var(--success-bg)'; ?>">🍽️</div>
                <div class="insight-content">
                    <div class="insight-title">Overeating Frequency</div>
                    <div class="insight-text"><?php echo $overeatDays > 0 ? "You exceeded your calorie target on {$overeatDays} out of {$totalDays} days." : "No overeating detected — great discipline!"; ?></div>
                </div>
                <div class="font-mono font-bold"><?php echo $overeatDays; ?> days</div>
            </div>
        </div>

        <!-- Calorie Trend Chart -->
        <div class="glass-card-static" style="margin-bottom:var(--space-xl)">
            <div class="chart-header">
                <h3 class="chart-title">30-Day Calorie Trend</h3>
                <span class="text-sm text-secondary">Avg: <span class="font-mono"><?php echo number_format($avgCal); ?></span> cal/day</span>
            </div>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Detailed Insights -->
        <h3 class="section-title" style="margin-bottom:var(--space-md)">💡 Personalized Insights</h3>
        <div class="stagger">
            <?php
            $insights = [];
            if ($healthyPct < 60) $insights[] = ['icon' => '🥗', 'text' => 'Your healthy food ratio is below 60%. Try replacing one junk food item per day with a healthier option.', 'class' => 'alert'];
            if ($lateNightDays > 5) $insights[] = ['icon' => '🌙', 'text' => 'You tend to eat late at night frequently. Late eating can disrupt sleep and increase fat storage.', 'class' => 'alert'];
            if ($breakfastSkipRate > 40) $insights[] = ['icon' => '🌅', 'text' => 'You skip breakfast often. A protein-rich breakfast can boost your metabolism and reduce cravings.', 'class' => 'alert'];
            if ($avgCal > $target * 1.1) $insights[] = ['icon' => '📊', 'text' => "Your average intake ({$avgCal} cal) exceeds your target ({$target} cal). Consider portion control.", 'class' => 'danger'];
            if ($avgCal > 0 && $avgCal < $target * 0.7) $insights[] = ['icon' => '⚠️', 'text' => 'Your average calorie intake is too low. Under-eating can slow metabolism and cause nutrient deficiency.', 'class' => 'alert'];
            if ($overeatDays == 0 && $totalDays >= 7) $insights[] = ['icon' => '🎯', 'text' => 'Excellent calorie discipline! You stayed within your target every day.', 'class' => 'success'];
            if ($healthyPct >= 80) $insights[] = ['icon' => '🏆', 'text' => 'Your healthy food ratio is excellent at ' . $healthyPct . '%! Keep it up.', 'class' => 'success'];
            if (empty($insights)) $insights[] = ['icon' => '📝', 'text' => 'Log more meals to unlock detailed behavioral insights about your eating patterns.', 'class' => ''];
            
            foreach ($insights as $ins):
            ?>
            <div class="glass-card rec-card <?php echo $ins['class']; ?>" style="margin-bottom:12px">
                <span class="rec-icon"><?php echo $ins['icon']; ?></span>
                <div class="rec-content">
                    <div class="rec-message"><?php echo $ins['text']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>
<script src="<?php echo JS_PATH; ?>charts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const calData = <?php echo json_encode(array_reverse(array_map(function($d) { return ['date' => $d['log_date'], 'cal' => round($d['total_cal'])]; }, $dailyData))); ?>;
    const target = <?php echo $target; ?>;
    
    if (calData.length > 0) {
        NouriqCharts.drawLineChart('trendChart', 
            [{ data: calData.map(d => d.cal), color: '#6C5CE7', fill: true }],
            calData.map(d => { const date = new Date(d.date); return (date.getMonth()+1) + '/' + date.getDate(); }),
            { height: 250 }
        );
    }
});
</script>
</body>
</html>
