<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $client = new MongoDB\Client(
        "mongodb+srv://Manasa_dbUser:Manasa%407106@cluster0.3z2wind.mongodb.net/?appName=Cluster0"
    );

    $db = $client->medical_inventory;

    $client->selectDatabase('admin')->command(['ping' => 1]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Database connection failed: " . $e->getMessage()
    ]);
    exit();
}
?>