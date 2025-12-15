<?php
require_once 'config.php';

// Simple test endpoint
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    echo json_encode([
        "success" => true,
        "message" => "API is working!",
        "timestamp" => date('Y-m-d H:i:s')
    ]);
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = getJsonInput();
    
    echo json_encode([
        "success" => true,
        "message" => "POST received",
        "data_received" => $input
    ]);
}
?>