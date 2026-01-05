<?php
// get_workouts.php
require_once '../config/database.php';

error_log("=== GET WORKOUTS API CALLED ===");

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get user_id from query parameters
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 
               (isset($_GET['userId']) ? (int)$_GET['userId'] : 0);
    
    error_log("Requested workouts for user_id: $user_id");
    
    if ($user_id <= 0) {
        error_log("Invalid user_id: $user_id");
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "User ID is required and must be greater than 0",
            "received_user_id" => $user_id
        ]);
        exit();
    }
    
    // Check if user exists
    $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkUser->bind_param("i", $user_id);
    $checkUser->execute();
    $checkUser->store_result();
    
    if ($checkUser->num_rows == 0) {
        error_log("User not found: $user_id");
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "User not found",
            "user_id" => $user_id
        ]);
        $checkUser->close();
        exit();
    }
    $checkUser->close();
    
    // Get workouts with optional date filter
    $date_filter = isset($_GET['date']) ? $_GET['date'] : null;
    
    if ($date_filter) {
        // Get workouts for specific date
        $sql = "SELECT * FROM workouts WHERE user_id = ? AND date = ? ORDER BY date DESC, timestamp DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $date_filter);
    } else {
        // Get all workouts
        $sql = "SELECT * FROM workouts WHERE user_id = ? ORDER BY date DESC, timestamp DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $workouts = [];
    while ($row = $result->fetch_assoc()) {
        $workouts[] = [
            'id' => (int)$row['id'],
            'userId' => (int)$row['user_id'],
            'type' => $row['type'],
            'duration' => (int)$row['duration'],
            'distance' => $row['distance'] !== null ? (float)$row['distance'] : null,
            'calories' => (int)$row['calories'],
            'notes' => $row['notes'],
            'date' => $row['date'],
            'timestamp' => (int)$row['timestamp'],
            'synced' => (bool)$row['synced'],
            'createdAt' => $row['created_at']
        ];
    }
    
    $stmt->close();
    
    error_log("Found " . count($workouts) . " workouts for user $user_id");
    
    echo json_encode([
        "success" => true,
        "count" => count($workouts),
        "workouts" => $workouts
    ], JSON_PRETTY_PRINT);
    
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed. Use GET method."
    ]);
}

$conn->close();
error_log("=== GET WORKOUTS API ENDED ===");
?>