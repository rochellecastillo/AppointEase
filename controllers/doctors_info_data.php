<?php
// app/data/doctors_info_data.php
// Data + action handling for doctors_info_report.php
// This file should NOT output HTML — it only prepares variables used by the template.

require_once 'session_handler.php'; 
require_once 'security_helper.php'; 
require_once 'db.php'; 
require_once 'logging_helper.php'; 
require_once 'iprog_sms.php'; // <--- 1. NEW: Include SMS helper

// Require admin authentication
session_require_auth(['admin']);

$success = '';
$error = '';

// --- HANDLE ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Toggle Status (Active/Inactive)
    if (isset($_POST['toggle_status'])) {
        $target_id = $_POST['user_id'] ?? null;
        $current_status = isset($_POST['current_status']) ? (int)$_POST['current_status'] : null;
        if ($target_id === null || $current_status === null) {
            $error = "Invalid request.";
        } else {
            $new_status = ($current_status === 1) ? 0 : 1;
            try {
                $stmt = $pdo->prepare("UPDATE tbluser SET status = ? WHERE user_id = ?");
                $stmt->execute([$new_status, $target_id]);
                $success = "Doctor status updated successfully.";
                if (function_exists('log_activity')) {
                    log_activity("Admin ({$_SESSION['user_id']}) changed status for user {$target_id} to {$new_status}");
                }
            } catch (Exception $ex) {
                $error = "Error updating status: " . $ex->getMessage();
            }
        }
    }
}
    
// --- FILTERS (GET) ---
$search = $_GET['search'] ?? '';
$specialization_filter = $_GET['specialization'] ?? '';

// --- FETCH SPECIALIZATIONS (For Dropdown) ---
try {
    $stmtSpec = $pdo->query("SELECT DISTINCT specialization FROM tblinfo WHERE specialization != '' ORDER BY specialization");
    $specializations = $stmtSpec->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $specializations = [];
    // Log but don't die
    if (function_exists('log_activity')) {
        log_activity("Error fetching specializations: " . $e->getMessage());
    }
}

// --- FETCH DOCTORS ---
try {
    $sql = "SELECT u.user_id, u.status AS account_status,
                   i.first_name, i.last_name, i.specialization, i.contact, i.image
            FROM tbluser u
            LEFT JOIN tblinfo i ON i.user_id = u.user_id
            WHERE u.user_type = 'doctor'";

    $params = [];

    // Apply Search
    if (!empty($search)) {
        $sql .= " AND (i.last_name LIKE ? OR i.first_name LIKE ? OR u.user_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Apply Specialization Filter
    if (!empty($specialization_filter)) {
        $sql .= " AND i.specialization = ?";
        $params[] = $specialization_filter;
    }

    $sql .= " ORDER BY i.last_name, i.first_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Provide safe fallback and capture error for template
    $doctors = [];
    $error = "Database Error: " . $e->getMessage();
    if (function_exists('log_activity')) {
        log_activity("Error fetching doctors: " . $e->getMessage());
    }
}