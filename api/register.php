<?php
// register.php
require_once '../config/database.php';

error_log("=== REGISTER API CALLED ===");

// Get raw POST data
$rawData = file_get_contents("php://input");
error_log("Raw register data: " . $rawData);

$data = json_decode($rawData, true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON data"
        ]);
        exit();
    }
    
    $name = isset($data['name']) ? trim($data['name']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? trim($data['password']) : '';
    $age = isset($data['age']) ? (int)$data['age'] : null;
    $weight = isset($data['weight']) ? (float)$data['weight'] : null;
    $height = isset($data['height']) ? (float)$data['height'] : null;
    
    error_log("Registration attempt: Name=$name, Email=$email");
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if (!empty($errors)) {
        error_log("Validation errors: " . implode(", ", $errors));
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Validation failed",
            "errors" => $errors
        ]);
        exit();
    }
    
    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if ($checkEmail->num_rows > 0) {
        error_log("Email already registered: $email");
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email already registered"
        ]);
        $checkEmail->close();
        exit();
    }
    $checkEmail->close();
    
    // For now, store password as plain text (for testing)
    // In production, use: $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $hashedPassword = $password;
    $created_at = time();
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, age, weight, height, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiidi", $name, $email, $hashedPassword, $age, $weight, $height, $created_at);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Get created user
        $getUser = $conn->prepare("SELECT id, name, email, age, weight, height, created_at FROM users WHERE id = ?");
        $getUser->bind_param("i", $user_id);
        $getUser->execute();
        $result = $getUser->get_result();
        $user = $result->fetch_assoc();
        $getUser->close();
        
        // Prepare response
        $userResponse = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'age' => $user['age'] !== null ? (int)$user['age'] : null,
            'weight' => $user['weight'] !== null ? (float)$user['weight'] : null,
            'height' => $user['height'] !== null ? (float)$user['height'] : null,
            'createdAt' => $user['created_at']
        ];
        
        error_log("Registration successful for user: $user_id");
        
        echo json_encode([
            "success" => true,
            "message" => "Registration successful",
            "user" => $userResponse
        ], JSON_PRETTY_PRINT);
        
    } else {
        $error = $stmt->error;
        error_log("Registration failed: " . $error);
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Registration failed",
            "error" => $error
        ]);
    }
    
    $stmt->close();
    
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed. Use POST method."
    ]);
}

$conn->close();
error_log("=== REGISTER API ENDED ===");
?>