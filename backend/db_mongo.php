<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->medical_inventory;
    
    // Explicitly create collections if they don't exist (MongoDB usually auto-creates, but this ensures they are available)
    // Verify connection
    $client->selectDatabase('admin')->command(['ping' => 1]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => "Database connection failed"
    ]);
    exit();
}
?>
