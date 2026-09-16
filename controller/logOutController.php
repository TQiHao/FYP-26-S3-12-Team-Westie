<?php
session_start();

// 1. Unset all session variables
$_SESSION = [];

// 2. Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destroy the session
session_destroy();

// 4. Start a new session for the logout message
session_start();
$_SESSION['logout_success'] = "You have logged out successfully.";

// 5. Redirect to login page
header("Location: ../boundary/loginPage.php");
exit();
?>