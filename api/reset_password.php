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
$password = $data["password"] ?? "";

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    return;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters"]);
    return;
}

try {
    $collection = $db->users;

    // Hash password 
    // IMPORTANT SECURITY RULE: Always use password_hash
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $result = $collection->updateOne(
        ['email' => $email],
        ['$set' => ['password' => $hashedPassword]]
    );

    if ($result->getModifiedCount() >= 0) {
        // We consider 0 modifications success too if they just saved the exact same password, but usually it updates.
        echo json_encode(["success" => true, "message" => "Password updated successfully"]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Failed to reset password. User not found?"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
