<?php
/**
 * SECURE LOGOUT HANDLER
 * Properly destroys session and redirects to login page.
 */
session_start();
require_once "../config/db.php";
require_once "../config/functions.php";

// Use POST for logout to prevent CSRF and accidental logouts via GET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log activity before destroying session
    if (isset($_SESSION['userID'])) {
        logActivity($conn, $_SESSION['userID'], $_SESSION['role'], 'Logout', 'Sessions', 'User logged out of the system');
    }

    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie if it exists
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

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header("Location: login.php");
    exit();
} else {
    // If accessed via GET, redirect back to dashboard or login
    header("Location: ../student/dashboard.php");
    exit();
}
?>