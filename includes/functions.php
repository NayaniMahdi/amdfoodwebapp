<?php
/**
 * Nouriq — Utility Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// ============================================================
// SESSION MANAGEMENT
// ============================================================

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}

function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUserId() {
    startSecureSession();
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, p.first_name, p.last_name, p.avatar_url FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ' . APP_URL . '/dashboard/');
        exit;
    }
}

// ============================================================
// CSRF PROTECTION
// ============================================================

function generateCSRFToken() {
    startSecureSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    startSecureSession();
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

// ============================================================
// INPUT SANITIZATION
// ============================================================

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ============================================================
// NUTRITION CALCULATIONS
// ============================================================

function calculateBMI($weightKg, $heightCm) {
    if ($heightCm <= 0) return 0;
    $heightM = $heightCm / 100;
    return round($weightKg / ($heightM * $heightM), 1);
}

function getBMICategory($bmi) {
    if ($bmi < 18.5) return ['category' => 'Underweight', 'color' => '#74b9ff', 'icon' => '⚠️'];
    if ($bmi < 25)   return ['category' => 'Normal', 'color' => '#00cec9', 'icon' => '✅'];
    if ($bmi < 30)   return ['category' => 'Overweight', 'color' => '#fdcb6e', 'icon' => '⚠️'];
    return ['category' => 'Obese', 'color' => '#ff6b6b', 'icon' => '🚨'];
}

function calculateBMR($weightKg, $heightCm, $age, $gender) {
    // Mifflin-St Jeor Equation
    if ($gender === 'male') {
        return (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) + 5;
    }
    return (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) - 161;
}

function calculateDailyCalories($weightKg, $heightCm, $age, $gender, $activityLevel, $healthGoal) {
    $bmr = calculateBMR($weightKg, $heightCm, $age, $gender);
    $multipliers = ACTIVITY_MULTIPLIERS;
    $adjustments = GOAL_ADJUSTMENTS;
    
    $tdee = $bmr * ($multipliers[$activityLevel] ?? 1.55);
    $target = $tdee + ($adjustments[$healthGoal] ?? 0);
    
    return max(1200, round($target)); // Never go below 1200
}

function calculateMacroTargets($calories, $healthGoal) {
    $ratios = MACRO_RATIOS[$healthGoal] ?? MACRO_RATIOS['maintenance'];
    return [
        'protein' => round(($calories * $ratios['protein']) / 4), // 4 cal/g
        'carbs'   => round(($calories * $ratios['carbs']) / 4),   // 4 cal/g
        'fat'     => round(($calories * $ratios['fat']) / 9),     // 9 cal/g
    ];
}

// ============================================================
// DATE/TIME HELPERS
// ============================================================

function now() {
    return date('Y-m-d H:i:s');
}

function today() {
    return date('Y-m-d');
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $time);
}

function isToday($date) {
    return date('Y-m-d', strtotime($date)) === today();
}

function isLateNight($time = null) {
    $hour = (int)date('H', $time ? strtotime($time) : time());
    return $hour >= LATE_NIGHT_HOUR || $hour < 5;
}

// ============================================================
// RESPONSE HELPERS
// ============================================================

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonSuccess($data = null, $message = 'Success') {
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

function jsonError($message = 'Error', $statusCode = 400) {
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

// ============================================================
// GAMIFICATION HELPERS
// ============================================================

function addPoints($userId, $points) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO user_points (user_id, total_points, level) VALUES (?, ?, 1) 
                          ON DUPLICATE KEY UPDATE total_points = total_points + ?, level = FLOOR((total_points + ?) / ?) + 1");
    $stmt->execute([$userId, $points, $points, $points, POINTS_LEVEL_MULTIPLIER]);
}

function getUserPoints($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM user_points WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: ['total_points' => 0, 'level' => 1];
}

function updateStreak($userId, $streakType, $isPositive = true) {
    $db = getDB();
    $today = today();
    
    $stmt = $db->prepare("SELECT * FROM user_streaks WHERE user_id = ? AND streak_type = ?");
    $stmt->execute([$userId, $streakType]);
    $streak = $stmt->fetch();
    
    if (!$streak) {
        $stmt = $db->prepare("INSERT INTO user_streaks (user_id, streak_type, current_count, best_count, last_logged_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $streakType, $isPositive ? 1 : 0, $isPositive ? 1 : 0, $today]);
        return $isPositive ? 1 : 0;
    }
    
    if ($streak['last_logged_date'] === $today) {
        return $streak['current_count']; // Already logged today
    }
    
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($isPositive && $streak['last_logged_date'] === $yesterday) {
        // Continue streak
        $newCount = $streak['current_count'] + 1;
        $bestCount = max($streak['best_count'], $newCount);
        $stmt = $db->prepare("UPDATE user_streaks SET current_count = ?, best_count = ?, last_logged_date = ? WHERE id = ?");
        $stmt->execute([$newCount, $bestCount, $today, $streak['id']]);
        return $newCount;
    } elseif ($isPositive) {
        // Reset streak
        $stmt = $db->prepare("UPDATE user_streaks SET current_count = 1, last_logged_date = ? WHERE id = ?");
        $stmt->execute([$today, $streak['id']]);
        return 1;
    } else {
        // Break streak
        $stmt = $db->prepare("UPDATE user_streaks SET current_count = 0, last_logged_date = ? WHERE id = ?");
        $stmt->execute([$today, $streak['id']]);
        return 0;
    }
}

function checkAndAwardAchievements($userId) {
    $db = getDB();
    $awarded = [];
    
    // Get all achievements not yet earned
    $stmt = $db->prepare("SELECT a.* FROM achievements a WHERE a.id NOT IN (SELECT achievement_id FROM user_achievements WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $achievements = $stmt->fetchAll();
    
    foreach ($achievements as $achievement) {
        $earned = false;
        
        switch ($achievement['condition_type']) {
            case 'total_logs':
                $stmt2 = $db->prepare("SELECT COUNT(*) as cnt FROM food_logs WHERE user_id = ?");
                $stmt2->execute([$userId]);
                $earned = $stmt2->fetch()['cnt'] >= $achievement['condition_value'];
                break;
                
            case 'log_streak':
                $stmt2 = $db->prepare("SELECT best_count FROM user_streaks WHERE user_id = ? AND streak_type = 'logging'");
                $stmt2->execute([$userId]);
                $row = $stmt2->fetch();
                $earned = $row && $row['best_count'] >= $achievement['condition_value'];
                break;
                
            case 'no_junk_streak':
                $stmt2 = $db->prepare("SELECT best_count FROM user_streaks WHERE user_id = ? AND streak_type = 'no_junk'");
                $stmt2->execute([$userId]);
                $row = $stmt2->fetch();
                $earned = $row && $row['best_count'] >= $achievement['condition_value'];
                break;
                
            case 'protein_streak':
                $stmt2 = $db->prepare("SELECT best_count FROM user_streaks WHERE user_id = ? AND streak_type = 'protein_target'");
                $stmt2->execute([$userId]);
                $row = $stmt2->fetch();
                $earned = $row && $row['best_count'] >= $achievement['condition_value'];
                break;
                
            case 'calorie_streak':
                $stmt2 = $db->prepare("SELECT best_count FROM user_streaks WHERE user_id = ? AND streak_type = 'calorie_target'");
                $stmt2->execute([$userId]);
                $row = $stmt2->fetch();
                $earned = $row && $row['best_count'] >= $achievement['condition_value'];
                break;
                
            case 'full_day_log':
                $stmt2 = $db->prepare("
                    SELECT DATE(logged_at) as d, COUNT(DISTINCT meal_type) as mt 
                    FROM food_logs WHERE user_id = ? 
                    GROUP BY DATE(logged_at) HAVING mt >= 3
                ");
                $stmt2->execute([$userId]);
                $earned = $stmt2->rowCount() >= $achievement['condition_value'];
                break;

            case 'veggie_count':
                $stmt2 = $db->prepare("
                    SELECT COALESCE(SUM(fl.servings), 0) as cnt 
                    FROM food_logs fl JOIN food_items fi ON fl.food_item_id = fi.id 
                    WHERE fl.user_id = ? AND fi.category = 'vegetables'
                ");
                $stmt2->execute([$userId]);
                $earned = $stmt2->fetch()['cnt'] >= $achievement['condition_value'];
                break;

            case 'fruit_count':
                $stmt2 = $db->prepare("
                    SELECT COALESCE(SUM(fl.servings), 0) as cnt 
                    FROM food_logs fl JOIN food_items fi ON fl.food_item_id = fi.id 
                    WHERE fl.user_id = ? AND fi.category = 'fruits'
                ");
                $stmt2->execute([$userId]);
                $earned = $stmt2->fetch()['cnt'] >= $achievement['condition_value'];
                break;
        }
        
        if ($earned) {
            $stmt3 = $db->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
            $stmt3->execute([$userId, $achievement['id']]);
            if ($stmt3->rowCount() > 0) {
                addPoints($userId, $achievement['points']);
                $awarded[] = $achievement;
                
                // Create notification
                $stmt4 = $db->prepare("INSERT INTO notifications (user_id, type, title, message, icon) VALUES (?, 'achievement', ?, ?, ?)");
                $stmt4->execute([$userId, 'Achievement Unlocked!', 'You earned "' . $achievement['name'] . '" — ' . $achievement['description'], $achievement['icon']]);
            }
        }
    }
    
    return $awarded;
}

// ============================================================
// NOTIFICATION HELPERS
// ============================================================

function createNotification($userId, $type, $title, $message, $icon = '🔔') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, icon) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $title, $message, $icon]);
}

function getUnreadNotificationCount($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetch()['cnt'];
}

// ============================================================
// PROFILE HELPERS
// ============================================================

function getUserProfile($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getDisplayName($user) {
    if (!empty($user['first_name'])) {
        return $user['first_name'] . (!empty($user['last_name']) ? ' ' . $user['last_name'] : '');
    }
    return $user['username'] ?? 'User';
}
