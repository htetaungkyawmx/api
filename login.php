<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = getJsonInput();
    
    if (!$input || empty($input)) {
        sendResponse(false, "Invalid or empty input data");
    }
    
    // Validate required fields
    if (!isset($input['email']) || empty($input['email'])) {
        sendResponse(false, "Email is required");
    }
    
    if (!isset($input['password']) || empty($input['password'])) {
        sendResponse(false, "Password is required");
    }
    
    $email = validateInput($input['email']);
    $password = $input['password'];
    
    // Get user from database
    $stmt = $conn->prepare("SELECT id, name, email, password, age, weight, height, gender, daily_goal FROM users WHERE email = ?");
    
    if (!$stmt) {
        sendResponse(false, "Database error");
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        sendResponse(false, "Invalid email or password");
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Remove password from response
        unset($user['password']);
        sendResponse(true, "Login successful", $user);
    } else {
        sendResponse(false, "Invalid email or password");
    }
} else {
    sendResponse(false, "Invalid request method. Use POST");
}
?>