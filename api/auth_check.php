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
