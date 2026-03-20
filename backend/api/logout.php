<?php
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    header("Content-Type: application/json");
    echo json_encode(["success" => true, "message" => "Logged out successfully"]);
} else {
    header("Location: ../login_page.html");
}
?>
