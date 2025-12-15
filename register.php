<?php
require_once 'config.php';

// For testing: Log the request
file_put_contents('debug.txt', date('Y-m-d H:i:s') . " - Register called\n", FILE_APPEND);

$input = getJsonInput();

// For testing: Log input
file_put_contents('debug.txt', "Input: " . print_r($input, true) . "\n", FILE_APPEND);

if (!$input) {
    sendResponse(false, "No data received");
}

// Check required fields
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

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, "Invalid email format");
}

// Check if email exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    sendResponse(false, "Email already registered");
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hashedPassword);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    
    // Get user data
    $userStmt = $conn->prepare("SELECT id, name, email, age, weight, height, gender, daily_goal FROM users WHERE id = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $result = $userStmt->get_result();
    $user = $result->fetch_assoc();
    
    sendResponse(true, "Registration successful", $user);
} else {
    sendResponse(false, "Registration failed: " . $stmt->error);
}
?>