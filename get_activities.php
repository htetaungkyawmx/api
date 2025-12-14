<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $userId = $_GET['user_id'] ?? '';
    
    if (empty($userId) || !is_numeric($userId)) {
        sendResponse(false, "Valid User ID is required");
    }
    
    // Get activities for user
    $stmt = $conn->prepare("SELECT * FROM activities WHERE user_id = ? ORDER BY date DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            "id" => $row['id'],
            "user_id" => $row['user_id'],
            "type" => $row['type'],
            "duration" => $row['duration'],
            "distance" => (float)$row['distance'],
            "calories" => $row['calories'],
            "note" => $row['note'],
            "date" => $row['date'],
            "created_at" => $row['created_at']
        ];
    }
    
    sendResponse(true, "Activities retrieved successfully", $activities);
} else {
    sendResponse(false, "Invalid request method");
}
?>