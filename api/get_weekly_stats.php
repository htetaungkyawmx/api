<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $userId = $_GET['user_id'] ?? '';
    
    if (empty($userId) || !is_numeric($userId)) {
        sendResponse(false, "Valid User ID is required");
    }
    
    // Get stats for last 7 days
    $query = "
        SELECT 
            DATE(date) as activity_date,
            COUNT(*) as total_activities,
            SUM(duration) as total_duration,
            SUM(distance) as total_distance,
            SUM(calories) as total_calories
        FROM activities 
        WHERE user_id = ? 
          AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(date)
        ORDER BY activity_date ASC
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        sendResponse(false, "Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $weeklyStats = [];
    $totalStats = [
        'activities' => 0,
        'duration' => 0,
        'distance' => 0,
        'calories' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $weeklyStats[] = [
            'date' => $row['activity_date'],
            'activities' => (int)$row['total_activities'],
            'duration' => (int)$row['total_duration'],
            'distance' => (float)$row['total_distance'],
            'calories' => (int)$row['total_calories']
        ];
        
        $totalStats['activities'] += (int)$row['total_activities'];
        $totalStats['duration'] += (int)$row['total_duration'];
        $totalStats['distance'] += (float)$row['total_distance'];
        $totalStats['calories'] += (int)$row['total_calories'];
    }
    
    // Fill in missing days with zeros
    $completeStats = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $found = false;
        
        foreach ($weeklyStats as $stat) {
            if ($stat['date'] == $date) {
                $completeStats[] = $stat;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $completeStats[] = [
                'date' => $date,
                'activities' => 0,
                'duration' => 0,
                'distance' => 0.0,
                'calories' => 0
            ];
        }
    }
    
    sendResponse(true, "Weekly stats retrieved successfully", [
        'daily_stats' => $completeStats,
        'total_stats' => $totalStats
    ]);
} else {
    sendResponse(false, "Invalid request method. Use GET");
}
?>