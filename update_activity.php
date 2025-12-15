<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $input = getJsonInput();
    
    if (!$input) {
        sendResponse(false, "Invalid input data");
    }
    
    if (!isset($input['id']) || empty($input['id'])) {
        sendResponse(false, "Activity ID is required");
    }
    
    $activityId = validateInput($input['id']);
    $type = isset($input['type']) ? validateInput($input['type']) : '';
    $duration = isset($input['duration']) ? validateInput($input['duration']) : '';
    $distance = isset($input['distance']) ? $input['distance'] : 0;
    $calories = isset($input['calories']) ? validateInput($input['calories']) : '';
    $note = isset($input['note']) ? validateInput($input['note']) : '';
    $date = isset($input['date']) ? validateInput($input['date']) : '';
    
    // Update activity
    $stmt = $conn->prepare("UPDATE activities SET type = ?, duration = ?, distance = ?, calories = ?, note = ?, date = ? WHERE id = ?");
    
    if (!$stmt) {
        sendResponse(false, "Database error: " . $conn->error);
    }
    
    $stmt->bind_param("sidissi", $type, $duration, $distance, $calories, $note, $date, $activityId);
    
    if ($stmt->execute()) {
        // Update weightlifting data if exists
        if ($type == 'Weightlifting' && isset($input['exercise_name'])) {
            $exerciseName = validateInput($input['exercise_name']);
            $sets = isset($input['sets']) ? validateInput($input['sets']) : 0;
            $reps = isset($input['reps']) ? validateInput($input['reps']) : 0;
            $weight = isset($input['weight']) ? validateInput($input['weight']) : 0;
            
            // Check if weightlifting record exists
            $checkStmt = $conn->prepare("SELECT id FROM weightlifting_activities WHERE activity_id = ?");
            if ($checkStmt) {
                $checkStmt->bind_param("i", $activityId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing
                    $weightStmt = $conn->prepare("UPDATE weightlifting_activities SET exercise_name = ?, sets = ?, reps = ?, weight = ? WHERE activity_id = ?");
                    if ($weightStmt) {
                        $weightStmt->bind_param("siiid", $exerciseName, $sets, $reps, $weight, $activityId);
                        $weightStmt->execute();
                    }
                } else {
                    // Insert new
                    $weightStmt = $conn->prepare("INSERT INTO weightlifting_activities (activity_id, exercise_name, sets, reps, weight) VALUES (?, ?, ?, ?, ?)");
                    if ($weightStmt) {
                        $weightStmt->bind_param("isiid", $activityId, $exerciseName, $sets, $reps, $weight);
                        $weightStmt->execute();
                    }
                }
            }
        }
        
        sendResponse(true, "Activity updated successfully");
    } else {
        sendResponse(false, "Failed to update activity: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method. Use PUT");
}
?>