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
if (!isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access. Login required."]);
    exit;
}
header("Content-Type: application/json");
require_once 'db_mongo.php';

try {
    $collection = $db->medicines;
    $cursor = $collection->find([]);

    $inventory = [];
    foreach ($cursor as $document) {
        $item        = (array)$document;
        // Make ObjectId safe for JSON encoding cleanly or access object fields
        if(isset($item['_id'])) {
           $item['_id'] = (string)$item['_id'];
        }

        if (isset($item['createdAt'])) {
            $item['createdAt'] = clone $item['createdAt']; // BSON to readable if needed, or keeping it as is works for JS in some setups 
        }

        $qty = (int)($item['quantity'] ?? 0);
        if ($qty <= 0) {
            $item['status'] = "Out of Stock";
        } elseif ($qty <= 20) {
            $item['status'] = "Low Stock";
        } else {
            $item['status'] = "In Stock";
        }

        $inventory[] = $item;
    }

    echo json_encode([
        "success" => true,
        "count"   => count($inventory),
        "data"    => $inventory
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch inventory: " . $e->getMessage()
    ]);
}
?>
