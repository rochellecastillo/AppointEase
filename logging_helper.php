<?php
/**
 * Function to log system activities to the database.
 * * @param string $action_type A constant describing the action (e.g., 'APPOINTMENT_CREATED').
 * @param string $details A brief description or JSON string of the action outcome.
 * @param string $user_id The user ID of the person who performed the action.
 * @param string $user_type The role of the user (e.g., 'admin', 'doctor', 'user').
 * @return bool True on success, false on failure.
 */
function log_activity($action_type, $details, $user_id = null, $user_type = null) {
    // Requires a global $pdo connection
    global $pdo;

    // Default to 'SYSTEM' if user ID is not passed (e.g., cron jobs, initial setup)
    if (empty($user_id)) {
        $user_id = 'SYSTEM';
        $user_type = 'SYSTEM';
    }

    // Attempt to get user ID and type from session if not passed
    if (empty($user_id) && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if (empty($user_type) && isset($_SESSION['user_type'])) {
        $user_type = $_SESSION['user_type'];
    }

    // Get the IP address of the user
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';

    $sql = "INSERT INTO tblactivity_log 
            (user_id, user_type, action_type, details, ip_address) 
            VALUES (?, ?, ?, ?, ?)";
            
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $user_id, 
            $user_type, 
            $action_type, 
            $details, 
            $ip_address
        ]);
    } catch (PDOException $e) {
        // Log the error internally but prevent page crash
        error_log("Activity Log Error: " . $e->getMessage() . 
                  " Action: " . $action_type . " Details: " . $details);
        return false;
    }
}
?>