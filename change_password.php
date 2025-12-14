<?php
require_once 'config.php';

$userId = validateToken($pdo);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($data->current_password) || empty($data->new_password)) {
        echo json_encode(['success' => false, 'message' => 'Both current and new passwords are required']);
        exit();
    }

    // Get current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($data->current_password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }

    // Validate new password
    if (strlen($data->new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
        exit();
    }

    // Update password
    $newHash = password_hash($data->new_password, PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");

    if ($updateStmt->execute([$newHash, $userId])) {
        // Invalidate all existing tokens
        $tokenStmt = $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?");
        $tokenStmt->execute([$userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully. Please login again.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to change password']);
    }
}
?>