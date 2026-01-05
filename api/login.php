<?php
// login.php
require_once '../config/database.php';

error_log("=== LOGIN API CALLED ===");

// Get raw POST data
$rawData = file_get_contents("php://input");
error_log("Raw login data: " . $rawData);

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
    
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? trim($data['password']) : '';
    
    error_log("Login attempt for email: $email");
    
    if (empty($email) || empty($password)) {
        error_log("Missing email or password");
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email and password are required"
        ]);
        exit();
    }
    
    // Get user with password (for verification)
    $stmt = $conn->prepare("SELECT id, name, email, password, age, weight, height, created_at FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        error_log("User not found with email: $email");
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
        exit();
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify password
    // Note: In your case, you're storing plain text passwords, so we'll compare directly
    // For production, you should use password_hash() and password_verify()
    if ($password === $user['password']) {
        // Remove password from response
        unset($user['password']);
        
        // Convert to proper response format
        $userResponse = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'age' => $user['age'] !== null ? (int)$user['age'] : null,
            'weight' => $user['weight'] !== null ? (float)$user['weight'] : null,
            'height' => $user['height'] !== null ? (float)$user['height'] : null,
            'createdAt' => $user['created_at']
        ];
        
        error_log("Login successful for user: " . $user['id']);
        
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => $userResponse
        ], JSON_PRETTY_PRINT);
        
    } else {
        error_log("Invalid password for email: $email");
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed. Use POST method."
    ]);
}

$conn->close();
error_log("=== LOGIN API ENDED ===");
?>