<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $userId = $_GET['user_id'] ?? '';
    
    if (empty($userId) || !is_numeric($userId)) {
        sendResponse(false, "Valid User ID is required");
    }
    
    // Optional filters
    $type = $_GET['type'] ?? null;
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    
    // Build query
    $query = "
        SELECT a.*, 
               w.exercise_name, w.sets, w.reps, w.weight
        FROM activities a
        LEFT JOIN weightlifting_activities w ON a.id = w.activity_id
        WHERE a.user_id = ?
    ";
    
    $params = [$userId];
    $types = "i";
    
    if ($type) {
        $query .= " AND a.type = ?";
        $params[] = $type;
        $types .= "s";
    }
    
    if ($dateFrom) {
        $query .= " AND DATE(a.date) >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }
    
    if ($dateTo) {
        $query .= " AND DATE(a.date) <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }
    
    $query .= " ORDER BY a.date DESC LIMIT ?";
    $params[] = (int)$limit;
    $types .= "i";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            "id" => (int)$row['id'],
            "user_id" => (int)$row['user_id'],
            "type" => $row['type'],
            "duration" => (int)$row['duration'],
            "distance" => (float)$row['distance'],
            "calories" => (int)$row['calories'],
            "note" => $row['note'],
            "date" => $row['date'],
            "created_at" => $row['created_at'],
            "exercise_name" => $row['exercise_name'],
            "sets" => $row['sets'] ? (int)$row['sets'] : null,
            "reps" => $row['reps'] ? (int)$row['reps'] : null,
            "weight" => $row['weight'] ? (float)$row['weight'] : null
        ];
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM activities WHERE user_id = ?";
    $countParams = [$userId];
    
    if ($type) {
        $countQuery .= " AND type = ?";
        $countParams[] = $type;
    }
    
    $countStmt = $conn->prepare($countQuery);
    if (count($countParams) > 1) {
        $countStmt->bind_param("is", ...$countParams);
    } else {
        $countStmt->bind_param("i", ...$countParams);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    sendResponse(true, "Activities retrieved successfully", [
        "activities" => $activities,
        "total" => (int)$total
    ]);
} else {
    sendResponse(false, "Invalid request method. Use GET");
}
?>