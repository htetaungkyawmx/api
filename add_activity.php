<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid input data");
    }
    
    $userId = validateInput($input['user_id'] ?? '');
    $type = validateInput($input['type'] ?? '');
    $duration = validateInput($input['duration'] ?? '');
    $distance = $input['distance'] ?? 0;
    $calories = validateInput($input['calories'] ?? '');
    $note = validateInput($input['note'] ?? '');
    $date = validateInput($input['date'] ?? '');
    
    // Validation
    if (empty($userId) || empty($type) || empty($duration) || empty($calories) || empty($date)) {
        sendResponse(false, "Required fields: user_id, type, duration, calories, date");
    }
    
    // Check if user exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows == 0) {
        sendResponse(false, "User not found");
    }
    
    // Insert activity
    $stmt = $conn->prepare("INSERT INTO activities (user_id, type, duration, distance, calories, note, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isidiss", $userId, $type, $duration, $distance, $calories, $note, $date);
    
    if ($stmt->execute()) {
        $activityId = $stmt->insert_id;
        sendResponse(true, "Activity added successfully", [
            "activity_id" => $activityId,
            "user_id" => $userId,
            "type" => $type,
            "duration" => $duration,
            "distance" => $distance,
            "calories" => $calories,
            "note" => $note,
            "date" => $date
        ]);
    } else {
        sendResponse(false, "Failed to add activity: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method");
}
?>