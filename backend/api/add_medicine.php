<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access. Owner role required."]);
    exit;
}
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

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
