<?php
/**
 * Nouriq — Logout
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

// Clear remember me
if (isset($_COOKIE['nouriq_remember'])) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['nouriq_remember']]);
    setcookie('nouriq_remember', '', time() - 3600, '/');
}

// Destroy session
$_SESSION = [];
session_destroy();

header('Location: ' . APP_URL . '/auth/login.php');
exit;
