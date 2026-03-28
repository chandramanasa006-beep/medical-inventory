<?php
require_once 'db_mongo.php';

$collection = $db->users;

$user = [
    "username" => "admin",
    "email" => "admin@gmail.com",
    "password" => password_hash("admin123", PASSWORD_DEFAULT),
    "role" => "owner"
];

$collection->insertOne($user);

echo "User created successfully";
?>