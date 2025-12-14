<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid JSON data");
    }
    
    // Validate required fields
    $error = validateRequired($input, ['email', 'password']);
    if ($error) {
        sendResponse(false, $error);
    }
    
    $email = validateInput($input['email']);
    $password = $input['password'];
    
    // Get user from database
    $stmt = $conn->prepare("SELECT id, name, email, password, age, weight, height, gender, daily_goal FROM users WHERE email = ?");
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