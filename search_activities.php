<?php
require_once 'config.php';

$userId = validateToken($pdo);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? null;
    $date_from = $_GET['date_from'] ?? null;
    $date_to = $_GET['date_to'] ?? null;
    $limit = $_GET['limit'] ?? 20;
    $page = $_GET['page'] ?? 1;
    $offset = ($page - 1) * $limit;

    $query = "SELECT * FROM activities WHERE user_id = ?";
    $params = [$userId];

    if ($type) {
        $query .= " AND activity_type = ?";
        $params[] = $type;
    }

    if ($date_from) {
        $query .= " AND DATE(created_at) >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $query .= " AND DATE(created_at) <= ?";
        $params[] = $date_to;
    }

    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = (int)$limit;
    $params[] = (int)$offset;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $activities = $stmt->fetchAll();

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM activities WHERE user_id = ?";
    $countParams = [$userId];

    if ($type) {
        $countQuery .= " AND activity_type = ?";
        $countParams[] = $type;
    }

    if ($date_from) {
        $countQuery .= " AND DATE(created_at) >= ?";
        $countParams[] = $date_from;
    }

    if ($date_to) {
        $countQuery .= " AND DATE(created_at) <= ?";
        $countParams[] = $date_to;
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $total = $countStmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'pagination' => [
            'total' => $total,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}
?>