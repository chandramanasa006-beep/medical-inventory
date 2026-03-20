<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access. Owner role required."]);
    exit;
}

require_once 'db_mongo.php';

try {
    $collection = $db->users;
    $cursor = $collection->find(['role' => 'staff']);
    
    $staffList = [];
    foreach ($cursor as $staff) {
        $staffArray = (array)$staff;
        unset($staffArray['password']); // Never return password hashes
        $staffList[] = $staffArray;
    }
    
    echo json_encode([
        "success" => true,
        "data" => $staffList
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
