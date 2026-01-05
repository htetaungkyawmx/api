<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $user_id = $_GET['user_id'] ?? 0;

    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "User ID is required"
        ]);
        exit();
    }

    // Get weekly statistics
    $weeklyStats = $conn->prepare("
        SELECT 
            SUM(duration) as total_duration,
            SUM(calories) as total_calories,
            COUNT(*) as workout_count
        FROM workouts 
        WHERE user_id = ? 
        AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $weeklyStats->bind_param("i", $user_id);
    $weeklyStats->execute();
    $weeklyResult = $weeklyStats->get_result()->fetch_assoc();
    $weeklyStats->close();

    // Get monthly statistics
    $monthlyStats = $conn->prepare("
        SELECT 
            SUM(duration) as total_duration,
            SUM(calories) as total_calories,
            COUNT(*) as workout_count
        FROM workouts 
        WHERE user_id = ? 
        AND MONTH(date) = MONTH(CURDATE())
        AND YEAR(date) = YEAR(CURDATE())
    ");
    $monthlyStats->bind_param("i", $user_id);
    $monthlyStats->execute();
    $monthlyResult = $monthlyStats->get_result()->fetch_assoc();
    $monthlyStats->close();

    // Get workout type distribution
    $typeStats = $conn->prepare("
        SELECT 
            type,
            COUNT(*) as count,
            SUM(duration) as total_duration
        FROM workouts 
        WHERE user_id = ? 
        GROUP BY type
        ORDER BY count DESC
    ");
    $typeStats->bind_param("i", $user_id);
    $typeStats->execute();
    $typeResult = $typeStats->get_result();
    
    $typeDistribution = [];
    while ($row = $typeResult->fetch_assoc()) {
        $typeDistribution[] = [
            'type' => $row['type'],
            'count' => $row['count'],
            'totalDuration' => $row['total_duration']
        ];
    }
    $typeStats->close();

    // Get recent workouts
    $recentWorkouts = $conn->prepare("
        SELECT type, duration, calories, date 
        FROM workouts 
        WHERE user_id = ? 
        ORDER BY date DESC 
        LIMIT 5
    ");
    $recentWorkouts->bind_param("i", $user_id);
    $recentWorkouts->execute();
    $recentResult = $recentWorkouts->get_result();
    
    $recentActivities = [];
    while ($row = $recentResult->fetch_assoc()) {
        $recentActivities[] = [
            'type' => $row['type'],
            'duration' => $row['duration'],
            'calories' => $row['calories'],
            'date' => $row['date']
        ];
    }
    $recentWorkouts->close();

    echo json_encode([
        "success" => true,
        "statistics" => [
            "weekly" => [
                "totalDuration" => $weeklyResult['total_duration'] ?? 0,
                "totalCalories" => $weeklyResult['total_calories'] ?? 0,
                "workoutCount" => $weeklyResult['workout_count'] ?? 0
            ],
            "monthly" => [
                "totalDuration" => $monthlyResult['total_duration'] ?? 0,
                "totalCalories" => $monthlyResult['total_calories'] ?? 0,
                "workoutCount" => $monthlyResult['workout_count'] ?? 0
            ],
            "typeDistribution" => $typeDistribution,
            "recentActivities" => $recentActivities
        ]
    ]);

} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
}

$conn->close();
?>