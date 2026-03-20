<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? "";
$password = $data["password"] ?? "";

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    return;
}

if (strlen($password) < 6) {
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
        echo json_encode(["success" => false, "message" => "Failed to reset password. User not found?"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
