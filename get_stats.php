<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $userId = $_GET['user_id'] ?? '';
    
    if (empty($userId) || !is_numeric($userId)) {
        sendResponse(false, "Valid User ID is required");
    }
    
    $period = $_GET['period'] ?? 'today'; // today, week, month, all
    
    // Calculate date range
    $dateCondition = "";
    switch ($period) {
        case 'today':
            $dateCondition = "DATE(date) = CURDATE()";
            break;
        case 'week':
            $dateCondition = "date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'all':
            $dateCondition = "1=1";
            break;
        default:
            $dateCondition = "DATE(date) = CURDATE()";
    }
    
    // Get stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_activities,
            COALESCE(SUM(duration), 0) as total_duration,
            COALESCE(SUM(distance), 0) as total_distance,
            COALESCE(SUM(calories), 0) as total_calories,
            GROUP_CONCAT(DISTINCT type) as activity_types
        FROM activities 
        WHERE user_id = ? AND $dateCondition
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    // Get steps (for demo, generate based on activities)
    $steps = 0;
    if ($stats['total_activities'] > 0) {
        $steps = rand(5000, 15000);
    }
    
    $response = [
        "steps" => (int)$steps,
        "calories" => (int)($stats['total_calories'] ?? 0),
        "distance" => (float)($stats['total_distance'] ?? 0),
        "duration" => (int)($stats['total_duration'] ?? 0),
        "total_activities" => (int)($stats['total_activities'] ?? 0),
        "activity_types" => $stats['activity_types'] ? explode(',', $stats['activity_types']) : []
    ];
    
    sendResponse(true, "Stats retrieved successfully", $response);
} else {
    sendResponse(false, "Invalid request method. Use GET");
}
?>