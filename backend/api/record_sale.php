<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"));

/*
Expected payload:
{
    "items": [
        {"id": "<medicine_id>", "quantity": 2, "price": 10.50, "name": "Aspirin"},
        ...
    ],
    "totalAmount": 26.00,
    "customerName": "John Doe",
    "paymentMethod": "Cash"
}
*/

if (empty($data->items) || !is_array($data->items)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Sale items array is required."]);
    exit();
}

try {
    $salesCollection = $db->sales;
    $medicinesCollection = $db->medicines;

    // Build sale record for `sales` collection
    $saleRecord = [
        "items"         => $data->items,
        "totalAmount"   => (float)($data->totalAmount ?? 0),
        "customerName"  => $data->customerName ?? "Walk-in Customer",
        "paymentMethod" => $data->paymentMethod ?? "Cash",
        "saleDate"      => new MongoDB\BSON\UTCDateTime()
    ];

    $saleResult = $salesCollection->insertOne($saleRecord);
    $saleId = $saleResult->getInsertedId();

    $itemsUpdated = 0;
    // Decrement stock in `medicines` collection for each sold item
    foreach ($data->items as $item) {
        if (!empty($item->id) && !empty($item->quantity)) {
            $medicineId = new MongoDB\BSON\ObjectId($item->id);
            $updateResult = $medicinesCollection->updateOne(
                ['_id' => $medicineId],
                ['$inc' => ['quantity' => -abs((int)$item->quantity)]]
            );
            $itemsUpdated += $updateResult->getModifiedCount();
        }
    }

    echo json_encode([
        "success"      => true,
        "message"      => "Sale recorded successfully.",
        "saleId"       => (string)$saleId,
        "itemsUpdated" => $itemsUpdated
    ]);

} catch (MongoDB\Driver\Exception\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid medicine ID in item list."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
