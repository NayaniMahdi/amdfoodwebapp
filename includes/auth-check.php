<?php
/**
 * Nouriq — Auth Guard
 */
require_once __DIR__ . '/functions.php';

startSecureSession();

// Check remember me cookie if not logged in
if (!isLoggedIn() && isset($_COOKIE['nouriq_remember'])) {
    $token = $_COOKIE['nouriq_remember'];
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE remember_token = ? AND is_active = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        session_regenerate_id(true);
    } else {
        setcookie('nouriq_remember', '', time() - 3600, '/');
    }
}

if (!isLoggedIn()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}
