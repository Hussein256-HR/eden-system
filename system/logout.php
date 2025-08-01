<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Log the logout activity if user is logged in
if (is_logged_in()) {
    log_activity('user_logout', 'User logged out: ' . ($_SESSION['username'] ?? 'Unknown'));
}

// Destroy all session data
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header('Location: login.php');
exit();
?>