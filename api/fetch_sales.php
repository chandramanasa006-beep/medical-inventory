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
    // Sort by saleDate descending (most recent first)
    $options = [
        'sort' => ['saleDate' => -1]
    ];

    $collection = $db->sales;
    $cursor = $collection->find([], $options);

    $sales = [];
    foreach ($cursor as $document) {
        $sale          = (array)$document;
        $sale['_id']   = (string)$sale['_id'];

        if (isset($sale['saleDate']) && $sale['saleDate'] instanceof MongoDB\BSON\UTCDateTime) {
            $sale['saleDate'] = $sale['saleDate']->toDateTime()->format('Y-m-d H:i:s');
        }

        // Convert items array (BSON objects → PHP arrays)
        if (isset($sale['items'])) {
            $itemArray = [];
            foreach ($sale['items'] as $item) {
                $itemArray[] = (array)$item;
            }
            $sale['items'] = $itemArray;
        }

        $sales[] = $sale;
    }

    echo json_encode([
        "success" => true,
        "count"   => count($sales),
        "data"    => $sales
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch sales: " . $e->getMessage()
    ]);
}
?>
