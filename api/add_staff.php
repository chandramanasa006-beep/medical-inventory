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

if (empty($data->name) || empty($data->email) || empty($data->username) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
    exit();
}

try {
    $filter = [
        '$or' => [
            ['username' => $data->username],
            ['email' => $data->email]
        ]
    ];
    $collection = $db->users;
    if ($collection->countDocuments($filter) > 0) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Username or Email already exists."]);
        exit();
    }

    $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);

    $userDocument = [
        "role" => "staff",
        "personName" => $data->name,
        "email" => $data->email,
        "username" => $data->username,
        "password" => $hashedPassword,
        "createdAt" => new MongoDB\BSON\UTCDateTime()
    ];

    $collection->insertOne($userDocument);
    echo json_encode(["success" => true, "message" => "Staff created successfully!"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
