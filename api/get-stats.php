<?php
/**
 * Nouriq API — Dashboard Statistics
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) jsonError('Unauthorized', 401);

$userId = getCurrentUserId();
$db = getDB();
$profile = getUserProfile($userId);

$action = $_GET['action'] ?? 'today';

switch ($action) {
    case 'today':
        // Today's totals
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(calories), 0) as total_cal,
                   COALESCE(SUM(protein_g), 0) as total_protein,
                   COALESCE(SUM(carbs_g), 0) as total_carbs,
                   COALESCE(SUM(fat_g), 0) as total_fat,
                   COUNT(*) as total_entries
            FROM food_logs WHERE user_id = ? AND DATE(logged_at) = CURDATE()
        ");
        $stmt->execute([$userId]);
        $today = $stmt->fetch();
        
        // Meals logged today
        $stmt = $db->prepare("SELECT DISTINCT meal_type FROM food_logs WHERE user_id = ? AND DATE(logged_at) = CURDATE()");
        $stmt->execute([$userId]);
        $mealsLogged = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Streak
        $stmt = $db->prepare("SELECT current_count FROM user_streaks WHERE user_id = ? AND streak_type = 'logging'");
        $stmt->execute([$userId]);
        $streak = $stmt->fetch();
        
        // Points
        $points = getUserPoints($userId);
        
        jsonSuccess([
            'calories' => [
                'consumed' => round($today['total_cal']),
                'target' => $profile['daily_calorie_target'] ?? DEFAULT_CALORIE_TARGET,
                'remaining' => max(0, ($profile['daily_calorie_target'] ?? DEFAULT_CALORIE_TARGET) - $today['total_cal'])
            ],
            'macros' => [
                'protein' => ['consumed' => round($today['total_protein']), 'target' => $profile['daily_protein_target'] ?? DEFAULT_PROTEIN_TARGET],
                'carbs'   => ['consumed' => round($today['total_carbs']), 'target' => $profile['daily_carb_target'] ?? DEFAULT_CARB_TARGET],
                'fat'     => ['consumed' => round($today['total_fat']), 'target' => $profile['daily_fat_target'] ?? DEFAULT_FAT_TARGET]
            ],
            'entries' => $today['total_entries'],
            'meals_logged' => $mealsLogged,
            'streak' => $streak['current_count'] ?? 0,
            'points' => $points
        ]);
        break;
        
    case 'weekly':
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(calories), 0) as cal,
                       COALESCE(SUM(protein_g), 0) as protein,
                       COALESCE(SUM(carbs_g), 0) as carbs,
                       COALESCE(SUM(fat_g), 0) as fat
                FROM food_logs WHERE user_id = ? AND DATE(logged_at) = ?
            ");
            $stmt->execute([$userId, $date]);
            $row = $stmt->fetch();
            $data[] = [
                'date' => $date,
                'label' => date('D', strtotime($date)),
                'calories' => round($row['cal']),
                'protein' => round($row['protein']),
                'carbs' => round($row['carbs']),
                'fat' => round($row['fat'])
            ];
        }
        
        jsonSuccess([
            'data' => $data,
            'target' => $profile['daily_calorie_target'] ?? DEFAULT_CALORIE_TARGET
        ]);
        break;
        
    case 'monthly':
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $stmt = $db->prepare("SELECT COALESCE(SUM(calories), 0) as cal FROM food_logs WHERE user_id = ? AND DATE(logged_at) = ?");
            $stmt->execute([$userId, $date]);
            $data[] = [
                'date' => $date,
                'label' => date('j', strtotime($date)),
                'calories' => round($stmt->fetch()['cal'])
            ];
        }
        jsonSuccess(['data' => $data, 'target' => $profile['daily_calorie_target'] ?? DEFAULT_CALORIE_TARGET]);
        break;
        
    default:
        jsonError('Invalid action');
}
