<?php
// logging_helper.php - Robust activity logging helper

// Ensure session is started if the app hasn't already
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/**
 * Log a generic activity to tblactivity_log.
 *
 * @param string $action_type
 * @param string|array|object $details
 * @param string|null $user_id
 * @param string|null $user_type
 * @return bool
 */
function log_activity($action_type, $details = '', $user_id = null, $user_type = null) {
    global $pdo;

    // Defensive: ensure $pdo exists
    if (!isset($pdo) || !$pdo instanceof PDO) {
        error_log("log_activity: missing or invalid \$pdo. Action: {$action_type}");
        return false;
    }

    // Normalize details into a string (JSON for arrays/objects)
    if (is_array($details) || is_object($details)) {
        $details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } elseif ($details === null) {
        $details = '';
    } else {
        // cast to string to avoid errors
        $details = (string)$details;
    }

    // Determine user context
    if (empty($user_id) && !empty($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if (empty($user_type) && !empty($_SESSION['user_type'])) {
        $user_type = $_SESSION['user_type'];
    }
    if (empty($user_id)) {
        $user_id = 'SYSTEM';
        $user_type = $user_type ?: 'SYSTEM';
    }

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';

    $sql = "INSERT INTO tblactivity_log (user_id, user_type, action_type, details, ip_address)
            VALUES (?, ?, ?, ?, ?)";
    try {
        $stmt = $pdo->prepare($sql);
        return (bool)$stmt->execute([$user_id, $user_type, $action_type, $details, $ip_address]);
    } catch (PDOException $e) {
        // Helpful debug info - still don't crash app
        error_log("Activity Log Error: {$e->getMessage()} | Action: {$action_type} | User: {$user_id} | IP: {$ip_address}");
        error_log("Activity Log Details: " . substr($details, 0, 1000)); // avoid super-long messages
        return false;
    }
}

// Convenience wrapper used across the app for security-related logs.
if (!function_exists('log_security_event')) {
    function log_security_event(string $action_type, $details = '', $user_id = null, $user_type = null) {
        // let log_activity normalize details for us
        return log_activity($action_type, $details, $user_id, $user_type);
    }
}
?>
