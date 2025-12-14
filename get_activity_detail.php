<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $activityId = $_GET['id'] ?? '';
    
    if (empty($activityId) || !is_numeric($activityId)) {
        sendResponse(false, "Valid Activity ID is required");
    }
    
    $query = "
        SELECT a.*, 
               w.exercise_name, w.sets, w.reps, w.weight
        FROM activities a
        LEFT JOIN weightlifting_activities w ON a.id = w.activity_id
        WHERE a.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        sendResponse(false, "Activity not found");
    }
    
    $activity = $result->fetch_assoc();
    
    $activityData = [
        "id" => (int)$activity['id'],
        "user_id" => (int)$activity['user_id'],
        "type" => $activity['type'],
        "duration" => (int)$activity['duration'],
        "distance" => (float)$activity['distance'],
        "calories" => (int)$activity['calories'],
        "note" => $activity['note'],
        "date" => $activity['date'],
        "created_at" => $activity['created_at'],
        "exercise_name" => $activity['exercise_name'],
        "sets" => $activity['sets'] ? (int)$activity['sets'] : null,
        "reps" => $activity['reps'] ? (int)$activity['reps'] : null,
        "weight" => $activity['weight'] ? (float)$activity['weight'] : null
    ];
    
    sendResponse(true, "Activity retrieved successfully", $activityData);
} else {
    sendResponse(false, "Invalid request method. Use GET");
}
?>