<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        sendResponse(false, "Invalid input data");
    }

    $activityId = validateInput($input['id'] ?? '');

    if (empty($activityId)) {
        sendResponse(false, "Activity ID is required");
    }

    // Delete activity
    $stmt = $conn->prepare("DELETE FROM activities WHERE id = ?");
    $stmt->bind_param("i", $activityId);

    if ($stmt->execute()) {
        sendResponse(true, "Activity deleted successfully");
    } else {
        sendResponse(false, "Failed to delete activity: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method");
}
?>