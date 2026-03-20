<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

require_once 'db_mongo.php';

$data = json_decode(file_get_contents("php://input"), true);

$email    = $data["email"] ?? "";
$username = $data["username"] ?? "";
$provider = $data["provider"] ?? "";

if (empty($email) || empty($provider) || empty($username)) {
    echo json_encode([
        "success" => false, 
        "message" => "Invalid request. Missing credentials."
    ]);
    return;
}

try {
    $collection = $db->users;
    $user = $collection->findOne(['email' => $email]);

    if (!$user) {
        // User doesn't exist, create a new user account automatically.
        // We will default to a 'staff' role for social logins, as 'owner' implies business registry data.
        $userDocument = [
            "username" => $username,
            "email" => $email,
            "password" => "", // No password for social login
            "role" => "staff", // Default role
            "provider" => $provider,
            "shopName" => "",
            "shopAddress" => "",
            "personName" => $username,
            "phone" => "",
            "createdAt" => new MongoDB\BSON\UTCDateTime()
        ];
        
        $insertResult = $collection->insertOne($userDocument);
        $user_id = (string)$insertResult->getInsertedId();
        
        $_SESSION['user_id']  = $user_id;
        $_SESSION['role']     = "staff";
        $_SESSION['username'] = $username;

        echo json_encode([
            "success"  => true,
            "message"  => "Account created and logged in successfully",
            "role"     => "staff"
        ]);
        return;
    } else {
        // User exists. Update the provider just in case if it's their first time doing social login
        if (!isset($user["provider"]) || $user["provider"] !== $provider) {
             // Optional: You could allow linking if their original account was 'email', 
             // but here we just seamlessly log them in or update their provider optionally.
             // Updating provider to merge the account visually:
             $collection->updateOne(
                 ['_id' => $user['_id']],
                 ['$set' => ['provider' => $provider]]
             );
        }

        $_SESSION['user_id']  = (string)$user["_id"];
        $_SESSION['role']     = $user["role"] ?? 'staff';
        $_SESSION['username'] = $user["username"];

        echo json_encode([
            "success"  => true,
            "message"  => "Login successful",
            "role"     => $_SESSION['role']
        ]);
        return;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
    return;
}
?>