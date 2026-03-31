<?php
/**
 * Nouriq — Main Dashboard
 */
require_once __DIR__ . '/../includes/auth-check.php';
$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nouriq Dashboard — Track your nutrition and health goals">
    <title>Dashboard — Nouriq</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page-content">
        <!-- Welcome Banner -->
        <div class="glass-card-static" style="padding:var(--space-lg);margin-bottom:var(--space-xl);border-left:3px solid var(--accent)">
            <div class="flex items-center justify-between flex-wrap gap-md">
                <div>
                    <h2 style="font-size:var(--text-2xl);font-weight:700">Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening'); ?>, <?php echo sanitize($currentUser['first_name'] ?: $currentUser['username']); ?> 👋</h2>
                    <p class="text-secondary" style="margin-top:4px">Here's your nutrition summary for today</p>
                </div>
                <a href="food-log.php" class="btn btn-primary">
                    <span>+</span> Log Meal
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid stagger">
            <!-- Calories Card -->
            <div class="glass-card stat-card">
                <div class="stat-icon" style="background:rgba(162,155,254,0.15)">🔥</div>
                <div class="stat-value" id="statCalories">--</div>
                <div class="stat-label">Calories consumed</div>
                <div class="progress-bar" style="margin-top:4px">
                    <div class="progress-fill" id="calProgress" style="width:0%"></div>
                </div>
                <div class="text-xs text-secondary" style="margin-top:4px">
                    <span id="calRemaining">--</span> remaining
                </div>
            </div>

            <!-- Protein Card -->
            <div class="glass-card stat-card">
                <div class="stat-icon" style="background:rgba(255,107,107,0.15)">💪</div>
                <div class="stat-value" id="statProtein">--</div>
                <div class="stat-label">Protein (g)</div>
                <div class="progress-bar" style="margin-top:4px">
                    <div class="progress-fill" id="proteinProgress" style="width:0%;background:var(--color-protein)"></div>
                </div>
            </div>

            <!-- Streak Card -->
            <div class="glass-card stat-card">
                <div class="stat-icon" style="background:rgba(253,203,110,0.15)">🔥</div>
                <div class="stat-value" id="statStreak">0</div>
                <div class="stat-label">Day streak</div>
            </div>

            <!-- Points Card -->
            <div class="glass-card stat-card">
                <div class="stat-icon" style="background:rgba(0,206,201,0.15)">⭐</div>
                <div class="stat-value" id="statPoints">0</div>
                <div class="stat-label">Total points</div>
                <div class="badge badge-accent" id="statLevel" style="margin-top:4px">Level 1</div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-lg);margin-bottom:var(--space-xl)">
            <!-- Calorie Ring -->
            <div class="glass-card-static">
                <div class="chart-header">
                    <h3 class="chart-title">Today's Calories</h3>
                    <span class="badge badge-info" id="calBadge">--</span>
                </div>
                <div class="calorie-ring-container">
                    <div class="calorie-ring">
                        <canvas id="calorieRingCanvas"></canvas>
                        <div class="calorie-ring-center">
                            <div class="calorie-ring-value" id="ringValue">--</div>
                            <div class="calorie-ring-label">of <span id="ringTarget">--</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Macro Breakdown -->
            <div class="glass-card-static">
                <div class="chart-header">
                    <h3 class="chart-title">Macronutrients</h3>
                </div>
                <div class="macro-bars" id="macroBars">
                    <div class="skeleton skeleton-card" style="height:200px"></div>
                </div>
            </div>
        </div>

        <!-- Weekly Chart -->
        <div class="glass-card-static" style="margin-bottom:var(--space-xl)">
            <div class="chart-header">
                <h3 class="chart-title">Weekly Calories</h3>
                <div class="chart-period">
                    <button class="active" onclick="loadChart('weekly')">Week</button>
                    <button onclick="loadChart('monthly')">Month</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>

        <!-- Recommendations & Today's Log -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-lg)">
            <!-- Quick Recommendations -->
            <div>
                <div class="section-header">
                    <h3 class="section-title">Smart Insights</h3>
                    <a href="recommendations.php" class="btn btn-ghost btn-sm">View all →</a>
                </div>
                <div id="quickRecommendations">
                    <div class="skeleton skeleton-card" style="height:100px;margin-bottom:8px"></div>
                    <div class="skeleton skeleton-card" style="height:100px;margin-bottom:8px"></div>
                </div>
            </div>

            <!-- Today's Food Log Preview -->
            <div>
                <div class="section-header">
                    <h3 class="section-title">Today's Log</h3>
                    <a href="food-log.php" class="btn btn-ghost btn-sm">Full log →</a>
                </div>
                <div id="todayLogPreview">
                    <div class="skeleton skeleton-card" style="height:60px;margin-bottom:8px"></div>
                    <div class="skeleton skeleton-card" style="height:60px;margin-bottom:8px"></div>
                    <div class="skeleton skeleton-card" style="height:60px;margin-bottom:8px"></div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>

<script src="<?php echo JS_PATH; ?>charts.js"></script>
<script src="<?php echo JS_PATH; ?>dashboard.js"></script>
</body>
</html>
