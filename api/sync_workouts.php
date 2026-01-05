<?php
// sync_workouts.php
require_once '../config/database.php';

error_log("=== SYNC WORKOUTS API CALLED ===");

// Get raw POST data
$rawData = file_get_contents("php://input");
error_log("Raw sync data length: " . strlen($rawData));

$data = json_decode($rawData, true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON data",
            "error" => json_last_error_msg()
        ]);
        exit();
    }
    
    error_log("Sync data received: " . print_r($data, true));
    
    $workouts = $data['workouts'] ?? [];
    $user_id = 0;
    
    // Extract user_id from multiple possible field names
    if (isset($data['userId'])) {
        $user_id = (int)$data['userId'];
    } elseif (isset($data['user_id'])) {
        $user_id = (int)$data['user_id'];
    } elseif (isset($data['userID'])) {
        $user_id = (int)$data['userID'];
    }
    
    error_log("Syncing workouts for user: $user_id, Count: " . count($workouts));
    
    if (empty($workouts) || $user_id <= 0) {
        error_log("Invalid input: user_id=$user_id, workouts count=" . count($workouts));
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid input data. User ID and workouts array are required."
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
            "message" => "User not found"
        ]);
        $checkUser->close();
        exit();
    }
    $checkUser->close();
    
    $successCount = 0;
    $failedCount = 0;
    $skippedCount = 0;
    $syncedIds = [];
    
    foreach ($workouts as $workout) {
        // Extract workout data
        $type = isset($workout['type']) ? trim($workout['type']) : '';
        $duration = isset($workout['duration']) ? (int)$workout['duration'] : 0;
        $distance = isset($workout['distance']) && $workout['distance'] !== null ? (float)$workout['distance'] : null;
        $calories = isset($workout['calories']) ? (int)$workout['calories'] : 0;
        $notes = isset($workout['notes']) ? trim($workout['notes']) : '';
        $timestamp = isset($workout['timestamp']) ? (int)$workout['timestamp'] : 0;
        
        // Handle date
        $date_input = isset($workout['date']) ? trim($workout['date']) : '';
        if (!empty($date_input)) {
            $parsed_date = strtotime($date_input);
            $formattedDate = $parsed_date !== false ? date('Y-m-d', $parsed_date) : date('Y-m-d');
        } else {
            $formattedDate = date('Y-m-d');
        }
        
        // Validate required fields
        if (empty($type) || $duration <= 0 || $timestamp <= 0) {
            error_log("Skipping invalid workout: type=$type, duration=$duration, timestamp=$timestamp");
            $failedCount++;
            continue;
        }
        
        // Check if workout already exists (by timestamp)
        $checkStmt = $conn->prepare("SELECT id FROM workouts WHERE user_id = ? AND timestamp = ?");
        $checkStmt->bind_param("ii", $user_id, $timestamp);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            // Already exists, skip
            $checkStmt->close();
            $skippedCount++;
            continue;
        }
        $checkStmt->close();
        
        // Insert new workout
        if ($distance !== null) {
            $sql = "INSERT INTO workouts (user_id, type, duration, distance, calories, notes, date, timestamp, synced) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isidissi", $user_id, $type, $duration, $distance, $calories, $notes, $formattedDate, $timestamp);
        } else {
            $sql = "INSERT INTO workouts (user_id, type, duration, calories, notes, date, timestamp, synced) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isiissi", $user_id, $type, $duration, $calories, $notes, $formattedDate, $timestamp);
        }
        
        if ($stmt->execute()) {
            $workout_id = $stmt->insert_id;
            $successCount++;
            
            $syncedIds[] = [
                'localId' => isset($workout['id']) ? (int)$workout['id'] : 0,
                'serverId' => $workout_id,
                'timestamp' => $timestamp,
                'status' => 'synced'
            ];
            
            error_log("Workout synced: ID=$workout_id, Type=$type, Timestamp=$timestamp");
        } else {
            error_log("Failed to sync workout: " . $stmt->error);
            $failedCount++;
            
            $syncedIds[] = [
                'localId' => isset($workout['id']) ? (int)$workout['id'] : 0,
                'serverId' => 0,
                'timestamp' => $timestamp,
                'status' => 'failed',
                'error' => $stmt->error
            ];
        }
        
        $stmt->close();
    }
    
    error_log("Sync completed: Success=$successCount, Failed=$failedCount, Skipped=$skippedCount");
    
    echo json_encode([
        "success" => true,
        "message" => "Sync completed successfully",
        "summary" => [
            "total" => count($workouts),
            "synced" => $successCount,
            "failed" => $failedCount,
            "skipped" => $skippedCount
        ],
        "syncedIds" => $syncedIds
    ], JSON_PRETTY_PRINT);
    
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed. Use POST method."
    ]);
}

$conn->close();
error_log("=== SYNC WORKOUTS API ENDED ===");
?>