<?php
/**
 * Nouriq API — Notifications
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) jsonError('Unauthorized', 401);

$userId = getCurrentUserId();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            jsonSuccess(null, 'Marked as read');
            break;
            
        case 'mark_all_read':
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            jsonSuccess(null, 'All marked as read');
            break;
            
        default:
            jsonError('Invalid action');
    }
} else {
    $limit = min((int)($_GET['limit'] ?? 20), 50);
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    jsonSuccess($stmt->fetchAll());
}
