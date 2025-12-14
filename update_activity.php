<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid JSON data");
    }
    
    $error = validateRequired($input, ['id']);
    if ($error) {
        sendResponse(false, $error);
    }
    
    $activityId = validateInput($input['id']);
    $type = validateInput($input['type'] ?? '');
    $duration = validateInput($input['duration'] ?? '');
    $distance = $input['distance'] ?? 0;
    $calories = validateInput($input['calories'] ?? '');
    $note = validateInput($input['note'] ?? '');
    $date = validateInput($input['date'] ?? '');
    
    // Update activity
    $stmt = $conn->prepare("UPDATE activities SET type = ?, duration = ?, distance = ?, calories = ?, note = ?, date = ? WHERE id = ?");
    $stmt->bind_param("sidissi", $type, $duration, $distance, $calories, $note, $date, $activityId);
    
    if ($stmt->execute()) {
        // Update weightlifting data if exists
        if ($type == 'Weightlifting' && isset($input['exercise_name'])) {
            $exerciseName = validateInput($input['exercise_name']);
            $sets = validateInput($input['sets'] ?? 0);
            $reps = validateInput($input['reps'] ?? 0);
            $weight = validateInput($input['weight'] ?? 0);
            
            // Check if weightlifting record exists
            $checkStmt = $conn->prepare("SELECT id FROM weightlifting_activities WHERE activity_id = ?");
            $checkStmt->bind_param("i", $activityId);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                // Update existing
                $weightStmt = $conn->prepare("UPDATE weightlifting_activities SET exercise_name = ?, sets = ?, reps = ?, weight = ? WHERE activity_id = ?");
                $weightStmt->bind_param("siiid", $exerciseName, $sets, $reps, $weight, $activityId);
            } else {
                // Insert new
                $weightStmt = $conn->prepare("INSERT INTO weightlifting_activities (activity_id, exercise_name, sets, reps, weight) VALUES (?, ?, ?, ?, ?)");
                $weightStmt->bind_param("isiid", $activityId, $exerciseName, $sets, $reps, $weight);
            }
            $weightStmt->execute();
        }
        
        sendResponse(true, "Activity updated successfully");
    } else {
        sendResponse(false, "Failed to update activity: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method. Use PUT");
}
?>