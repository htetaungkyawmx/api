<?php
require_once 'config.php';

$userId = validateToken($pdo);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = $_GET['date'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as activity_count,
            SUM(duration_minutes) as total_minutes,
            SUM(calories_burned) as total_calories,
            GROUP_CONCAT(DISTINCT activity_type) as activity_types
        FROM activities
        WHERE user_id = ?
        AND DATE(created_at) = ?
    ");

    $stmt->execute([$userId, $date]);
    $summary = $stmt->fetch();

    // Get activities for the day
    $activitiesStmt = $pdo->prepare("
        SELECT *
        FROM activities
        WHERE user_id = ?
        AND DATE(created_at) = ?
        ORDER BY created_at DESC
    ");

    $activitiesStmt->execute([$userId, $date]);
    $activities = $activitiesStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'date' => $date,
        'summary' => $summary,
        'activities' => $activities
    ]);
}
?>