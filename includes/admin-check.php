<?php
/**
 * Nouriq — Admin Guard
 */
require_once __DIR__ . '/auth-check.php';

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'admin') {
    header('Location: ' . APP_URL . '/dashboard/');
    exit;
}
