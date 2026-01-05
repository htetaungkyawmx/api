<?php
require_once 'config.php';

$userId = validateToken($pdo);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user profile
    $userStmt = $pdo->prepare("
        SELECT
            id, username, email, height_cm, weight_kg,
            DATE_FORMAT(birth_date, '%Y-%m-%d') as birth_date,
            created_at
        FROM users
        WHERE id = ?
    ");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Get user statistics
    $statsStmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_activities,
            SUM(duration_minutes) as total_minutes,
            SUM(calories_burned) as total_calories,
            MAX(created_at) as last_activity
        FROM activities
        WHERE user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch();

    // Get goals
    $goalsStmt = $pdo->prepare("
        SELECT
            goal_type,
            target_value,
            current_value,
            deadline
        FROM goals
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $goalsStmt->execute([$userId]);
    $goals = $goalsStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'profile' => $user,
        'statistics' => $stats,
        'goals' => $goals
    ]);
}
?>