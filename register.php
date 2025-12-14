<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input) {
        sendResponse(false, "Invalid input data");
    }
    
    $name = validateInput($input['name'] ?? '');
    $email = validateInput($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        sendResponse(false, "All fields are required");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, "Invalid email format");
    }
    
    if (strlen($password) < 6) {
        sendResponse(false, "Password must be at least 6 characters");
    }
    
    // Check if user exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        sendResponse(false, "Email already registered");
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        sendResponse(true, "Registration successful", [
            "user_id" => $userId,
            "name" => $name,
            "email" => $email
        ]);
    } else {
        sendResponse(false, "Registration failed: " . $stmt->error);
    }
} else {
    sendResponse(false, "Invalid request method");
}
?>