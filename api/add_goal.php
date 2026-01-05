<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $data['userId'] ?? 0;
    $type = $data['type'] ?? '';
    $target = $data['target'] ?? 0;
    $deadline = $data['deadline'] ?? '';

    if ($user_id <= 0 || empty($type) || $target <= 0 || empty($deadline)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid input data"
        ]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO goals (user_id, type, target, deadline) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $user_id, $type, $target, $deadline);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Goal added successfully",
            "goalId" => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to add goal: " . $stmt->error
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