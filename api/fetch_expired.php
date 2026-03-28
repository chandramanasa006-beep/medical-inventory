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
    // Fetch all medicines and filter those with expiryDate in the past
    $today  = date('Y-m-d');

    // Use a filter comparing expiryDate string field (stored as YYYY-MM-DD)
    // Since expiryDate is stored as a string, we use $lt comparison
    $filter = [
        'expiryDate' => ['$lte' => $today]
    ];

    $options = ['sort' => ['expiryDate' => 1]];
    $collection = $db->medicines;
    $cursor = $collection->find($filter, $options);

    $expired = [];
    foreach ($cursor as $document) {
        $item        = (array)$document;
        $item['_id'] = (string)$item['_id'];

        if (isset($item['createdAt']) && $item['createdAt'] instanceof MongoDB\BSON\UTCDateTime) {
            $item['createdAt'] = $item['createdAt']->toDateTime()->format('Y-m-d');
        }

        $expired[] = $item;
    }

    echo json_encode([
        "success" => true,
        "count"   => count($expired),
        "data"    => $expired
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch expired medicines: " . $e->getMessage()
    ]);
}
?>
