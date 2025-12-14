<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid JSON data");
    }
    
    // Validate required fields
    $error = validateRequired($input, ['name', 'email', 'password']);
    if ($error) {
        sendResponse(false, $error);
    }
    
    $name = validateInput($input['name']);
    $email = validateInput($input['email']);
    $password = $input['password'];
    
    // Additional validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, "Invalid email format");
    }
    
    if (strlen($password) < 6) {
        sendResponse(false, "Password must be at least 6 characters");
    }
    
    // Check if email exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        sendResponse(false, "Email already registered");
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        
        // Get user data without password
        $userStmt = $conn->prepare("SELECT id, name, email, age, weight, height, gender, daily_goal FROM users WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userData = $userStmt->get_result()->fetch_assoc();
        
        sendResponse(true, "Registration successful", $userData);
    } else {
        sendResponse(false, "Registration failed: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method. Use POST");
}
?>