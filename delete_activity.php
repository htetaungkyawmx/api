<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid JSON data");
    }
    
    $error = validateRequired($input, ['id']);
    if ($error) {
        sendResponse(false, $error);
    }
    
    $activityId = validateInput($input['id']);
    
    // Delete activity (cascade will delete weightlifting record)
    $stmt = $conn->prepare("DELETE FROM activities WHERE id = ?");
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