<?php 
require './security.inc.php';

// Initialize secure session
initializeSecureSession();

// Verify CSRF token for security
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF token validation failed in logout.inc.php');
        header("Location: ../index.php?error=invalid_token");
        exit();
    }
} else {
    // Only allow POST requests for logout
    logSecurityEvent('Invalid request method for logout: ' . $_SERVER['REQUEST_METHOD']);
    header("Location: ../index.php?error=invalid_request");
    exit();
}

// Log logout event
if (isset($_SESSION['userUid'])) {
    logSecurityEvent('User logout: ' . $_SESSION['userUid'], 'INFO');
}

// Clear all session data
$_SESSION = array();

// Destroy the session cookie securely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: ../index.php?logout=success");
exit();
?>