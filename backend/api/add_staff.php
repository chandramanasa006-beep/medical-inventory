<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

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
