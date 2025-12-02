<?php
// admin_home_data.php
// Responsible for authentication and fetching all data required by the admin_home template.

// Required resources
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

// Require admin authentication
session_require_auth(['admin']);

// Prepare safe default values so template can render even if a query fails
$adminName = session_get_username() ?: 'Admin';
$pending_count = 0;
$today_count = 0;
$total_doctors = 0;
$total_patients = 0;
$total_profiles = 0;
$recentAppointments = [];
$activities = [];

try {
    // Admin Info (prefer full name from tblinfo)
    $user_id = session_get_user_id();
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM tblinfo WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($info && !empty($info['first_name'])) {
            $adminName = trim($info['first_name'] . ' ' . $info['last_name']);
        }
    }

    // --- DASHBOARD METRICS ---

    // 1. Pending Requests (Status 2)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblappointment WHERE status = 2");
    $stmt->execute();
    $pending_count = (int)$stmt->fetchColumn();

    // 2. Today's Appointments (Status 1 - Confirmed)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblappointment WHERE booking_date = CURDATE() AND status = 1");
    $stmt->execute();
    $today_count = (int)$stmt->fetchColumn();

    // 3. Active Doctors
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbluser WHERE user_type = 'doctor'");
    $stmt->execute();
    $total_doctors = (int)$stmt->fetchColumn();

    // 4. Registered Patients
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbluser WHERE user_type = 'user'");
    $stmt->execute();
    $total_patients = (int)$stmt->fetchColumn();

    // 5. Health Profiles
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_health_profile");
    $stmt->execute();
    $total_profiles = (int)$stmt->fetchColumn();

    // --- RECENT APPOINTMENTS ---
    $sql = "SELECT a.id, a.booking_date, a.booking_time, a.status,
                   p.first_name AS pfirst, p.last_name AS plast,
                   d.first_name AS dfirst, d.last_name AS dlast
            FROM tblappointment a
            LEFT JOIN tblinfo p ON p.user_id = a.user_id
            LEFT JOIN tblinfo d ON d.user_id = a.doctor
            ORDER BY a.booking_date DESC, a.booking_time DESC
            LIMIT 6";
    $stmt = $pdo->query($sql);
    $recentAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // --- RECENT ACTIVITY LOG ---
    try {
        $stmt = $pdo->query("SELECT * FROM tblactivity_log ORDER BY created_at DESC LIMIT 5");
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        // If the activity log table doesn't exist or other minor problem: keep $activities empty
        // Do not throw; template can show "no recent activities"
    }

} catch (Exception $ex) {
    // Log error for server admin and continue with defaults so page doesn't leak internal errors
    error_log('[admin_home_data] ' . $ex->getMessage());
    // Optionally set a flag for the template to show an admin-visible message.
    $data_fetch_error = true;
}