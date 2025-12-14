<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid JSON data");
    }
    
    $error = validateRequired($input, ['user_id', 'current_password', 'new_password']);
    if ($error) {
        sendResponse(false, $error);
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
    $updateStmt->bind_param("si", $hashedPassword, $userId);
    
    if ($updateStmt->execute()) {
        sendResponse(true, "Password changed successfully");
    } else {
        sendResponse(false, "Failed to change password: " . $updateStmt->error);
    }
} else {
    sendResponse(false, "Invalid request method. Use PUT");
}
?>