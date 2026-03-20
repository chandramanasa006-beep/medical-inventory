<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"));

// Basic validation for required fields
if (
    empty($data->shopName) || 
    empty($data->shopAddress) || 
    empty($data->personName) || 
    empty($data->email) || 
    empty($data->username) || 
    empty($data->password)
) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
    exit();
}

$role = !empty($data->role) ? $data->role : "staff";

// Check if username or email already exists
try {
    $filter = [
        '$or' => [
            ['username' => $data->username],
            ['email' => $data->email]
        ]
    ];
    
    $collection = $db->users;
    $count = $collection->countDocuments($filter);
    
    if ($count > 0) {
        http_response_code(409); // Conflict
        echo json_encode(["success" => false, "message" => "Username or Email already exists."]);
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error during validation."]);
    exit();
}

$passwordHash = password_hash($data->password, PASSWORD_DEFAULT);

$userDocument = [
    "username" => $data->username,
    "email" => $data->email,
    "password" => $passwordHash,
    "role" => $role,
    "shopName" => $data->shopName,
    "shopAddress" => $data->shopAddress,
    "personName" => $data->personName,
    "phone" => $data->phone ?? "",
    "provider" => "email",
    "createdAt" => new MongoDB\BSON\UTCDateTime()
];

if ($role === 'owner') {
    $userDocument["licenseNumber"] = $data->licenseNumber ?? "";
    $userDocument["gstNumber"] = $data->gstNumber ?? "";
} else if ($role === 'staff') {
    $userDocument["Qualification"] = $data->Qualification ?? "";
}

try {
    $collection = $db->users;
    $collection->insertOne($userDocument);
    
    echo json_encode([
        "success" => true,
        "message" => "Account created successfully"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to create account."]);
}
?>
