<?php
/**
 * Nouriq API — Profile CRUD
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) jsonError('Unauthorized', 401);

$userId = getCurrentUserId();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $age = max(1, min(120, (int)($_POST['age'] ?? 25)));
    $gender = sanitize($_POST['gender'] ?? 'male');
    $heightCm = max(50, min(300, (float)($_POST['height_cm'] ?? 170)));
    $weightKg = max(20, min(500, (float)($_POST['weight_kg'] ?? 70)));
    $activityLevel = sanitize($_POST['activity_level'] ?? 'moderate');
    $dietType = sanitize($_POST['diet_type'] ?? 'omnivore');
    $healthGoal = sanitize($_POST['health_goal'] ?? 'maintenance');
    $allergies = $_POST['allergies'] ?? '[]';
    
    // Calculate BMI
    $bmi = calculateBMI($weightKg, $heightCm);
    
    // Calculate calorie targets
    $calorieTarget = calculateDailyCalories($weightKg, $heightCm, $age, $gender, $activityLevel, $healthGoal);
    $macros = calculateMacroTargets($calorieTarget, $healthGoal);
    
    $stmt = $db->prepare("
        UPDATE profiles SET 
            first_name = ?, last_name = ?, age = ?, gender = ?,
            height_cm = ?, weight_kg = ?, bmi = ?, activity_level = ?,
            diet_type = ?, health_goal = ?, allergies = ?,
            daily_calorie_target = ?, daily_protein_target = ?,
            daily_carb_target = ?, daily_fat_target = ?
        WHERE user_id = ?
    ");
    $stmt->execute([
        $firstName, $lastName, $age, $gender,
        $heightCm, $weightKg, $bmi, $activityLevel,
        $dietType, $healthGoal, $allergies,
        $calorieTarget, $macros['protein'],
        $macros['carbs'], $macros['fat'],
        $userId
    ]);
    
    jsonSuccess([
        'bmi' => $bmi,
        'bmi_category' => getBMICategory($bmi),
        'calorie_target' => $calorieTarget,
        'macros' => $macros
    ], 'Profile updated successfully');
    
} else {
    $profile = getUserProfile($userId);
    $user = getCurrentUser();
    
    if ($profile) {
        $profile['bmi_category'] = getBMICategory($profile['bmi'] ?? 0);
        $profile['email'] = $user['email'];
        $profile['username'] = $user['username'];
    }
    
    jsonSuccess($profile);
}
