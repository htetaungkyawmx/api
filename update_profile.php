<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid JSON data");
    }
    
    $error = validateRequired($input, ['user_id']);
    if ($error) {
        sendResponse(false, $error);
    }
    
    $userId = validateInput($input['user_id']);
    $name = validateInput($input['name'] ?? '');
    $email = validateInput($input['email'] ?? '');
    $age = validateInput($input['age'] ?? null);
    $weight = $input['weight'] ?? null;
    $height = $input['height'] ?? null;
    $gender = validateInput($input['gender'] ?? '');
    $dailyGoal = validateInput($input['daily_goal'] ?? null);
    
    // Check if user exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows == 0) {
        sendResponse(false, "User not found");
    }
    
    // Build update query dynamically
    $updateFields = [];
    $params = [];
    $types = "";
    
    if (!empty($name)) {
        $updateFields[] = "name = ?";
        $params[] = $name;
        $types .= "s";
    }
    
    if (!empty($email)) {
        // Check if email is already taken by another user
        $emailCheckStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $emailCheckStmt->bind_param("si", $email, $userId);
        $emailCheckStmt->execute();
        
        if ($emailCheckStmt->get_result()->num_rows > 0) {
            sendResponse(false, "Email already taken by another user");
        }
        
        $updateFields[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }
    
    if ($age !== null) {
        $updateFields[] = "age = ?";
        $params[] = $age;
        $types .= "i";
    }
    
    if ($weight !== null) {
        $updateFields[] = "weight = ?";
        $params[] = $weight;
        $types .= "d";
    }
    
    if ($height !== null) {
        $updateFields[] = "height = ?";
        $params[] = $height;
        $types .= "d";
    }
    
    if (!empty($gender)) {
        $updateFields[] = "gender = ?";
        $params[] = $gender;
        $types .= "s";
    }
    
    if ($dailyGoal !== null) {
        $updateFields[] = "daily_goal = ?";
        $params[] = $dailyGoal;
        $types .= "i";
    }
    
    if (empty($updateFields)) {
        sendResponse(false, "No fields to update");
    }
    
    // Add user_id to params
    $params[] = $userId;
    $types .= "i";
    
    $query = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Get updated user data
        $userStmt = $conn->prepare("SELECT id, name, email, age, weight, height, gender, daily_goal FROM users WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userData = $userStmt->get_result()->fetch_assoc();
        
        sendResponse(true, "Profile updated successfully", $userData);
    } else {
        sendResponse(false, "Failed to update profile: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method. Use PUT");
}
?>