<?php
// app/data/doctors_info_data.php
// Data + action handling for doctors_info_report.php

require_once 'session_handler.php'; 
require_once 'security_helper.php'; 
require_once 'db.php'; 
require_once 'logging_helper.php'; 
require_once 'iprog_sms.php';

// Require admin authentication
session_require_auth(['admin']);

$success = '';
$error = '';

// --- HANDLE ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Toggle Status (Active/Inactive)
    if (isset($_POST['toggle_status'])) {
        $target_id = $_POST['user_id'] ?? null;
        $current_status = isset($_POST['current_status']) ? (int)$_POST['current_status'] : null;
        
        if ($target_id && $current_status !== null) {
            $new_status = ($current_status === 1) ? 0 : 1;
            try {
                $stmt = $pdo->prepare("UPDATE tbluser SET status = ? WHERE user_id = ?");
                $stmt->execute([$new_status, $target_id]);
                $success = "Doctor status updated successfully.";
                if (function_exists('log_activity')) log_activity("Admin changed status for doctor {$target_id} to {$new_status}");
            } catch (Exception $ex) {
                $error = "Error updating status: " . $ex->getMessage();
            }
        }
    }

    // 2. Delete User (FIXED ORDER)
    if (isset($_POST['delete_user'])) {
        $target_id = $_POST['user_id'] ?? null;

        if ($target_id) {
            try {
                // Start Transaction to ensure all or nothing deletes
                $pdo->beginTransaction();

                // A. Delete Dependencies first (Tables referring to tblinfo)
                // 1. Delete Schedules
                $delSched = $pdo->prepare("DELETE FROM tblschedule WHERE user_id = ?");
                $delSched->execute([$target_id]);

                // 2. Delete No-Appointment settings
                $delNoAppt = $pdo->prepare("DELETE FROM tblnoappointment WHERE doctor_id = ?");
                $delNoAppt->execute([$target_id]);

                // 3. Delete Appointments where this doctor is assigned
                // Note: This assumes you want to remove appts for this doctor. 
                // If you want to keep appts but unassign doctor, you'd use UPDATE instead.
                $delAppt = $pdo->prepare("DELETE FROM tblappointment WHERE doctor = ?");
                $delAppt->execute([$target_id]);

                // B. Delete the User Account (tbluser refers to tblinfo)
                // We must delete tbluser BEFORE tblinfo because of 'user_details' constraint
                $stmtUser = $pdo->prepare("DELETE FROM tbluser WHERE user_id = ?");
                $stmtUser->execute([$target_id]);

                // C. Delete the Info Profile (tblinfo is the parent)
                $stmtInfo = $pdo->prepare("DELETE FROM tblinfo WHERE user_id = ?");
                $stmtInfo->execute([$target_id]);

                // Commit changes
                $pdo->commit();

                $success = "Doctor account and all related data deleted successfully.";
                if (function_exists('log_activity')) log_activity("Admin deleted doctor {$target_id}");

            } catch (Exception $ex) {
                // Rollback if anything fails
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Error deleting doctor: " . $ex->getMessage();
            }
        }
    }
}
    
// --- FILTERS (GET) ---
$search = $_GET['search'] ?? '';
$specialization_filter = $_GET['specialization'] ?? '';

// --- FETCH SPECIALIZATIONS ---
try {
    $stmtSpec = $pdo->query("SELECT DISTINCT specialization FROM tblinfo WHERE specialization != '' ORDER BY specialization");
    $specializations = $stmtSpec->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $specializations = []; }

// --- FETCH DOCTORS ---
try {
    $sql = "SELECT u.user_id, u.status AS account_status,
                   i.first_name, i.last_name, i.specialization, i.contact, i.image
            FROM tbluser u
            LEFT JOIN tblinfo i ON i.user_id = u.user_id
            WHERE u.user_type = 'doctor'";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (i.last_name LIKE ? OR i.first_name LIKE ? OR u.user_id LIKE ?)";
        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    }
    if (!empty($specialization_filter)) {
        $sql .= " AND i.specialization = ?";
        $params[] = $specialization_filter;
    }
    $sql .= " ORDER BY i.last_name, i.first_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $doctors = [];
    $error = "Database Error: " . $e->getMessage();
}
?>