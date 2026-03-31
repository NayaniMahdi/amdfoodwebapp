<?php
/**
 * Nouriq API — Food Search
 */
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) jsonError('Unauthorized', 401);

$query = sanitize($_GET['q'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$limit = min((int)($_GET['limit'] ?? 20), 50);

$db = getDB();

$sql = "SELECT * FROM food_items WHERE 1=1";
$params = [];

if (!empty($query)) {
    $sql .= " AND (name LIKE ? OR tags LIKE ?)";
    $params[] = "%{$query}%";
    $params[] = "%{$query}%";
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY name ASC LIMIT ?";
$params[] = $limit;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$foods = $stmt->fetchAll();

jsonSuccess($foods);
