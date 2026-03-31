<?php
/**
 * Nouriq API — Achievements
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) jsonError('Unauthorized', 401);

$userId = getCurrentUserId();
$db = getDB();

// Get all achievements with user status
$stmt = $db->prepare("
    SELECT a.*, 
           CASE WHEN ua.id IS NOT NULL THEN 1 ELSE 0 END as earned,
           ua.earned_at
    FROM achievements a
    LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
    ORDER BY earned DESC, a.points ASC
");
$stmt->execute([$userId]);
$achievements = $stmt->fetchAll();

// Get user points and streaks
$points = getUserPoints($userId);

$stmt = $db->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
$stmt->execute([$userId]);
$streaks = [];
while ($row = $stmt->fetch()) {
    $streaks[$row['streak_type']] = $row;
}

jsonSuccess([
    'achievements' => $achievements,
    'points' => $points,
    'streaks' => $streaks,
    'total_earned' => count(array_filter($achievements, fn($a) => $a['earned'])),
    'total_available' => count($achievements)
]);
