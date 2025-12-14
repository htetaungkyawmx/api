<?php
require_once 'config.php';

$userId = validateToken($pdo);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['goal_type', 'target_value'];
    foreach ($required as $field) {
        if (empty($data->$field)) {
            echo json_encode(['success' => false, 'message' => "$field is required"]);
            exit();
        }
    }

    // Check if goal exists
    $checkStmt = $pdo->prepare("SELECT id FROM goals WHERE user_id = ? AND goal_type = ?");
    $checkStmt->execute([$userId, $data->goal_type]);

    if ($checkStmt->rowCount() > 0) {
        // Update existing goal
        $stmt = $pdo->prepare("
            UPDATE goals
            SET target_value = ?, current_value = ?, deadline = ?, updated_at = NOW()
            WHERE user_id = ? AND goal_type = ?
        ");
        $success = $stmt->execute([
            $data->target_value,
            $data->current_value ?? 0,
            $data->deadline ?? null,
            $userId,
            $data->goal_type
        ]);
    } else {
        // Insert new goal
        $stmt = $pdo->prepare("
            INSERT INTO goals (user_id, goal_type, target_value, current_value, deadline)
            VALUES (?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([
            $userId,
            $data->goal_type,
            $data->target_value,
            $data->current_value ?? 0,
            $data->deadline ?? null
        ]);
    }

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Goal set successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to set goal']);
    }
}
?>