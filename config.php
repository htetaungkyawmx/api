<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Accept");
header("Access-Control-Allow-Credentials: true");

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "fitness_tracker";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit();
}

// Set charset
$conn->set_charset("utf8mb4");

// Helper function to validate input
function validateInput($data) {
    if (is_array($data)) {
        return array_map('validateInput', $data);
    }
    if (is_string($data)) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

// Helper function to send JSON response
function sendResponse($success, $message, $data = null) {
    http_response_code($success ? 200 : 400);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Helper function to validate required fields
function validateRequired($input, $requiredFields) {
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            return "$field is required";
        }
    }
    return null;
}

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>