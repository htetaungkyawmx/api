<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $userId = $_GET['user_id'] ?? '';
    
    if (empty($userId) || !is_numeric($userId)) {
        sendResponse(false, "Valid User ID is required");
    }
    
    // Get today's date
    $today = date('Y-m-d');
    
    // Get today's stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_activities,
            COALESCE(SUM(duration), 0) as total_duration,
            COALESCE(SUM(distance), 0) as total_distance,
            COALESCE(SUM(calories), 0) as total_calories
        FROM activities 
        WHERE user_id = ? AND DATE(date) = ?
    ");
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    // Get random steps (for demo)
    $steps = rand(5000, 15000);
    
    $response = [
        "steps" => $steps,
        "calories" => (int)($stats['total_calories'] ?? 0),
        "distance" => (float)($stats['total_distance'] ?? 0),
        "duration" => (int)($stats['total_duration'] ?? 0),
        "total_activities" => (int)($stats['total_activities'] ?? 0)
    ];
    
    sendResponse(true, "Stats retrieved successfully", $response);
} else {
    sendResponse(false, "Invalid request method");
}
?>