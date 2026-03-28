<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? "";

if (empty($email)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please enter an email address"]);
    return;
}

try {
    $collection = $db->users;
    $user = $collection->findOne(['email' => $email]);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found"]);
        return;
    }

    echo json_encode(["success" => true, "message" => "User verified"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
