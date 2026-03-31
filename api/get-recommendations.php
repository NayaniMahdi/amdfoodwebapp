<?php
/**
 * Nouriq API — Recommendation Engine
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) jsonError('Unauthorized', 401);

$userId = getCurrentUserId();
$db = getDB();
$profile = getUserProfile($userId);

$recommendations = [];

if (!$profile) {
    $recommendations[] = [
        'type' => 'tip',
        'title' => 'Complete Your Profile',
        'message' => 'Set up your profile to get personalized recommendations based on your health goals.',
        'priority' => 'high',
        'icon' => '👤',
        'class' => 'alert'
    ];
    jsonSuccess($recommendations);
}

$target = $profile['daily_calorie_target'] ?? DEFAULT_CALORIE_TARGET;
$proteinTarget = $profile['daily_protein_target'] ?? DEFAULT_PROTEIN_TARGET;

// Get today's data
$stmt = $db->prepare("
    SELECT COALESCE(SUM(calories), 0) as cal, COALESCE(SUM(protein_g), 0) as protein,
           COALESCE(SUM(carbs_g), 0) as carbs, COALESCE(SUM(fat_g), 0) as fat,
           COUNT(*) as entries
    FROM food_logs WHERE user_id = ? AND DATE(logged_at) = CURDATE()
");
$stmt->execute([$userId]);
$today = $stmt->fetch();

// Get today's meal types
$stmt = $db->prepare("SELECT DISTINCT meal_type FROM food_logs WHERE user_id = ? AND DATE(logged_at) = CURDATE()");
$stmt->execute([$userId]);
$mealsToday = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get today's unhealthy food count
$stmt = $db->prepare("
    SELECT COUNT(*) as junk_count FROM food_logs fl 
    JOIN food_items fi ON fl.food_item_id = fi.id 
    WHERE fl.user_id = ? AND DATE(fl.logged_at) = CURDATE() AND fi.is_healthy = 0
");
$stmt->execute([$userId]);
$junkCount = $stmt->fetch()['junk_count'];

// Get last 7 days average
$stmt = $db->prepare("
    SELECT COALESCE(AVG(daily_cal), 0) as avg_cal FROM (
        SELECT DATE(logged_at) as d, SUM(calories) as daily_cal 
        FROM food_logs WHERE user_id = ? AND DATE(logged_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(logged_at)
    ) sub
");
$stmt->execute([$userId]);
$weeklyAvg = round($stmt->fetch()['avg_cal']);

// Get recent unhealthy foods for swap suggestions
$stmt = $db->prepare("
    SELECT fi.name FROM food_logs fl 
    JOIN food_items fi ON fl.food_item_id = fi.id 
    WHERE fl.user_id = ? AND fi.is_healthy = 0 AND DATE(fl.logged_at) >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
    GROUP BY fi.name ORDER BY COUNT(*) DESC LIMIT 3
");
$stmt->execute([$userId]);
$recentJunk = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get late night eating
$stmt = $db->prepare("
    SELECT COUNT(*) as cnt FROM food_logs 
    WHERE user_id = ? AND DATE(logged_at) = CURDATE() AND HOUR(logged_at) >= ?
");
$stmt->execute([$userId, LATE_NIGHT_HOUR]);
$lateNightCount = $stmt->fetch()['cnt'];

// ============================================================
// GENERATE RECOMMENDATIONS
// ============================================================

$currentHour = (int)date('H');

// 1. Calorie Analysis
if ($today['cal'] > $target * 1.1) {
    $over = round($today['cal'] - $target);
    $recommendations[] = [
        'type' => 'alert', 'priority' => 'high', 'icon' => '🔥', 'class' => 'danger',
        'title' => 'Over Calorie Target',
        'message' => "You've exceeded your daily target by {$over} calories. For your remaining meals, try lighter options like salads, grilled proteins, or steamed vegetables."
    ];
} elseif ($today['cal'] > $target * 0.9 && $currentHour < 18) {
    $remaining = round($target - $today['cal']);
    $recommendations[] = [
        'type' => 'tip', 'priority' => 'medium', 'icon' => '🎯', 'class' => '',
        'title' => 'On Track — Budget Your Remaining Calories',
        'message' => "You have {$remaining} calories left for the day. A lean protein with veggies would be perfect for dinner."
    ];
}

// 2. Protein Check
if ($today['entries'] > 0 && $today['protein'] < $proteinTarget * 0.4 && $currentHour >= 14) {
    $recommendations[] = [
        'type' => 'meal_suggestion', 'priority' => 'high', 'icon' => '💪', 'class' => 'alert',
        'title' => 'Low Protein Intake Today',
        'message' => "You've only had " . round($today['protein']) . "g of protein (target: {$proteinTarget}g). Consider adding grilled chicken, eggs, Greek yogurt, or lentils to your next meal."
    ];
}

// 3. Meal Skipping
if ($currentHour >= 11 && !in_array('breakfast', $mealsToday)) {
    $recommendations[] = [
        'type' => 'alert', 'priority' => 'medium', 'icon' => '🌅', 'class' => 'alert',
        'title' => 'Breakfast Skipped',
        'message' => "You haven't logged breakfast today. Starting your day with a balanced meal helps maintain energy and prevents overeating later."
    ];
}
if ($currentHour >= 15 && !in_array('lunch', $mealsToday)) {
    $recommendations[] = [
        'type' => 'alert', 'priority' => 'medium', 'icon' => '🍽️', 'class' => 'alert',
        'title' => 'Lunch Not Logged',
        'message' => "You haven't logged lunch. Skipping meals can slow your metabolism and lead to poor food choices later."
    ];
}

// 4. Late Night Eating
if ($lateNightCount > 0) {
    $recommendations[] = [
        'type' => 'tip', 'priority' => 'medium', 'icon' => '🌙', 'class' => 'alert',
        'title' => 'Late Night Eating Detected',
        'message' => 'Eating after 9 PM can disrupt sleep and increase fat storage. Try herbal tea or a small portion of nuts if you feel hungry.'
    ];
}

// 5. Food Swaps
$swapMap = FOOD_SWAPS;
foreach ($recentJunk as $junkName) {
    if (isset($swapMap[$junkName])) {
        $healthyAlt = $swapMap[$junkName];
        $recommendations[] = [
            'type' => 'food_swap', 'priority' => 'low', 'icon' => '🔄', 'class' => 'success',
            'title' => "Healthier Alternative: {$junkName}",
            'message' => "Instead of {$junkName}, try {$healthyAlt} — it's lower in calories and better for your goals."
        ];
    }
}

// 6. Junk Food Alert
if ($junkCount >= 3) {
    $recommendations[] = [
        'type' => 'alert', 'priority' => 'high', 'icon' => '🚨', 'class' => 'danger',
        'title' => 'High Junk Food Intake',
        'message' => "You've logged {$junkCount} unhealthy food items today. Try to balance with fruits, vegetables, and lean proteins."
    ];
}

// 7. Weekly Average Insight
if ($weeklyAvg > 0) {
    if ($weeklyAvg > $target * 1.15) {
        $recommendations[] = [
            'type' => 'tip', 'priority' => 'medium', 'icon' => '📊', 'class' => 'alert',
            'title' => 'Weekly Average Above Target',
            'message' => "Your 7-day average is " . number_format($weeklyAvg) . " cal/day (target: " . number_format($target) . "). Consider reducing portion sizes or choosing lower-calorie alternatives."
        ];
    } elseif ($weeklyAvg > 0 && $weeklyAvg < $target * 0.7) {
        $recommendations[] = [
            'type' => 'tip', 'priority' => 'medium', 'icon' => '⚠️', 'class' => 'alert',
            'title' => 'Eating Too Little',
            'message' => "Your 7-day average is only " . number_format($weeklyAvg) . " cal/day. Under-eating can slow your metabolism and lead to nutrient deficiency."
        ];
    }
}

// 8. No entries yet today
if ($today['entries'] == 0 && $currentHour >= 8) {
    $recommendations[] = [
        'type' => 'tip', 'priority' => 'medium', 'icon' => '📝', 'class' => '',
        'title' => 'Start Logging Today',
        'message' => 'You haven\'t logged any meals yet. Track your intake to stay on top of your nutrition goals!'
    ];
}

// 9. Hydration Reminder
if ($currentHour >= 10 && $currentHour <= 20) {
    $recommendations[] = [
        'type' => 'tip', 'priority' => 'low', 'icon' => '💧', 'class' => '',
        'title' => 'Stay Hydrated',
        'message' => 'Remember to drink water throughout the day. Aim for at least 8 glasses (2 liters) daily.'
    ];
}

// Sort by priority
$priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
usort($recommendations, function($a, $b) use ($priorityOrder) {
    return ($priorityOrder[$a['priority']] ?? 9) - ($priorityOrder[$b['priority']] ?? 9);
});

jsonSuccess($recommendations);
