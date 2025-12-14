<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid JSON data");
    }
    
    // Validate required fields
    $required = ['user_id', 'type', 'duration', 'calories', 'date'];
    $error = validateRequired($input, $required);
    if ($error) {
        sendResponse(false, $error);
    }
    
    $userId = validateInput($input['user_id']);
    $type = validateInput($input['type']);
    $duration = validateInput($input['duration']);
    $distance = $input['distance'] ?? 0;
    $calories = validateInput($input['calories']);
    $note = validateInput($input['note'] ?? '');
    $date = validateInput($input['date']);
    
    // Check if user exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows == 0) {
        sendResponse(false, "User not found");
    }
    
    // Insert activity
    $stmt = $conn->prepare("INSERT INTO activities (user_id, type, duration, distance, calories, note, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isidiss", $userId, $type, $duration, $distance, $calories, $note, $date);
    
    if ($stmt->execute()) {
        $activityId = $stmt->insert_id;
        
        // Handle weightlifting specific data
        if ($type == 'Weightlifting' && isset($input['exercise_name'])) {
            $exerciseName = validateInput($input['exercise_name']);
            $sets = validateInput($input['sets'] ?? 0);
            $reps = validateInput($input['reps'] ?? 0);
            $weight = validateInput($input['weight'] ?? 0);
            
            $weightStmt = $conn->prepare("INSERT INTO weightlifting_activities (activity_id, exercise_name, sets, reps, weight) VALUES (?, ?, ?, ?, ?)");
            $weightStmt->bind_param("isiid", $activityId, $exerciseName, $sets, $reps, $weight);
            $weightStmt->execute();
        }
        
        // Get complete activity data
        $activityStmt = $conn->prepare("
            SELECT a.*, 
                   w.exercise_name, w.sets, w.reps, w.weight
            FROM activities a
            LEFT JOIN weightlifting_activities w ON a.id = w.activity_id
            WHERE a.id = ?
        ");
        $activityStmt->bind_param("i", $activityId);
        $activityStmt->execute();
        $activityData = $activityStmt->get_result()->fetch_assoc();
        
        sendResponse(true, "Activity added successfully", $activityData);
    } else {
        sendResponse(false, "Failed to add activity: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method. Use POST");
}
?>