<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"), true);

$emailOrUsername = $data["email"] ?? "";
$password = $data["password"] ?? "";

if (empty($emailOrUsername) || empty($password)) {
    echo json_encode([
        "success" => false, 
        "message" => "Please provide both username/email and password"
    ]);
    return;
}

try {
    $filter = [
        '$or' => [
            ['username' => $emailOrUsername],
            ['email'    => $emailOrUsername]
        ]
    ];

    $collection = $db->users;
    $user = $collection->findOne($filter);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "No account found"]);
        return;
    }

    if (password_verify($password, $user["password"])) {
        $_SESSION['user_id']  = (string)$user["_id"];
        $_SESSION['role']     = $user["role"] ?? 'staff';
        $_SESSION['username'] = $user["username"];

        echo json_encode([
            "success"  => true,
            "message"  => "Login successful",
            "role"     => $_SESSION['role']
        ]);
        return;
    } else {
        echo json_encode(["success" => false, "message" => "Incorrect password"]);
        return;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
    return;
}
?>
