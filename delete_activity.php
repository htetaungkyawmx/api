<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $input = getJsonInput();
    
    if (!$input) {
        sendResponse(false, "Invalid input data");
    }
    
    if (!isset($input['id']) || empty($input['id'])) {
        sendResponse(false, "Activity ID is required");
    }
    
    $activityId = validateInput($input['id']);
    
    // Delete activity (cascade will delete weightlifting record)
    $stmt = $conn->prepare("DELETE FROM activities WHERE id = ?");
    
    if (!$stmt) {
        sendResponse(false, "Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $activityId);
    
    if ($stmt->execute()) {
        sendResponse(true, "Activity deleted successfully");
    } else {
        sendResponse(false, "Failed to delete activity: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method. Use DELETE");
}
?>