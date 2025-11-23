<?php
/**
 * logout.php - Complete logout implementation with security best practices
 */

// Include session handler
require_once 'session_handler.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Enhanced session destruction with audit logging
 */
function secure_logout() {
    // Optional: Log logout activity before destroying session
    if (session_is_logged_in()) {
        $user_id = session_get_user_id();
        $user_name = session_get_username();
        
        // Log logout event (add your logging mechanism here)
        // Example: log_user_activity($user_id, 'logout', 'User logged out successfully');
    }
    
    // Clear all session variables
    $_SESSION = [];
    
    // Delete the session cookie with all security parameters
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $params["path"],
                'domain' => $params["domain"],
                'secure' => $params["secure"],
                'httponly' => $params["httponly"],
                'samesite' => 'Strict'
            ]
        );
    }
    
    // Destroy the session file on server
    session_destroy();
    
    // Prevent caching of logout page
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
}

// Perform logout
secure_logout();

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit;
?>