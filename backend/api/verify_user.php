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

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Please enter an email address"]);
    return;
}

try {
    $collection = $db->users;
    $user = $collection->findOne(['email' => $email]);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        return;
    }

    echo json_encode(["success" => true, "message" => "User verified"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
