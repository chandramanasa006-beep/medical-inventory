<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "authenticated" => true,
        "user_id"       => $_SESSION['user_id'],
        "role"          => $_SESSION['role'] ?? "staff",
        "username"      => $_SESSION['username'] ?? "user"
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        "authenticated" => false,
        "message"       => "Unauthorized access."
    ]);
}
?>
