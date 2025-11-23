<?php
// session_handler.php - Secure session management
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    
    // Set session lifetime (24 hours)
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 86400);
    
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Initialize user session after successful login
 */
function session_init_user($user_data) {
    // Regenerate session ID on login
    session_regenerate_id(true);
    
    // Store user data
    $_SESSION['user_id'] = $user_data['user_id'];
    $_SESSION['user_name'] = $user_data['user_name'];
    $_SESSION['user_type'] = $user_data['user_type'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Generate CSRF token
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
}

/**
 * Check if user is logged in
 */
function session_is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id']);
}

/**
 * Check session timeout (30 minutes of inactivity)
 */
function session_check_timeout($timeout = 1800) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_destroy_user();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Validate session integrity
 */
function session_validate() {
    if (!session_is_logged_in()) {
        return false;
    }
    
    // Check timeout
    if (!session_check_timeout()) {
        return false;
    }
    
    // Validate IP address (optional - can be too strict for mobile users)
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        session_destroy_user();
        return false;
    }
    
    return true;
}

/**
 * Destroy user session (logout)
 */
function session_destroy_user() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function session_require_auth($allowed_types = []) {
    if (!session_validate()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    if (!empty($allowed_types) && !in_array(strtolower($_SESSION['user_type'] ?? ''), array_map('strtolower', $allowed_types))) {
        header('Location: unauthorized.php');
        exit;
    }
}

/**
 * Get CSRF token
 */
function session_get_csrf_token() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > 1800) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function session_validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get current user ID
 */
function session_get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type
 */
function session_get_user_type() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get current username
 */
function session_get_username() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Check if user has specific role
 */
function session_has_role($role) {
    return strtolower($_SESSION['user_type'] ?? '') === strtolower($role);
}
?>