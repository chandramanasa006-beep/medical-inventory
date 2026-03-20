<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

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
