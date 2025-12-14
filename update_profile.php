<?php
require_once 'config.php';

$userId = validateToken($pdo);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $updateFields = [];
    $params = [];

    if (isset($data->height_cm) && $data->height_cm > 0) {
        $updateFields[] = 'height_cm = ?';
        $params[] = $data->height_cm;
    }

    if (isset($data->weight_kg) && $data->weight_kg > 0) {
        $updateFields[] = 'weight_kg = ?';
        $params[] = $data->weight_kg;
    }

    if (isset($data->birth_date)) {
        $updateFields[] = 'birth_date = ?';
        $params[] = $data->birth_date;
    }

    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit();
    }

    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        // Get updated user data
        $userStmt = $pdo->prepare("SELECT id, username, email, height_cm, weight_kg, birth_date FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
}
?>