<?php
// add_workout.php
require_once '../config/database.php';

error_log("=== ADD WORKOUT API CALLED ===");

// Get raw POST data
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Extract data
    $userId = $data['userId'] ?? 0;
    $type = $data['type'] ?? '';
    $duration = $data['duration'] ?? 0;
    $distance = isset($data['distance']) && $data['distance'] !== null ? (float)$data['distance'] : null;
    $calories = $data['calories'] ?? 0;
    $notes = $data['notes'] ?? '';
    $date = $data['date'] ?? date('Y-m-d');
    $timestamp = $data['timestamp'] ?? time();
    
    error_log("Adding workout - User: $userId, Type: $type, Date: $date");
    
    // Check if user exists
    $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkUser->bind_param("i", $userId);
    $checkUser->execute();
    $checkUser->store_result();
    
    if ($checkUser->num_rows == 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);
        exit();
    }
    $checkUser->close();
    
    // Insert workout
    if ($distance !== null) {
        $sql = "INSERT INTO workouts (user_id, type, duration, distance, calories, notes, date, timestamp, synced) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isidissi", $userId, $type, $duration, $distance, $calories, $notes, $date, $timestamp);
    } else {
        $sql = "INSERT INTO workouts (user_id, type, duration, calories, notes, date, timestamp, synced) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiissi", $userId, $type, $duration, $calories, $notes, $date, $timestamp);
    }
    
    if ($stmt->execute()) {
        $workout_id = $stmt->insert_id;
        
        echo json_encode([
            "success" => true,
            "message" => "Workout added successfully",
            "workoutId" => $workout_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to add workout: " . $stmt->error
        ]);
    }
    
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
}

$conn->close();
?>