<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $user_id = $_GET['user_id'] ?? 0;

    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "User ID is required"
        ]);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT id, user_id, type, target, current, deadline, achieved, created_at 
        FROM goals 
        WHERE user_id = ? 
        ORDER BY deadline ASC, created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $goals = [];
    while ($row = $result->fetch_assoc()) {
        $goals[] = [
            'id' => $row['id'],
            'userId' => $row['user_id'],
            'type' => $row['type'],
            'target' => (float)$row['target'],
            'current' => (float)$row['current'],
            'deadline' => $row['deadline'],
            'achieved' => (bool)$row['achieved'],
            'progress' => $row['target'] > 0 ? ($row['current'] / $row['target']) * 100 : 0
        ];
    }

    echo json_encode($goals);

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