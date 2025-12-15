<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $input = getJsonInput();
    
    if (!$input) {
        sendResponse(false, "Invalid input data");
    }
    
    if (!isset($input['user_id']) || empty($input['user_id'])) {
        sendResponse(false, "User ID is required");
    }
    
    if (!isset($input['current_password']) || empty($input['current_password'])) {
        sendResponse(false, "Current password is required");
    }
    
    if (!isset($input['new_password']) || empty($input['new_password'])) {
        sendResponse(false, "New password is required");
    }
    
    $userId = validateInput($input['user_id']);
    $currentPassword = $input['current_password'];
    $newPassword = $input['new_password'];
    
    // Validate new password
    if (strlen($newPassword) < 6) {
        sendResponse(false, "New password must be at least 6 characters");
    }
    
    // Get current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    
    if (!$stmt) {
        sendResponse(false, "Database error");
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        sendResponse(false, "User not found");
    }
    
    $user = $result->fetch_assoc();
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        sendResponse(false, "Current password is incorrect");
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Update password
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    
    if (!$updateStmt) {
        sendResponse(false, "Database error");
    }
    
    $updateStmt->bind_param("si", $hashedPassword, $userId);
    
    if ($updateStmt->execute()) {
        sendResponse(true, "Password changed successfully");
    } else {
        sendResponse(false, "Failed to change password");
    }
} else {
    sendResponse(false, "Invalid request method. Use PUT");
}
?>