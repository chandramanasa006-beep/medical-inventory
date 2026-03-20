<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

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
