<?php
/**
 * Nouriq API — Log Meal
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) jsonError('Unauthorized', 401);

$userId = getCurrentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

$db = getDB();

switch ($action) {
    case 'add':
        $foodItemId = (int)($_POST['food_item_id'] ?? 0);
        $mealType = sanitize($_POST['meal_type'] ?? 'snack');
        $servings = max(0.1, (float)($_POST['servings'] ?? 1));
        $loggedAt = sanitize($_POST['logged_at'] ?? now());
        
        if ($foodItemId <= 0) jsonError('Invalid food item');
        
        // Get food data
        $stmt = $db->prepare("SELECT * FROM food_items WHERE id = ?");
        $stmt->execute([$foodItemId]);
        $food = $stmt->fetch();
        
        if (!$food) jsonError('Food item not found');
        
        $calories = $food['calories'] * $servings;
        $protein = $food['protein_g'] * $servings;
        $carbs = $food['carbs_g'] * $servings;
        $fat = $food['fat_g'] * $servings;
        
        $stmt = $db->prepare("INSERT INTO food_logs (user_id, food_item_id, meal_type, servings, calories, protein_g, carbs_g, fat_g, logged_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $foodItemId, $mealType, $servings, $calories, $protein, $carbs, $fat, $loggedAt]);
        
        // Update streaks
        updateStreak($userId, 'logging', true);
        
        // Check if healthy
        if ($food['is_healthy']) {
            updateStreak($userId, 'no_junk', true);
            addPoints($userId, POINTS_PER_HEALTHY_MEAL);
        } else {
            updateStreak($userId, 'no_junk', false);
        }
        
        addPoints($userId, POINTS_PER_LOG);
        
        // Check achievements
        $newAchievements = checkAndAwardAchievements($userId);
        
        // Generate smart notifications
        generateSmartNotifications($userId);
        
        jsonSuccess([
            'log_id' => $db->lastInsertId(),
            'calories' => $calories,
            'new_achievements' => $newAchievements
        ], 'Meal logged successfully');
        break;
        
    case 'delete':
        $logId = (int)($_POST['log_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM food_logs WHERE id = ? AND user_id = ?");
        $stmt->execute([$logId, $userId]);
        jsonSuccess(null, 'Entry deleted');
        break;
        
    case 'list':
        $date = sanitize($_GET['date'] ?? today());
        $stmt = $db->prepare("
            SELECT fl.*, fi.name as food_name, fi.category, fi.serving_size, fi.is_healthy
            FROM food_logs fl 
            JOIN food_items fi ON fl.food_item_id = fi.id 
            WHERE fl.user_id = ? AND DATE(fl.logged_at) = ?
            ORDER BY fl.logged_at ASC
        ");
        $stmt->execute([$userId, $date]);
        $logs = $stmt->fetchAll();
        
        // Group by meal type
        $grouped = ['breakfast' => [], 'lunch' => [], 'dinner' => [], 'snack' => []];
        foreach ($logs as $log) {
            $grouped[$log['meal_type']][] = $log;
        }
        
        jsonSuccess($grouped);
        break;
        
    case 'add_custom':
        $name = sanitize($_POST['name'] ?? '');
        $category = sanitize($_POST['category'] ?? 'other');
        $servingSize = sanitize($_POST['serving_size'] ?? '1 serving');
        $calories = max(0, (float)($_POST['calories'] ?? 0));
        $protein = max(0, (float)($_POST['protein_g'] ?? 0));
        $carbs = max(0, (float)($_POST['carbs_g'] ?? 0));
        $fat = max(0, (float)($_POST['fat_g'] ?? 0));
        
        if (empty($name)) jsonError('Food name is required');
        
        $stmt = $db->prepare("INSERT INTO food_items (name, category, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g, sugar_g, is_healthy, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 1, ?)");
        $stmt->execute([$name, $category, $servingSize, $calories, $protein, $carbs, $fat, $userId]);
        
        jsonSuccess(['food_id' => $db->lastInsertId()], 'Custom food added');
        break;
        
    default:
        jsonError('Invalid action');
}

function generateSmartNotifications($userId) {
    $db = getDB();
    $profile = getUserProfile($userId);
    if (!$profile) return;
    
    // Get today's totals
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(calories), 0) as total_cal, 
               COALESCE(SUM(protein_g), 0) as total_protein,
               COALESCE(SUM(carbs_g), 0) as total_carbs,
               COALESCE(SUM(fat_g), 0) as total_fat,
               MAX(logged_at) as last_log
        FROM food_logs WHERE user_id = ? AND DATE(logged_at) = CURDATE()
    ");
    $stmt->execute([$userId]);
    $totals = $stmt->fetch();
    
    $target = $profile['daily_calorie_target'] ?? DEFAULT_CALORIE_TARGET;
    
    // Over calorie target
    if ($totals['total_cal'] > $target * 1.1) {
        $existing = $db->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = 'behavior_alert' AND DATE(created_at) = CURDATE() AND title LIKE '%calorie%'");
        $existing->execute([$userId]);
        if (!$existing->fetch()) {
            createNotification($userId, 'behavior_alert', 'Calorie Target Exceeded ⚠️', 
                'You\'ve consumed ' . round($totals['total_cal']) . ' calories today, exceeding your ' . $target . ' target. Consider lighter options for remaining meals.', '🔥');
        }
    }
    
    // Late night eating
    $lastLogHour = $totals['last_log'] ? (int)date('H', strtotime($totals['last_log'])) : 0;
    if ($lastLogHour >= LATE_NIGHT_HOUR) {
        $existing = $db->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = 'behavior_alert' AND DATE(created_at) = CURDATE() AND title LIKE '%night%'");
        $existing->execute([$userId]);
        if (!$existing->fetch()) {
            createNotification($userId, 'behavior_alert', 'Late Night Eating Detected 🌙', 
                'Eating late at night can affect sleep and digestion. Try having your last meal before 9 PM.', '🌙');
        }
    }
}
