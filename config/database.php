<?php
// config/database.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Accept");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = "localhost";
$username = "root";
$password = ""; 
$dbname = "fitness_tracker";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $conn->connect_error,
        "details" => [
            "host" => $host,
            "dbname" => $dbname,
            "error" => $conn->connect_error
        ]
    ]);
    exit();
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log connection success
error_log("Database connected successfully to $dbname");
?>