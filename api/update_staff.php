<?php

// --- CORS & SESSION DEPLOYMENT SETUP ---
$allowed_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "*";
header("Access-Control-Allow-Origin: " . $allowed_origin);
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// --------------------------------------
header("Content-Type: application/json");
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access. Owner role required."]);
    exit;
}

require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Staff ID is required for updating."]);
    exit();
}

try {
    $id = new MongoDB\BSON\ObjectId($data->id);
    $updateFields = [];

    if (isset($data->name)) {
        $updateFields['personName'] = $data->name;
        $updateFields['name'] = $data->name;
    }
    if (isset($data->username)) {
        $updateFields['username'] = $data->username;
    }
    if (isset($data->email)) {
        $updateFields['email'] = $data->email;
    }
    if (!empty($data->password)) {
        $updateFields['password'] = password_hash($data->password, PASSWORD_DEFAULT);
    }

    if (empty($updateFields)) {
        echo json_encode(["success" => false, "message" => "No fields to update."]);
        exit;
    }

    $updateFields['updatedAt'] = new MongoDB\BSON\UTCDateTime();

    $collection = $db->users;
    $result = $collection->updateOne(['_id' => $id, 'role' => 'staff'], ['$set' => $updateFields]);

    if ($result->getModifiedCount() > 0 || $result->getMatchedCount() > 0) {
        echo json_encode(["success" => true, "message" => "Staff updated successfully."]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Staff not found or no changes made."]);
    }

} catch (MongoDB\Driver\Exception\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid ID format."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
