<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid JSON data");
    }
    
    // Validate required fields
    if (!isset($input['user_id']) || empty($input['user_id'])) {
        sendResponse(false, "User ID is required");
    }
    
    $userId = validateInput($input['user_id']);
    $name = isset($input['name']) ? validateInput($input['name']) : null;
    $email = isset($input['email']) ? validateInput($input['email']) : null;
    $age = isset($input['age']) ? validateInput($input['age']) : null;
    $weight = isset($input['weight']) ? $input['weight'] : null;
    $height = isset($input['height']) ? $input['height'] : null;
    $gender = isset($input['gender']) ? validateInput($input['gender']) : null;
    $dailyGoal = isset($input['daily_goal']) ? validateInput($input['daily_goal']) : null;
    
    // Check if user exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows == 0) {
        sendResponse(false, "User not found");
    }
    
    // Build update query
    $updateFields = [];
    $params = [];
    $types = "";
    
    if ($name !== null) {
        $updateFields[] = "name = ?";
        $params[] = $name;
        $types .= "s";
    }
    
    if ($email !== null) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, "Invalid email format");
        }
        
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
        if ($age < 0 || $age > 120) {
            sendResponse(false, "Age must be between 0 and 120");
        }
        $updateFields[] = "age = ?";
        $params[] = $age;
        $types .= "i";
    }
    
    if ($weight !== null) {
        if ($weight < 0 || $weight > 300) {
            sendResponse(false, "Weight must be between 0 and 300 kg");
        }
        $updateFields[] = "weight = ?";
        $params[] = $weight;
        $types .= "d";
    }
    
    if ($height !== null) {
        if ($height < 0 || $height > 250) {
            sendResponse(false, "Height must be between 0 and 250 cm");
        }
        $updateFields[] = "height = ?";
        $params[] = $height;
        $types .= "d";
    }
    
    if ($gender !== null) {
        // Validate gender
        $validGenders = ['Male', 'Female'];
        if (!in_array($gender, $validGenders)) {
            sendResponse(false, "Gender must be either Male or Female");
        }
        
        $updateFields[] = "gender = ?";
        $params[] = $gender;
        $types .= "s";
    }
    
    if ($dailyGoal !== null) {
        if ($dailyGoal < 0 || $dailyGoal > 50000) {
            sendResponse(false, "Daily goal must be between 0 and 50000");
        }
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
    
    if (!$stmt) {
        sendResponse(false, "Database error: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Get updated user data
        $userStmt = $conn->prepare("SELECT id, name, email, age, weight, height, gender, daily_goal FROM users WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $result = $userStmt->get_result();
        
        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            
            // Convert types
            $userData['id'] = (int)$userData['id'];
            $userData['age'] = $userData['age'] !== null ? (int)$userData['age'] : 0;
            $userData['weight'] = $userData['weight'] !== null ? (float)$userData['weight'] : 0.0;
            $userData['height'] = $userData['height'] !== null ? (float)$userData['height'] : 0.0;
            $userData['daily_goal'] = (int)$userData['daily_goal'];
            
            sendResponse(true, "Profile updated successfully", $userData);
        } else {
            sendResponse(false, "Failed to retrieve updated user data");
        }
    } else {
        sendResponse(false, "Failed to update profile: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method. Use PUT");
}
?>