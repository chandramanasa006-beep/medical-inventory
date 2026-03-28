<?php

require 'vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    // Verify connection by pinging
    $client->selectDatabase('admin')->command(['ping' => 1]);
    $db = $client->medical_inventory;
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]));
}
?>