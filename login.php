<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid input data");
    }
    
    $email = validateInput($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        sendResponse(false, "Email and password are required");
    }
    
    // Get user from database
    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        sendResponse(false, "User not found");
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Remove password from response
        unset($user['password']);
        sendResponse(true, "Login successful", $user);
    } else {
        sendResponse(false, "Invalid password");
    }
} else {
    sendResponse(false, "Invalid request method");
}
?>