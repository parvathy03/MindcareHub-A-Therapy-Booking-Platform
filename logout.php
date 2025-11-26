<?php
session_start();

// Determine user type and set a_userppropriate redirect
$redirect = 'index.php'; // Default redirect

if (isset($_SESSION['user_id'])) {
    // User logout
    $redirect = 'index.php';
} elseif (isset($_SESSION['therapist_id'])) {
    // Therapist logout
    $redirect = 'index.php';
} elseif (isset($_SESSION['admin_id'])) {
    // Admin logout
    $redirect = 'index.php';
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();
// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to index page
header("Location: $redirect");
exit();
?>