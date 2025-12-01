<?php
// admin_health_profiles.php - View Patient Health Records
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['admin']);

// Fetch all health profiles
try {
    $stmt = $pdo->query("
        SELECT h.*, u.first_name, u.last_name, u.email, u.contact, u.avatar
        FROM tbl_health_profile h
        JOIN tblinfo u ON h.user_id = u.user_id
        ORDER BY h.created_at DESC
    ");
    $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>