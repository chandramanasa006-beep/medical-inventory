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
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access. Owner role required."]);
    exit;
}
header("Content-Type: application/json");
require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"));

// Accept ID from JSON body or query string
$idString = isset($data->id) ? $data->id : (isset($_GET['id']) ? $_GET['id'] : null);

if (!empty($idString)) {
    try {
        $id   = new MongoDB\BSON\ObjectId($idString);
        $collection = $db->medicines;
        $result = $collection->deleteOne(['_id' => $id]);

        if ($result->getDeletedCount() > 0) {
            echo json_encode([
                "success" => true,
                "message" => "Medicine deleted successfully."
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "No medicine found with that ID."
            ]);
        }

    } catch (MongoDB\Driver\Exception\InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid ID format."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to delete: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Medicine ID is required."]);
}
?>
