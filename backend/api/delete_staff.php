<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access. Owner role required."]);
    exit;
}

require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"));
$idString = isset($data->id) ? $data->id : (isset($_GET['id']) ? $_GET['id'] : null);

if (empty($idString)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Staff ID is required."]);
    exit();
}

try {
    $id = new MongoDB\BSON\ObjectId($idString);
    $collection = $db->users;
    
    // Ensure we only delete staff members, not the owner
    $result = $collection->deleteOne(['_id' => $id, 'role' => 'staff']);
    
    if ($result->getDeletedCount() > 0) {
        echo json_encode(["success" => true, "message" => "Staff account deleted successfully."]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Staff not found."]);
    }

} catch (MongoDB\Driver\Exception\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid ID format."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to delete: " . $e->getMessage()]);
}
?>
