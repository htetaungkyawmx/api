<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get input
    $input = getJsonInput();
    
    if (empty($input)) {
        sendResponse(false, "No input data received");
    }
    
    // Log for debugging (optional)
    // file_put_contents('debug.log', "Register input: " . print_r($input, true) . "\n", FILE_APPEND);
    
    // Validate required fields
    if (!isset($input['name']) || empty($input['name'])) {
        sendResponse(false, "Name is required");
    }
    
    if (!isset($input['email']) || empty($input['email'])) {
        sendResponse(false, "Email is required");
    }
    
    if (!isset($input['password']) || empty($input['password'])) {
        sendResponse(false, "Password is required");
    }
    
    $name = validateInput($input['name']);
    $email = validateInput($input['email']);
    $password = $input['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, "Invalid email format");
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        sendResponse(false, "Password must be at least 6 characters");
    }
    
    try {
        // Check if email exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$checkStmt) {
            sendResponse(false, "Database prepare failed");
        }
        
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
        if (!$stmt) {
            sendResponse(false, "Database prepare failed");
        }
        
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            
            // Get user data without password
            $userStmt = $conn->prepare("SELECT id, name, email, age, weight, height, gender, daily_goal FROM users WHERE id = ?");
            $userStmt->bind_param("i", $userId);
            $userStmt->execute();
            $result = $userStmt->get_result();
            $userData = $result->fetch_assoc();
            
            sendResponse(true, "Registration successful", $userData);
        } else {
            sendResponse(false, "Registration failed");
        }
        
    } catch (Exception $e) {
        sendResponse(false, "Server error: " . $e->getMessage());
    }
    
} else {
    sendResponse(false, "Invalid request method. Use POST");
}
?>