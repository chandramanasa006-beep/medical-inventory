<?php
session_start();
if (!isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access. Login required."]);
    exit;
}
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Medicine ID is required for updating."]);
    exit();
}

try {
    $id = new MongoDB\BSON\ObjectId($data->id);

    // Build update fields dynamically from provided data
    $updateFields = [];
    if (isset($data->medicineName))  $updateFields['medicineName'] = $updateFields['medicine_name'] = $data->medicineName;
    if (isset($data->medicine_name)) $updateFields['medicine_name'] = $updateFields['medicineName'] = $data->medicine_name;
    if (isset($data->category))      $updateFields['category']     = $data->category;
    if (isset($data->batchNumber))   $updateFields['batchNumber']  = $updateFields['batch_number']  = $data->batchNumber;
    if (isset($data->batch_number))  $updateFields['batch_number'] = $updateFields['batchNumber']   = $data->batch_number;
    if (isset($data->manufacturer))  $updateFields['manufacturer'] = $updateFields['supplier']      = $data->manufacturer;
    if (isset($data->supplier))      $updateFields['supplier']     = $updateFields['manufacturer']  = $data->supplier;
    if (isset($data->expiryDate))    $updateFields['expiryDate']   = $updateFields['expiry_date']   = $data->expiryDate;
    if (isset($data->expiry_date))   $updateFields['expiry_date']  = $updateFields['expiryDate']    = $data->expiry_date;
    if (isset($data->quantity))      $updateFields['quantity']     = (int)$data->quantity;
    if (isset($data->costPrice))     $updateFields['costPrice']    = (float)$data->costPrice;
    if (isset($data->sellingPrice))  $updateFields['sellingPrice'] = $updateFields['price'] = (float)$data->sellingPrice;
    if (isset($data->price))         $updateFields['price']        = $updateFields['sellingPrice'] = (float)$data->price;
    if (isset($data->description))   $updateFields['description']  = $data->description;

    // Recalculate status if quantity is being updated
    if (isset($updateFields['quantity'])) {
        $qty = $updateFields['quantity'];
        $updateFields['status'] = $qty <= 0 ? 'Out of Stock' : ($qty <= 20 ? 'Low Stock' : 'In Stock');
    }

    $updateFields['updatedAt'] = new MongoDB\BSON\UTCDateTime();

    $collection = $db->medicines;
    $result = $collection->updateOne(['_id' => $id], ['$set' => $updateFields]);

    if ($result->getModifiedCount() > 0 || $result->getMatchedCount() > 0) {
        echo json_encode(["success" => true, "message" => "Medicine updated successfully."]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "No medicine found with that ID."]);
    }

} catch (MongoDB\Driver\Exception\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid ID format."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to update: " . $e->getMessage()]);
}
?>
