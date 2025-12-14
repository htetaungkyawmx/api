<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        sendResponse(false, "Invalid input data");
    }

    $activityId = validateInput($input['id'] ?? '');
    $type = validateInput($input['type'] ?? '');
    $duration = validateInput($input['duration'] ?? '');
    $distance = validateInput($input['distance'] ?? '');
    $calories = validateInput($input['calories'] ?? '');
    $note = validateInput($input['note'] ?? '');
    $date = validateInput($input['date'] ?? '');

    if (empty($activityId)) {
        sendResponse(false, "Activity ID is required");
    }

    // Update activity
    $stmt = $conn->prepare("UPDATE activities SET type = ?, duration = ?, distance = ?, calories = ?, note = ?, date = ? WHERE id = ?");
    $stmt->bind_param("sidissi", $type, $duration, $distance, $calories, $note, $date, $activityId);

    if ($stmt->execute()) {
        sendResponse(true, "Activity updated successfully");
    } else {
        sendResponse(false, "Failed to update activity: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method");
}
?>