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
require_once 'db_mongo.php';

try {
    // Fetch all documents from the `medicines` collection
    $collection = $db->medicines;
    $cursor = $collection->find([]);

    $inventory = [];
    foreach ($cursor as $document) {
        $item        = (array)$document;
        $item['_id'] = (string)$item['_id'];

        if (isset($item['createdAt'])) {
            $item['createdAt'] = $item['createdAt']->toDateTime()->format('c');
        }

        // Recalculate status dynamically from current quantity
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
