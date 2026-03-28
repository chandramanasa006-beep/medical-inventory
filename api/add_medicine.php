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
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access. Owner role required."]);
    exit;
}
header("Content-Type: application/json");
require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (
    empty($data->medicineName) ||
    empty($data->category) ||
    empty($data->quantity) ||
    empty($data->expiryDate)
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Required fields missing: medicineName, category, quantity, expiryDate."
    ]);
    exit();
}

// Prepare medicine document for the `medicines` collection
$document = [
    "medicine_name" => $data->medicineName,
    "medicineName"  => $data->medicineName,   // keep camelCase for frontend compatibility
    "category"      => $data->category,
    "batch_number"  => $data->batchNumber ?? "",
    "batchNumber"   => $data->batchNumber ?? "",
    "manufacturer"  => $data->manufacturer ?? "",
    "supplier"      => $data->manufacturer ?? "", // map manufacturer → supplier as requested
    "expiry_date"   => $data->expiryDate,
    "expiryDate"    => $data->expiryDate,
    "quantity"      => (int)$data->quantity,
    "price"         => (float)($data->sellingPrice ?? 0),
    "costPrice"     => (float)($data->costPrice ?? 0),
    "sellingPrice"  => (float)($data->sellingPrice ?? 0),
    "description"   => $data->description ?? "",
    "status"        => ((int)$data->quantity > 20) ? "In Stock" : "Low Stock",
    "createdAt"     => new MongoDB\BSON\UTCDateTime()
];

try {
    $collection = $db->medicines;
    $insertResult = $collection->insertOne($document);

    echo json_encode([
        "success" => true,
        "message" => "Medicine added successfully.",
        "id"      => (string)$insertResult->getInsertedId()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to add medicine: " . $e->getMessage()
    ]);
}
?>
