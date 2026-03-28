<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


// ---------------- CORS ----------------
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed_origins = [
    "https://medical-inventory1.netlify.app",
    "http://localhost",
    "http://127.0.0.1",
    
];

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}


header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");


// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ---------------- SESSION ----------------
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------- RESPONSE TYPE ----------------
header("Content-Type: application/json");

// ---------------- DB ----------------
require_once 'db_mongo.php';

// ---------------- INPUT HANDLING ----------------
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Fallback if JSON fails
if (!$data) {
    $data = $_POST;
}

// Extract values safely
$emailOrUsername = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

// Debug (optional - remove later)
// file_put_contents("debug.txt", $rawInput);

// ---------------- VALIDATION ----------------
if ($emailOrUsername === "" || $password === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Please provide both username/email and password"
    ]);
    exit();
}

// ---------------- LOGIN LOGIC ----------------
try {
    $filter = [
        '$or' => [
            ['username' => $emailOrUsername],
            ['email'    => $emailOrUsername]
        ]
    ];

    $collection = $db->users;
    
   $user = $collection->findOne(
    $filter,
    ['typeMap' => ['root' => 'array', 'document' => 'array']]
);
    

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "No account found"
        ]);
        exit();
    }

    if (password_verify($password, $user["password"])) {

        $_SESSION['user_id']  = (string)$user["_id"];
        $_SESSION['role']     = $user["role"] ?? 'staff';
        $_SESSION['username'] = $user["username"];

        echo json_encode([
            "success"  => true,
            "message"  => "Login successful",
            "role"     => $_SESSION['role'],
            "username" => $_SESSION['username']
        ]);
        exit();

    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Incorrect password"
        ]);
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error" => $e->getMessage() // remove in production
    ]);
   
}
?>